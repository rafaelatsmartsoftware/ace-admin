<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

function branch_toggle_redirect(string $key, string $message): void
{
	header('Location: branches.php?' . $key . '=' . urlencode($message));
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	branch_toggle_redirect('error', 'invalid_request');
}

$currentUser = current_user();

if (($currentUser['role'] ?? '') !== 'admin') {
	branch_toggle_redirect('error', 'permission_denied');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
	branch_toggle_redirect('error', 'invalid_request');
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	branch_toggle_redirect('error', 'database');
}

try {
	$statement = $pdo->prepare('SELECT id, status FROM branches WHERE id = :id LIMIT 1');
	$statement->execute(['id' => $id]);
	$branch = $statement->fetch();

	if (!$branch) {
		branch_toggle_redirect('error', 'branch_not_found');
	}

	$currentStatus = (string) ($branch['status'] ?? '');
	$newStatus = $currentStatus === 'active' ? 'inactive' : 'active';

	$update = $pdo->prepare('UPDATE branches SET status = :status WHERE id = :id');
	$update->execute([
		'status' => $newStatus,
		'id' => $id,
	]);

	branch_toggle_redirect('success', 'status_updated');
} catch (PDOException $exception) {
	error_log('Branch status toggle failed: ' . $exception->getMessage());
	branch_toggle_redirect('error', 'database');
}
