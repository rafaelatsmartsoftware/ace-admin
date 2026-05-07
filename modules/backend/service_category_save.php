<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

function service_category_save_redirect(string $target, string $key, string $message): void
{
	header('Location: ' . $target . '?' . $key . '=' . urlencode($message));
	exit;
}

function service_category_save_form_error(string $error, int $id = 0): void
{
	$target = 'service_category_form.php';

	if ($id > 0) {
		$target .= '?id=' . $id . '&error=' . urlencode($error);
	} else {
		$target .= '?error=' . urlencode($error);
	}

	header('Location: ' . $target);
	exit;
}

function service_category_save_slug(string $value): string
{
	$slug = strtolower(trim($value));
	$slug = str_replace('&', ' and ', $slug);
	$slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
	$slug = trim((string) $slug, '-');

	return $slug;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	service_category_save_redirect('service_categories.php', 'error', 'invalid_request');
}

$currentUser = current_user();

if (($currentUser['role'] ?? '') !== 'admin') {
	service_category_save_redirect('service_categories.php', 'error', 'permission_denied');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$categoryName = trim((string) ($_POST['category_name'] ?? ''));
$categorySlug = trim((string) ($_POST['category_slug'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$displayOrderValue = trim((string) ($_POST['display_order'] ?? '0'));

if ($categoryName === '') {
	service_category_save_form_error('invalid', $id);
}

if ($categorySlug === '') {
	$categorySlug = service_category_save_slug($categoryName);
} else {
	$categorySlug = service_category_save_slug($categorySlug);
}

if ($categorySlug === '') {
	service_category_save_form_error('invalid', $id);
}

$displayOrder = is_numeric($displayOrderValue) ? (int) $displayOrderValue : 0;
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	service_category_save_form_error('database', $id);
}

try {
	if ($id > 0) {
		$existing = $pdo->prepare('SELECT id FROM service_categories WHERE id = :id LIMIT 1');
		$existing->execute(['id' => $id]);

		if (!$existing->fetch()) {
			service_category_save_redirect('service_categories.php', 'error', 'category_not_found');
		}
	}

	$duplicate = $pdo->prepare('SELECT id FROM service_categories WHERE category_slug = :category_slug AND id <> :id LIMIT 1');
	$duplicate->execute([
		'category_slug' => $categorySlug,
		'id' => $id,
	]);

	if ($duplicate->fetch()) {
		service_category_save_form_error('duplicate_slug', $id);
	}

	if ($id > 0) {
		$statement = $pdo->prepare(
			'UPDATE service_categories SET
				category_name = :category_name,
				category_slug = :category_slug,
				description = :description,
				display_order = :display_order
			WHERE id = :id'
		);
		$statement->execute([
			'category_name' => $categoryName,
			'category_slug' => $categorySlug,
			'description' => $description !== '' ? $description : null,
			'display_order' => $displayOrder,
			'id' => $id,
		]);

		service_category_save_redirect('service_categories.php', 'success', 'category_updated');
	}

	$statement = $pdo->prepare(
		'INSERT INTO service_categories (category_name, category_slug, description, display_order)
		VALUES (:category_name, :category_slug, :description, :display_order)'
	);
	$statement->execute([
		'category_name' => $categoryName,
		'category_slug' => $categorySlug,
		'description' => $description !== '' ? $description : null,
		'display_order' => $displayOrder,
	]);

	service_category_save_redirect('service_categories.php', 'success', 'category_created');
} catch (PDOException $exception) {
	error_log('Service category save failed: ' . $exception->getMessage());

	if ($exception->getCode() === '23000') {
		service_category_save_form_error('duplicate_slug', $id);
	}

	service_category_save_form_error('database', $id);
}
