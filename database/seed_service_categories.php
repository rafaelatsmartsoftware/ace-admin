<?php
require_once __DIR__ . '/../config/database.php';

function service_category_seed_slug(string $value): string
{
	$slug = strtolower(trim($value));
	$slug = str_replace('&', ' and ', $slug);
	$slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
	$slug = trim((string) $slug, '-');

	return $slug !== '' ? $slug : 'category';
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	echo "Unable to connect to the database.\n";
	exit(1);
}

try {
	$pdo->exec(
		"CREATE TABLE IF NOT EXISTS service_categories (
			id INT AUTO_INCREMENT PRIMARY KEY,
			category_name VARCHAR(150) NOT NULL,
			category_slug VARCHAR(180) NOT NULL UNIQUE,
			description TEXT NULL,
			display_order INT DEFAULT 0,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		)"
	);

	$categories = [
		'Hair Cuts',
		'Hair Color',
		'Facial & Skin Care',
		'Massage & Spa Therapy',
		'Manicure & Pedicure',
		'Bridal Packages',
		'Makeup',
		'Waxing / Threading',
		'Men’s Grooming',
		'Packages / Offers',
	];

	$exists = $pdo->prepare('SELECT id FROM service_categories WHERE category_slug = :category_slug LIMIT 1');
	$insert = $pdo->prepare(
		'INSERT INTO service_categories (category_name, category_slug, display_order)
		VALUES (:category_name, :category_slug, :display_order)'
	);
	$inserted = 0;
	$skipped = 0;

	foreach ($categories as $index => $categoryName) {
		$categorySlug = service_category_seed_slug($categoryName);
		$exists->execute(['category_slug' => $categorySlug]);

		if ($exists->fetch()) {
			$skipped++;
			continue;
		}

		$insert->execute([
			'category_name' => $categoryName,
			'category_slug' => $categorySlug,
			'display_order' => $index + 1,
		]);
		$inserted++;
	}

	echo 'Service categories seed complete. Inserted: ' . $inserted . '. Already existed: ' . $skipped . ".\n";
} catch (PDOException $exception) {
	error_log('Service categories seed failed: ' . $exception->getMessage());
	echo "Unable to seed service categories.\n";
	exit(1);
}
