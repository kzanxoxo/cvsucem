<?php
require_once __DIR__ . '/../../includes/auth-guard.php';

if (getUserRole() !== 'admin') {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$adminActivePage = 'users';

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int) ($_POST['user_id'] ?? 0);

    if ($userId > 0 && getUserRole() === 'admin') {
        if ($action === 'approve') {
            $stmt = $pdo->prepare('UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ? AND role = ? AND is_active = 0');
            $stmt->execute([$userId, 'organizer']);
            if ($stmt->rowCount()) {
                logActivity($pdo, $_SESSION['user_id'], 'approve_organizer', "Approved organizer user #$userId");
                setFlash('success', 'Organizer request approved. The user can now log in.');
            } else {
                setFlash('error', 'Unable to approve this request.');
            }
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role = ? AND is_active = 0');
            $stmt->execute([$userId, 'organizer']);
            if ($stmt->rowCount()) {
                logActivity($pdo, $_SESSION['user_id'], 'reject_organizer', "Rejected organizer request #$userId");
                setFlash('success', 'Organizer request rejected and removed.');
            } else {
                setFlash('error', 'Unable to reject this request.');
            }
        }
    }
    redirect(SITE_URL . '/admin/users/index.php');
}

$requests = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE role = 'organizer' AND is_active = 0 ORDER BY created_at DESC");
$requests->execute();
$requests = $requests->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Organizer Requests | <?= e(SITE_NAME) ?></title>
  <?php include __DIR__ . '/../../includes/head-admin.php'; ?>
</head>
<body>
<div class="bg-orbs" aria-hidden="true"></div>
<div class="admin-layout">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

  <main class="admin-main" role="main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px;">
        <?php include __DIR__ . '/../../includes/sidebar-toggle.php'; ?>
        <div>
          <div class="admin-page-title">Organizer Requests</div>
          <div class="meta-line"><?= date('l, F j, Y') ?></div>
        </div>
      </div>
      <div class="admin-topbar-actions">
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
      </div>
    </div>

    <div class="admin-content">
      <div class="admin-content-header">
        <div>
          <h1>Organizer Requests</h1>
          <p>Review and approve or reject pending organizer applications.</p>
        </div>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
      <?php endif; ?>

      <?php if (empty($requests)): ?>
        <div class="card card-body">
          <p>No pending organizer requests at this time.</p>
        </div>
      <?php else: ?>
        <div class="card card-body">
          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Applied</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($requests as $request): ?>
                  <tr>
                    <td><?= e($request['name']) ?></td>
                    <td><?= e($request['email']) ?></td>
                    <td><?= e(date('M j, Y g:i A', strtotime($request['created_at']))) ?></td>
                    <td>
                      <form method="POST" style="display:inline-flex;gap:8px;flex-wrap:wrap;">
                        <input type="hidden" name="user_id" value="<?= e($request['id']) ?>">
                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-primary">Approve</button>
                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" onclick="return confirm('Reject this application?')">Reject</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>
