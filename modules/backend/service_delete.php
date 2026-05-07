<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

function service_delete_redirect(string $key, string $message): void
{
	header('Location: services.php?' . $key . '=' . urlencode($message));
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	service_delete_redirect('error', 'invalid_request');
}

$currentUser = current_user();

if (($currentUser['role'] ?? '') !== 'admin') {
	service_delete_redirect('error', 'permission_denied');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
	service_delete_redirect('error', 'invalid_request');
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	service_delete_redirect('error', 'database');
}

try {
	$service = $pdo->prepare('SELECT id FROM services WHERE id = :id LIMIT 1');
	$service->execute(['id' => $id]);

	if (!$service->fetch()) {
		service_delete_redirect('error', 'service_not_found');
	}

	$bookings = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE service_id = :id');
	$bookings->execute(['id' => $id]);

	if ((int) $bookings->fetchColumn() > 0) {
		service_delete_redirect('error', 'service_has_bookings');
	}

	$delete = $pdo->prepare('DELETE FROM services WHERE id = :id');
	$delete->execute(['id' => $id]);

	service_delete_redirect('success', 'service_deleted');
} catch (PDOException $exception) {
	error_log('Service delete failed: ' . $exception->getMessage());
	service_delete_redirect('error', 'database');
}
