<?php
declare(strict_types=1);

final class Storage
{
    private string $mode;
    private string $dataDir;
    private ?PDO $pdo = null;
    private string $jsonPath;

    private function __construct(string $dataDir)
    {
        $this->dataDir = $dataDir;
        $this->jsonPath = $dataDir . '/demo.json';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0775, true);
        }

        $drivers = class_exists(PDO::class) ? PDO::getAvailableDrivers() : [];
        if (in_array('sqlite', $drivers, true)) {
            $this->mode = 'sqlite';
            $this->pdo = new PDO('sqlite:' . $dataDir . '/app.sqlite');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->initializeSqlite();
        } else {
            $this->mode = 'json';
            $this->initializeJson();
        }
    }

    public static function create(string $dataDir): self
    {
        return new self($dataDir);
    }

    public function mode(): string
    {
        return $this->mode;
    }

    /** @return array<int,array<string,mixed>> */
    public function groups(): array
    {
        return $this->all('groups');
    }

    /** @return array<int,array<string,mixed>> */
    public function jobRoles(): array
    {
        return $this->all('job_roles');
    }

    /** @return array<int,array<string,mixed>> */
    public function audit(): array
    {
        $rows = $this->all('audit');
        usort($rows, fn(array $a, array $b): int => strcmp((string)$b['created_at'], (string)$a['created_at']));
        return array_slice($rows, 0, 50);
    }

    /** @return array<string,mixed>|null */
    public function find(string $type, int $id): ?array
    {
        $table = $type === 'group' ? 'groups' : 'job_roles';
        foreach ($this->all($table) as $row) {
            if ((int)$row['id'] === $id) {
                return $row;
            }
        }
        return null;
    }

    public function canEdit(array $item, string $email): bool
    {
        $email = strtolower(trim($email));
        $people = array_merge((array)($item['owners'] ?? []), (array)($item['approvers'] ?? []));
        foreach ($people as $person) {
            if (strtolower(trim((string)$person)) === $email) {
                return true;
            }
        }
        return false;
    }

    public function updateDescription(string $type, int $id, string $description, string $email): bool
    {
        $table = $type === 'group' ? 'groups' : 'job_roles';
        $item = $this->find($type, $id);
        if (!$item || !$this->canEdit($item, $email)) {
            return false;
        }

        $description = trim($description);
        if (strlen($description) > 1000) {
            throw new InvalidArgumentException('Description may not exceed 1000 characters.');
        }

        if ($this->mode === 'sqlite') {
            $stmt = $this->pdo->prepare("UPDATE {$table} SET description = :description, updated_at = :updated_at WHERE id = :id");
            $stmt->execute([
                'description' => $description,
                'updated_at' => gmdate('c'),
                'id' => $id,
            ]);
        } else {
            $data = $this->readJson();
            foreach ($data[$table] as &$row) {
                if ((int)$row['id'] === $id) {
                    $row['description'] = $description;
                    $row['updated_at'] = gmdate('c');
                    break;
                }
            }
            unset($row);
            $this->writeJson($data);
        }

        $this->addAudit($email, $type, $id, (string)$item['name'], 'Updated description');
        return true;
    }

    /** @return array<int,array<string,mixed>> */
    private function all(string $table): array
    {
        if ($this->mode === 'sqlite') {
            $rows = $this->pdo->query("SELECT * FROM {$table}")->fetchAll();
            foreach ($rows as &$row) {
                if (isset($row['owners'])) {
                    $row['owners'] = json_decode((string)$row['owners'], true) ?: [];
                }
                if (isset($row['approvers'])) {
                    $row['approvers'] = json_decode((string)$row['approvers'], true) ?: [];
                }
            }
            unset($row);
            return $rows;
        }

        $data = $this->readJson();
        return array_values($data[$table] ?? []);
    }

    private function addAudit(string $email, string $type, int $id, string $name, string $action): void
    {
        $row = [
            'id' => time() * 1000 + random_int(10, 999),
            'user_email' => $email,
            'entity_type' => $type,
            'entity_id' => $id,
            'entity_name' => $name,
            'action' => $action,
            'created_at' => gmdate('c'),
        ];

        if ($this->mode === 'sqlite') {
            $stmt = $this->pdo->prepare('INSERT INTO audit (id, user_email, entity_type, entity_id, entity_name, action, created_at) VALUES (:id, :user_email, :entity_type, :entity_id, :entity_name, :action, :created_at)');
            $stmt->execute($row);
        } else {
            $data = $this->readJson();
            $data['audit'][] = $row;
            $this->writeJson($data);
        }
    }

    private function initializeSqlite(): void
    {
        $schema = file_get_contents(APP_ROOT . '/database/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('Database schema not found.');
        }
        $this->pdo->exec($schema);

        $count = (int)$this->pdo->query('SELECT COUNT(*) FROM groups')->fetchColumn();
        if ($count === 0) {
            $seed = $this->defaultData();
            $this->pdo->beginTransaction();
            foreach (['groups', 'job_roles'] as $table) {
                $stmt = $this->pdo->prepare("INSERT INTO {$table} (id, name, category, owners, approvers, description, status, updated_at) VALUES (:id, :name, :category, :owners, :approvers, :description, :status, :updated_at)");
                foreach ($seed[$table] as $row) {
                    $row['owners'] = json_encode($row['owners'], JSON_THROW_ON_ERROR);
                    $row['approvers'] = json_encode($row['approvers'], JSON_THROW_ON_ERROR);
                    $stmt->execute($row);
                }
            }
            foreach ($seed['audit'] as $row) {
                $stmt = $this->pdo->prepare('INSERT INTO audit (id, user_email, entity_type, entity_id, entity_name, action, created_at) VALUES (:id, :user_email, :entity_type, :entity_id, :entity_name, :action, :created_at)');
                $stmt->execute($row);
            }
            $this->pdo->commit();
        }
    }

    private function initializeJson(): void
    {
        if (!file_exists($this->jsonPath)) {
            $this->writeJson($this->defaultData());
        }
    }

    /** @return array<string,array<int,array<string,mixed>>> */
    private function readJson(): array
    {
        $content = file_get_contents($this->jsonPath);
        if ($content === false) {
            throw new RuntimeException('Unable to read demo data.');
        }
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        return is_array($data) ? $data : [];
    }

    /** @param array<string,mixed> $data */
    private function writeJson(array $data): void
    {
        $handle = fopen($this->jsonPath, 'c+');
        if (!$handle) {
            throw new RuntimeException('Unable to write demo data.');
        }
        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock demo data.');
            }
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    /** @return array<string,array<int,array<string,mixed>>> */
    public function defaultData(): array
    {
        $now = gmdate('c');
        return [
            'groups' => [
                ['id'=>1,'name'=>'Cloud Platform Readers','category'=>'Cloud','owners'=>['altan@example.test'],'approvers'=>['sara@example.test'],'description'=>'Read-only access to cloud platform dashboards and operational metrics.','status'=>'Active','updated_at'=>$now],
                ['id'=>2,'name'=>'Finance Reporting Editors','category'=>'Finance','owners'=>['maya@example.test'],'approvers'=>['altan@example.test'],'description'=>'Edit access for monthly finance reporting workspaces.','status'=>'Active','updated_at'=>$now],
                ['id'=>3,'name'=>'Customer Support Knowledge','category'=>'Support','owners'=>['leo@example.test'],'approvers'=>['sara@example.test'],'description'=>'Contributors to the internal support knowledge base.','status'=>'Review','updated_at'=>$now],
                ['id'=>4,'name'=>'Device Management Operators','category'=>'IT Operations','owners'=>['altan@example.test'],'approvers'=>['it-governance@example.test'],'description'=>'Operators for device enrollment and compliance workflows.','status'=>'Active','updated_at'=>$now],
                ['id'=>5,'name'=>'Data Analytics Sandbox','category'=>'Data','owners'=>['data-owner@example.test'],'approvers'=>['data-approver@example.test'],'description'=>'Sandbox access for approved analytics experiments using synthetic data.','status'=>'Active','updated_at'=>$now],
                ['id'=>6,'name'=>'People Operations Workspace','category'=>'People','owners'=>['people-owner@example.test'],'approvers'=>['altan@example.test'],'description'=>'Workspace access for employee lifecycle process documentation.','status'=>'Review','updated_at'=>$now],
                ['id'=>7,'name'=>'Security Incident Coordinators','category'=>'Security','owners'=>['security-owner@example.test'],'approvers'=>['security-approver@example.test'],'description'=>'Coordination group for simulated incident response exercises.','status'=>'Active','updated_at'=>$now],
                ['id'=>8,'name'=>'Procurement Requesters','category'=>'Procurement','owners'=>['procurement-owner@example.test'],'approvers'=>['sara@example.test'],'description'=>'Request permissions for approved procurement workflows.','status'=>'Inactive','updated_at'=>$now],
            ],
            'job_roles' => [
                ['id'=>101,'name'=>'Cloud Operations Specialist','category'=>'Technology','owners'=>[],'approvers'=>['altan@example.test','sara@example.test'],'description'=>'Standard access bundle for cloud operations responsibilities.','status'=>'Active','updated_at'=>$now],
                ['id'=>102,'name'=>'Finance Business Partner','category'=>'Finance','owners'=>[],'approvers'=>['finance-approver@example.test'],'description'=>'Access bundle for planning, forecasting and reporting tasks.','status'=>'Active','updated_at'=>$now],
                ['id'=>103,'name'=>'People Operations Coordinator','category'=>'People','owners'=>[],'approvers'=>['altan@example.test'],'description'=>'Access bundle for employee lifecycle coordination workflows.','status'=>'Review','updated_at'=>$now],
                ['id'=>104,'name'=>'Support Knowledge Manager','category'=>'Support','owners'=>[],'approvers'=>['sara@example.test'],'description'=>'Access for maintaining internal support documentation and FAQs.','status'=>'Active','updated_at'=>$now],
                ['id'=>105,'name'=>'Security Analyst','category'=>'Security','owners'=>[],'approvers'=>['security-approver@example.test'],'description'=>'Read and investigation permissions for the demo security workspace.','status'=>'Active','updated_at'=>$now],
            ],
            'audit' => [
                ['id'=>1,'user_email'=>'system@example.test','entity_type'=>'group','entity_id'=>1,'entity_name'=>'Cloud Platform Readers','action'=>'Demo dataset initialized','created_at'=>$now],
            ],
        ];
    }
}
