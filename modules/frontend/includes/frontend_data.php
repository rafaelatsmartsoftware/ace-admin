<?php
require_once __DIR__ . '/../../../config/database.php';

function frontend_company_defaults(): array
{
	return [
		'business_name' => 'Sparlex',
		'logo' => '',
		'phone' => '+01234567890',
		'email' => 'Example@gmail.com',
		'website' => '#',
		'main_address' => 'Find A Location',
		'description' => 'Dolor amet sit justo amet elitr clita ipsum elitr est.Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam in tempor dui, non consectetur enim.',
		'facebook_url' => '#',
		'instagram_url' => '#',
		'opening_note' => 'Monday: 09:00 am - 10:00 pm',
		'status' => 'active',
	];
}

function frontend_company_data(): array
{
	static $company = null;

	if (is_array($company)) {
		return $company;
	}

	$company = frontend_company_defaults();
	$pdo = ace_admin_db();

	if (!$pdo instanceof PDO) {
		return $company;
	}

	try {
		$statement = $pdo->query(
			"SELECT business_name, logo, phone, email, website, main_address, description, facebook_url, instagram_url, opening_note, status
			 FROM company_settings
			 ORDER BY CASE WHEN status = 'active' THEN 0 ELSE 1 END, id ASC
			 LIMIT 1"
		);
		$row = $statement->fetch();
	} catch (PDOException $exception) {
		error_log('Frontend company settings query failed: ' . $exception->getMessage());
		return $company;
	}

	if (!$row) {
		return $company;
	}

	foreach ($company as $field => $fallback) {
		if (array_key_exists($field, $row) && trim((string) $row[$field]) !== '') {
			$company[$field] = trim((string) $row[$field]);
		}
	}

	return $company;
}

function frontend_company_value(string $field): string
{
	$company = frontend_company_data();

	return (string) ($company[$field] ?? '');
}

function frontend_escape(string $value): string
{
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function frontend_company_field(string $field): string
{
	return frontend_escape(frontend_company_value($field));
}

function frontend_company_url(string $field): string
{
	$url = frontend_company_value($field);

	if ($url === '#' || $url === '') {
		return '#';
	}

	if (!filter_var($url, FILTER_VALIDATE_URL)) {
		return '#';
	}

	$scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

	if (!in_array($scheme, ['http', 'https'], true)) {
		return '#';
	}

	return frontend_escape($url);
}

function frontend_default_service_categories(): array
{
	$categoryNames = [
		'Skin Care',
		'Face Masking',
		'Stream Bath',
		'Facial Therapy',
		'Body Massage',
		'Aroma Therapy',
		'Mineral Baths',
		'Stone Therapy',
	];

	$categories = [];

	foreach ($categoryNames as $index => $categoryName) {
		$categories[] = [
			'id' => 0,
			'category_name' => $categoryName,
			'category_slug' => '',
			'description' => '',
			'display_order' => $index + 1,
		];
	}

	return $categories;
}

function get_frontend_service_categories(): array
{
	static $categories = null;

	if (is_array($categories)) {
		return $categories;
	}

	$categories = frontend_default_service_categories();
	$pdo = ace_admin_db();

	if (!$pdo instanceof PDO) {
		return $categories;
	}

	try {
		$statement = $pdo->prepare(
			'SELECT id, category_name, category_slug, description, display_order
			FROM service_categories
			ORDER BY display_order ASC, id ASC'
		);
		$statement->execute();
		$rows = $statement->fetchAll();
	} catch (PDOException $exception) {
		error_log('Frontend service categories query failed: ' . $exception->getMessage());
		return $categories;
	}

	if (empty($rows)) {
		return $categories;
	}

	$categories = $rows;

	return $categories;
}

function get_frontend_services_by_category(): array
{
	static $servicesByCategory = null;

	if (is_array($servicesByCategory)) {
		return $servicesByCategory;
	}

	$servicesByCategory = [];
	$pdo = ace_admin_db();

	if (!$pdo instanceof PDO) {
		return $servicesByCategory;
	}

	try {
		$statement = $pdo->prepare(
			'SELECT id, service_name, price, service_category_id
			FROM services
			ORDER BY service_category_id ASC, id ASC'
		);
		$statement->execute();
		$rows = $statement->fetchAll();
	} catch (PDOException $exception) {
		error_log('Frontend services query failed: ' . $exception->getMessage());
		return $servicesByCategory;
	}

	foreach ($rows as $row) {
		$categoryId = isset($row['service_category_id']) ? (int) $row['service_category_id'] : 0;

		if ($categoryId <= 0) {
			continue;
		}

		if (!isset($servicesByCategory[$categoryId])) {
			$servicesByCategory[$categoryId] = [];
		}

		$servicesByCategory[$categoryId][] = [
			'id' => isset($row['id']) ? (int) $row['id'] : 0,
			'service_name' => (string) ($row['service_name'] ?? ''),
			'price' => isset($row['price']) ? (float) $row['price'] : 0.0,
			'service_category_id' => $categoryId,
		];
	}

	return $servicesByCategory;
}
