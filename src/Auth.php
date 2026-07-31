<?php
declare(strict_types=1);

final class Auth
{
    /** @var array<string,array{name:string,password_hash:string,role:string}> */
    private array $users;

    public function __construct()
    {
        $this->users = [
            'altan@example.test' => [
                'name' => 'Altan Demo',
                'password_hash' => password_hash('demo123', PASSWORD_DEFAULT),
                'role' => 'Owner & Approver',
            ],
            'sara@example.test' => [
                'name' => 'Sara Approver',
                'password_hash' => password_hash('demo123', PASSWORD_DEFAULT),
                'role' => 'Approver',
            ],
            'viewer@example.test' => [
                'name' => 'Victor Viewer',
                'password_hash' => password_hash('demo123', PASSWORD_DEFAULT),
                'role' => 'Read-only',
            ],
        ];
    }

    public function attempt(string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        $record = $this->users[$email] ?? null;
        if (!$record || !password_verify($password, $record['password_hash'])) {
            return false;
        }

        if (!headers_sent()) {
            session_regenerate_id(true);
        }
        $_SESSION['user'] = [
            'email' => $email,
            'name' => $record['name'],
            'role' => $record['role'],
        ];
        return true;
    }

    /** @return array{email:string,name:string,role:string}|null */
    public function user(): ?array
    {
        $user = $_SESSION['user'] ?? null;
        return is_array($user) ? $user : null;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
    }
}
