<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

function user_toggle_redirect(string $key, string $message): void
{
	header('Location: users.php?' . $key . '=' . urlencode($message));
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	user_toggle_redirect('error', 'invalid_request');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
	user_toggle_redirect('error', 'invalid_request');
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	user_toggle_redirect('error', 'database');
}

try {
	$statement = $pdo->prepare('SELECT id, role, status FROM users WHERE id = :id LIMIT 1');
	$statement->execute(['id' => $id]);
	$user = $statement->fetch();

	if (!$user) {
		user_toggle_redirect('error', 'user_not_found');
	}

	$currentStatus = (string) $user['status'];
	$newStatus = $currentStatus === 'active' ? 'inactive' : 'active';

	if (!can_manage_user_status(current_user(), $user)) {
		user_toggle_redirect('error', 'permission_denied');
	}

	$update = $pdo->prepare('UPDATE users SET status = :status WHERE id = :id');
	$update->execute([
		'status' => $newStatus,
		'id' => $id,
	]);

	user_toggle_redirect('success', 'status_updated');
} catch (PDOException $exception) {
	error_log('User status toggle failed: ' . $exception->getMessage());
	user_toggle_redirect('error', 'database');
}
