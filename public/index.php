<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

$error = null;
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$view = (string)($_GET['view'] ?? 'dashboard');
$allowedViews = ['dashboard','groups','roles','role_detail'];
if (!in_array($view, $allowedViews, true)) $view = 'dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'login') {
        if (!$security->validateCsrf($_POST['csrf'] ?? null)) {
            $error = 'Your session expired. Please try again.';
        } elseif (!$auth->attempt((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''))) {
            $error = 'Email or password is wrong.';
        } else {
            header('Location: /?view=dashboard'); exit;
        }
    }
    if ($action === 'logout' && $auth->check()) {
        if ($security->validateCsrf($_POST['csrf'] ?? null)) $auth->logout();
        header('Location: /'); exit;
    }
    if ($action === 'update_description' && $auth->check()) {
        $returnView = in_array((string)($_POST['return_view'] ?? ''), ['groups','roles'], true) ? (string)$_POST['return_view'] : 'dashboard';
        if (!$security->validateCsrf($_POST['csrf'] ?? null)) {
            $_SESSION['flash'] = ['type'=>'error','message'=>'Your session expired. Please reload the page.'];
        } else {
            try {
                $type = ($_POST['type'] ?? '') === 'job_role' ? 'job_role' : 'group';
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                $description = trim((string)($_POST['description'] ?? ''));
                $user = $auth->user();
                $ok = $id && $user && $storage->updateDescription($type, (int)$id, $description, $user['email']);
                $_SESSION['flash'] = $ok
                    ? ['type'=>'success','message'=>'Description saved.']
                    : ['type'=>'error','message'=>'You are not allowed to edit this description.'];
            } catch (Throwable $e) {
                $_SESSION['flash'] = ['type'=>'error','message'=>'Description could not be saved.'];
            }
        }
        header('Location: /?view=' . urlencode($returnView)); exit;
    }
}

$user = $auth->user();
$csrf = $security->csrfToken();
function e(string|int|float|null $v): string { return Security::e($v); }
function firstName(array $u): string { return explode(' ', trim((string)$u['name']))[0] ?: 'User'; }
function names(array $emails): string {
    $out=[]; foreach($emails as $email){$local=explode('@',(string)$email)[0];$out[]=ucwords(str_replace(['.','-','_'],' ',$local));}
    return implode(', ', $out);
}

if (!$user):
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>AccessHub — Login</title>
<link rel="stylesheet" href="/assets/app.css">
</head>
<body class="auth-page">
<header class="legacy-banner auth-banner"><img src="/assets/logo.svg" alt="AccessHub"></header>
<main class="login-card">
  <h1>Log in</h1><div class="divider"></div>
  <?php if ($error): ?><div class="status-msg status-err"><?=e($error)?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="action" value="login"><input type="hidden" name="csrf" value="<?=e($csrf)?>">
    <label>E-Mail:<input id="email" type="email" name="email" required value="altan@example.test"></label>
    <label>Password:<input type="password" name="password" required value="demo123"></label>
    <button type="submit">Log In</button>
  </form>
  <p class="demo-hint">Demo credentials: <b>altan@example.test</b> / <b>demo123</b></p>
</main>
<script src="/assets/app.js"></script>
</body></html>
<?php exit; endif;

$groups = $storage->groups();
$roles = $storage->jobRoles();
$currentEmail = (string)$user['email'];
$roleId = (int)($_GET['id'] ?? 0);
$selectedRole = $roleId ? $storage->find('job_role', $roleId) : null;
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>AccessHub — <?=e(ucwords(str_replace('_',' ',$view)))?></title>
<link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<header class="legacy-banner sticky-banner">
  <div class="banner-links"><a href="#" title="Demo link">Documentation</a><a href="/?view=dashboard">Welcome Dashboard</a></div>
  <a href="/?view=dashboard" class="logo-link"><img src="/assets/logo.svg" alt="AccessHub"></a>
  <div class="banner-actions">
    <?php if (in_array($view,['groups','roles','role_detail'],true)): ?>
      <button class="filter-btn" id="filterToggle" type="button">Filter ▾</button>
    <?php endif; ?>
    <button class="theme-btn" id="themeButton" type="button" title="Toggle dark mode">◐</button>
    <form method="post"><input type="hidden" name="action" value="logout"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><button id="logout-link" type="submit">Log out</button></form>
    <div class="filter-panel" id="filterPanel">
      <label><input type="checkbox" id="mineOnly"> Show mine</label>
      <label><input type="checkbox" id="missingApprover"> Without approver</label>
      <label><input type="checkbox" id="editableOnly"> Editable only</label>
    </div>
  </div>
</header>

<?php if ($flash): ?><div class="status-msg floating-status <?=e($flash['type']==='success'?'status-ok':'status-err')?>" id="statusToast"><?=e((string)$flash['message'])?></div><?php endif; ?>

<?php if ($view === 'dashboard'): ?>
<main class="dashboard-wrap">
  <h1 class="welcome-headline">Welcome, <?=e(firstName($user))?></h1>
  <p class="page-subtitle">Find access groups and job roles, see their owners and approvers, and maintain descriptions you are responsible for.</p>
  <section class="card-grid">
    <article class="legacy-card">
      <img src="/assets/jobroles.svg" alt="Abstract job roles illustration">
      <div class="inner"><h2>Job roles</h2><p>List of all job roles.<br>Use “Filter” to see the roles you can approve.</p><a class="primary-btn" href="/?view=roles">Show</a></div>
    </article>
    <article class="legacy-card">
      <img src="/assets/groups.svg" alt="Abstract groups illustration">
      <div class="inner"><h2>Groups</h2><p>List of all access groups.<br>Use “Filter” to see the groups you own or can approve.</p><a class="primary-btn" href="/?view=groups">Show</a></div>
    </article>
  </section>
  <section class="portfolio-note"><b>Portfolio demo:</b> This independent version uses fictional data and a local SQLite/JSON store. No company systems, credentials or proprietary content are included.</section>
</main>

<?php elseif ($view === 'groups' || $view === 'roles'): ?>
<?php $isGroups = $view === 'groups'; $records = $isGroups ? $groups : $roles; ?>
<main class="table-page">
  <div class="table-heading-row"><div><h1><?= $isGroups ? 'Groups' : 'Job roles' ?></h1><p><?= $isGroups ? 'Owners, approvers and descriptions for fictional access groups.' : 'Approvers and descriptions for fictional job roles.' ?></p></div><a class="back-link" href="/?view=dashboard">← Dashboard</a></div>
  <input id="tableSearch" class="table-search" type="search" placeholder="Search …">
  <div class="table-scroll">
  <table class="legacy-table" id="recordsTable">
    <thead><tr>
      <th><?= $isGroups ? 'Group' : 'Job role' ?> <button class="sort-btn" type="button" data-sort="name">↕</button></th>
      <?php if ($isGroups): ?><th>Owner</th><?php endif; ?>
      <th>Approver</th><th>Description</th><th class="action-col">Action</th>
    </tr></thead>
    <tbody>
    <?php foreach($records as $r): $editable=$storage->canEdit($r,$currentEmail); $approvers=(array)($r['approvers']??[]); $owners=(array)($r['owners']??[]); ?>
      <tr data-name="<?=e(strtolower((string)$r['name']))?>" data-mine="<?=($editable?'1':'0')?>" data-missing="<?=empty($approvers)?'1':'0'?>" data-editable="<?=$editable?'1':'0'?>">
        <td class="oneline"><?php if(!$isGroups): ?><a class="record-link" href="/?view=role_detail&id=<?=e((int)$r['id'])?>"><?php endif; ?><?=e((string)$r['name'])?><?php if(!$isGroups): ?></a><?php endif; ?><small><?=e((string)$r['category'])?></small></td>
        <?php if($isGroups): ?><td><?=e(names($owners))?></td><?php endif; ?>
        <td><?=e(names($approvers))?></td>
        <td class="description-cell"><span class="note-view"><?=e((string)$r['description'])?></span>
          <?php if($editable): ?><form method="post" class="note-form" hidden><input type="hidden" name="action" value="update_description"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="return_view" value="<?=e($view)?>"><input type="hidden" name="type" value="<?=$isGroups?'group':'job_role'?>"><input type="hidden" name="id" value="<?=e((int)$r['id'])?>"><textarea name="description" maxlength="1000"><?=e((string)$r['description'])?></textarea><div><button class="note-btn save" type="submit">Save</button><button class="note-btn cancel" type="button">Cancel</button></div></form><?php endif; ?>
        </td>
        <td class="action-col"><?php if($editable): ?><button class="note-btn edit" type="button">Edit</button><?php else: ?><span class="readonly-pill">View only</span><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</main>

<?php elseif ($view === 'role_detail'): ?>
<?php if(!$selectedRole): ?>
<main class="table-page"><h1>Job role not found</h1><a href="/?view=roles">Back to job roles</a></main>
<?php else: ?>
<main class="table-page">
  <div class="table-heading-row"><div><h1><?=e((string)$selectedRole['name'])?></h1><p>Fictional group assignments and location scope for this demo role.</p></div><a class="back-link" href="/?view=roles">← Job roles</a></div>
  <input id="tableSearch" class="table-search" type="search" placeholder="Search groups …">
  <div class="table-scroll"><table class="legacy-table" id="recordsTable"><thead><tr><th>Group <button class="sort-btn" type="button" data-sort="name">↕</button></th><th class="optional-col">Source</th><th>Germany</th><th>USA</th><th>APAC</th><th>Türkiye</th></tr></thead><tbody>
  <?php foreach(array_slice($groups,0,6) as $i=>$g): ?>
    <tr data-name="<?=e(strtolower((string)$g['name']))?>"><td><?=e((string)$g['name'])?><small><?=e((string)$g['category'])?></small></td><td class="optional-col">Demo directory</td><td class="<?=($i%2===0?'yes':'no')?>"><?=($i%2===0?'Yes':'No')?></td><td class="<?=($i%3!==0?'yes':'no')?>"><?=($i%3!==0?'Yes':'No')?></td><td class="<?=($i%2!==0?'yes':'no')?>"><?=($i%2!==0?'Yes':'No')?></td><td class="<?=($i%4===0?'yes':'no')?>"><?=($i%4===0?'Yes':'No')?></td></tr>
  <?php endforeach; ?>
  </tbody></table></div>
</main>
<?php endif; ?>
<?php endif; ?>
<script>window.ACCESSHUB={currentUser:<?=json_encode($currentEmail,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>};</script>
<script src="/assets/app.js"></script>
</body></html>
