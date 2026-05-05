<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

function service_save_redirect(string $target, string $key, string $message): void
{
	header('Location: ' . $target . '?' . $key . '=' . urlencode($message));
	exit;
}

function service_save_form_error(string $error, int $id = 0): void
{
	$target = 'service_form.php';

	if ($id > 0) {
		$target .= '?id=' . $id . '&error=' . urlencode($error);
	} else {
		$target .= '?error=' . urlencode($error);
	}

	header('Location: ' . $target);
	exit;
}

function service_save_slug(string $value): string
{
	$slug = strtolower(trim($value));
	$slug = str_replace('&', ' and ', $slug);
	$slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
	$slug = trim((string) $slug, '-');

	return $slug;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	service_save_redirect('services.php', 'error', 'invalid_request');
}

$currentUser = current_user();

if (($currentUser['role'] ?? '') !== 'admin') {
	service_save_redirect('services.php', 'error', 'permission_denied');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$serviceCategoryId = isset($_POST['service_category_id']) ? (int) $_POST['service_category_id'] : 0;
$outletId = isset($_POST['outlet_id']) ? (int) $_POST['outlet_id'] : 0;
$serviceName = trim((string) ($_POST['service_name'] ?? ''));
$serviceSlug = trim((string) ($_POST['service_slug'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$durationValue = trim((string) ($_POST['duration_minutes'] ?? ''));
$priceValue = trim((string) ($_POST['price'] ?? ''));

if ($serviceName === '' || $serviceCategoryId <= 0 || $outletId <= 0 || $durationValue === '' || $priceValue === '') {
	service_save_form_error('invalid', $id);
}

if ($serviceSlug === '') {
	$serviceSlug = service_save_slug($serviceName);
} else {
	$serviceSlug = service_save_slug($serviceSlug);
}

if ($serviceSlug === '' || !is_numeric($durationValue) || !is_numeric($priceValue)) {
	service_save_form_error('invalid', $id);
}

$durationMinutes = (int) $durationValue;
$price = (float) $priceValue;

if ($durationMinutes <= 0 || $price < 0) {
	service_save_form_error('invalid', $id);
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	service_save_form_error('database', $id);
}

try {
	if ($id > 0) {
		$existing = $pdo->prepare('SELECT id FROM services WHERE id = :id LIMIT 1');
		$existing->execute(['id' => $id]);

		if (!$existing->fetch()) {
			service_save_redirect('services.php', 'error', 'service_not_found');
		}
	}

	$category = $pdo->prepare('SELECT id FROM service_categories WHERE id = :id LIMIT 1');
	$category->execute(['id' => $serviceCategoryId]);

	if (!$category->fetch()) {
		service_save_form_error('invalid', $id);
	}

	$outlet = $pdo->prepare('SELECT id FROM branches WHERE id = :id LIMIT 1');
	$outlet->execute(['id' => $outletId]);

	if (!$outlet->fetch()) {
		service_save_form_error('invalid', $id);
	}

	$duplicate = $pdo->prepare('SELECT id FROM services WHERE service_slug = :service_slug AND id <> :id LIMIT 1');
	$duplicate->execute([
		'service_slug' => $serviceSlug,
		'id' => $id,
	]);

	if ($duplicate->fetch()) {
		service_save_form_error('duplicate_slug', $id);
	}

	$serviceData = [
		'service_category_id' => $serviceCategoryId,
		'outlet_id' => $outletId,
		'service_name' => $serviceName,
		'service_slug' => $serviceSlug,
		'description' => $description !== '' ? $description : null,
		'duration_minutes' => $durationMinutes,
		'price' => number_format($price, 2, '.', ''),
	];

	if ($id > 0) {
		$statement = $pdo->prepare(
			'UPDATE services SET
				service_category_id = :service_category_id,
				outlet_id = :outlet_id,
				service_name = :service_name,
				service_slug = :service_slug,
				description = :description,
				duration_minutes = :duration_minutes,
				price = :price
			WHERE id = :id'
		);
		$statement->execute(array_merge($serviceData, ['id' => $id]));

		service_save_redirect('services.php', 'success', 'service_updated');
	}

	$statement = $pdo->prepare(
		'INSERT INTO services
			(service_category_id, outlet_id, service_name, service_slug, description, duration_minutes, price)
		VALUES
			(:service_category_id, :outlet_id, :service_name, :service_slug, :description, :duration_minutes, :price)'
	);
	$statement->execute($serviceData);

	service_save_redirect('services.php', 'success', 'service_created');
} catch (PDOException $exception) {
	error_log('Service save failed: ' . $exception->getMessage());

	if ($exception->getCode() === '23000') {
		service_save_form_error('duplicate_slug', $id);
	}

	service_save_form_error('database', $id);
}
