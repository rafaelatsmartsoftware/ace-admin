<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

function service_category_delete_redirect(string $key, string $message): void
{
	header('Location: service_categories.php?' . $key . '=' . urlencode($message));
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	service_category_delete_redirect('error', 'invalid_request');
}

$currentUser = current_user();

if (($currentUser['role'] ?? '') !== 'admin') {
	service_category_delete_redirect('error', 'permission_denied');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
	service_category_delete_redirect('error', 'invalid_request');
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	service_category_delete_redirect('error', 'database');
}

try {
	$category = $pdo->prepare('SELECT id FROM service_categories WHERE id = :id LIMIT 1');
	$category->execute(['id' => $id]);

	if (!$category->fetch()) {
		service_category_delete_redirect('error', 'category_not_found');
	}

	$services = $pdo->prepare('SELECT COUNT(*) FROM services WHERE service_category_id = :id');
	$services->execute(['id' => $id]);

	if ((int) $services->fetchColumn() > 0) {
		service_category_delete_redirect('error', 'category_has_services');
	}

	$delete = $pdo->prepare('DELETE FROM service_categories WHERE id = :id');
	$delete->execute(['id' => $id]);

	service_category_delete_redirect('success', 'category_deleted');
} catch (PDOException $exception) {
	error_log('Service category delete failed: ' . $exception->getMessage());
	service_category_delete_redirect('error', 'database');
}
