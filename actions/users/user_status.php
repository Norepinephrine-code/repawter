<?php
require __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/admin/users/');
}

csrf_verify();
require_role(ROLE_OFFICIAL, ROLE_ADMIN);

[$clean, $errors] = validate($_POST, [
    'id'     => 'required|int',
    'status' => 'required|in:active,flagged,suspended',
    'reason' => 'nullable|max:255',
]);

if (!empty($errors)) {
    flash_old($_POST);
    flash('error', 'Invalid request: ' . implode(', ', $errors));
    redirect('/admin/users/');
}

$targetId = (int)$clean['id'];
$status   = $clean['status'];
$reason   = $clean['reason'] ?: null;

// Guard: cannot change your own account
if (user_id() === $targetId) {
    flash('error', 'You cannot change your own account status.');
    redirect('/admin/users/view.php?id=' . $targetId);
}

// Load target user
$target = UserModel::find($targetId);
if (!$target) {
    flash('error', 'User not found.');
    redirect('/admin/users/');
}

// Guard: barangay_official may only change residents; ROLE_ADMIN may change anyone
$isTargetStaff = in_array($target['role'], [ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN], true);
if (user_role() === ROLE_OFFICIAL && $isTargetStaff) {
    flash('error', 'Barangay officials may only change the status of community residents.');
    redirect('/admin/users/view.php?id=' . $targetId);
}

// Apply status change
UserModel::set_status($targetId, $status, $reason);

// Audit
$action = match($status) {
    'flagged'    => 'account_flagged',
    'suspended'  => 'account_suspended',
    'active'     => 'account_reactivated',
    default      => 'account_status_changed',
};
audit_log($action, 'user', $targetId, ['reason' => $reason], $targetId);

// Notify user
$statusLabel = ucfirst($status);
notify(
    $targetId,
    'account',
    'Account status updated',
    'Your account status is now: ' . $statusLabel . ($reason ? '. Reason: ' . $reason : '')
);

flash('success', 'Account status updated to "' . $statusLabel . '".');
redirect('/admin/users/view.php?id=' . $targetId);
