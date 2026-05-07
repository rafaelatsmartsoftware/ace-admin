<?php
require_once __DIR__ . '/../config/database.php';

$servicesByCategory = [
	'Hair Cuts' => [
		'Ladies Plain Cut',
		'U Cut',
		'V Cut',
		'Layer Cut',
		'Step Cut',
		'Feather Cut',
		'Bob Cut',
		'Long Layer Cut',
		'Front Layer Cut',
		'Bangs / Front Fringe Cut',
		'Hair Trim',
		'Split Ends Trim',
		'Baby Hair Cut',
		'Hair Wash & Blow Dry',
		'Hair Styling Cut',
	],
	'Hair Color' => [
		'Global Hair Color',
		'Root Touch Up',
		'Hair Highlights',
		'Foil Highlights',
		'Balayage',
		'Ombre Color',
		'Fashion Color',
		'Hair Streaks',
		'Grey Coverage',
		'Base Color',
		'Full Head Color',
		'Half Head Highlights',
		'Crown Highlights',
		'Color Correction',
		'Hair Gloss / Toner',
	],
	'Facial & Skin Care' => [
		'Basic Cleansing Facial',
		'Herbal Facial',
		'Fruit Facial',
		'Gold Facial',
		'Pearl Facial',
		'Diamond Facial',
		'Whitening Facial',
		'Brightening Facial',
		'Anti-Acne Facial',
		'Anti-Aging Facial',
		'Oily Skin Facial',
		'Dry Skin Facial',
		'Skin Polish',
		'Fair Polish',
		'Face Cleanup',
	],
	'Massage & Spa Therapy' => [
		'Head Massage',
		'Hair Oil Massage',
		'Hot Oil Massage',
		'Hot Oil Massage with Wash',
		'Neck & Shoulder Massage',
		'Back Massage',
		'Foot Massage',
		'Full Body Relaxing Massage',
		'Aromatherapy Massage',
		'Deep Tissue Massage',
		'Body Scrub',
		'Body Polish',
		'Steam Bath',
		'Spa Therapy Package',
		'Relaxation Spa Package',
	],
	'Manicure & Pedicure' => [
		'Basic Manicure',
		'Spa Manicure',
		'Luxury Manicure',
		'French Manicure',
		'Nail Polish Change',
		'Gel Polish',
		'Nail Art',
		'Basic Pedicure',
		'Spa Pedicure',
		'Luxury Pedicure',
		'French Pedicure',
		'Foot Scrub',
		'Foot Spa',
		'Heel Treatment',
		'Hand & Foot Care Combo',
	],
	'Bridal Packages' => [
		'Bridal Makeup Package',
		'Bridal Makeup with Hair Styling',
		'Bridal Makeup with Saree Draping',
		'Full Bridal Makeover',
		'Engagement Makeup Package',
		'Holud Makeup Package',
		'Mehendi Bridal Package',
		'Reception Makeup Package',
		'Walima Makeup Package',
		'Bridal Trial Makeup',
		'Bridal Hair Styling',
		'Bridal Saree Draping',
		'Bridal Skin Preparation Package',
		'Premium Bridal Package',
		'Home Bridal Service Package',
	],
	'Makeup' => [
		'Party Makeup',
		'Party Makeup with Hair Styling',
		'Party Makeup with Saree Draping',
		'Simple Day Makeup',
		'Evening Makeup',
		'HD Makeup',
		'Glam Makeup',
		'Natural Makeup',
		'Office Makeup',
		'Photoshoot Makeup',
		'Engagement Guest Makeup',
		'Holud Guest Makeup',
		'Baby Makeup',
		'Hair Styling',
		'Saree Draping',
	],
	'Waxing / Threading' => [
		'Eyebrow Threading',
		'Upper Lip Threading',
		'Chin Threading',
		'Forehead Threading',
		'Full Face Threading',
		'Side Face Threading',
		'Upper Lip Wax',
		'Chin Wax',
		'Underarm Waxing',
		'Half Hand Waxing',
		'Full Hand Waxing',
		'Half Leg Waxing',
		'Full Leg Waxing',
		'Full Body Waxing',
		'Bikini Line Waxing',
	],
	'Men’s Grooming' => [
		'Men’s Hair Cut',
		'Beard Trim',
		'Beard Styling',
		'Hair Wash',
		'Hair Styling',
		'Men’s Facial',
		'Men’s Cleanup',
		'Men’s Hair Color',
		'Grey Coverage for Men',
		'Head Massage',
		'Face Massage',
		'Manicure for Men',
		'Pedicure for Men',
		'Men’s Threading',
		'Men’s Grooming Package',
	],
	'Packages / Offers' => [
		'Regular Beauty Combo',
		'Facial + Threading Combo',
		'Hair Cut + Blow Dry Combo',
		'Manicure + Pedicure Combo',
		'Waxing Combo',
		'Party Makeup Combo',
		'Hair Spa Combo',
		'Skin Care Combo',
		'Bridal Pre-Care Combo',
		'Monthly Beauty Package',
		'Student Beauty Package',
		'Eid Special Package',
		'Puja Special Package',
		'Wedding Guest Package',
		'Premium Spa Package',
	],
];

function seed_services_slug(string $value): string
{
	$value = str_replace(['&', '+'], [' and ', ' plus '], $value);
	$value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
	$value = strtolower(trim((string) $value));
	$value = preg_replace('/[^a-z0-9]+/', '-', $value);
	$value = trim((string) $value, '-');

	return $value !== '' ? $value : 'service';
}

function seed_services_details(string $categoryName, string $serviceName): array
{
	$base = [
		'Hair Cuts' => [45, 500],
		'Hair Color' => [120, 2500],
		'Facial & Skin Care' => [75, 1500],
		'Massage & Spa Therapy' => [60, 1800],
		'Manicure & Pedicure' => [50, 900],
		'Bridal Packages' => [180, 10000],
		'Makeup' => [90, 2500],
		'Waxing / Threading' => [30, 500],
		'Men’s Grooming' => [40, 700],
		'Packages / Offers' => [120, 3500],
	];

	[$duration, $price] = $base[$categoryName] ?? [60, 1000];

	if (stripos($serviceName, 'package') !== false || stripos($serviceName, 'combo') !== false) {
		$duration += 60;
		$price += 2500;
	}

	if (stripos($serviceName, 'bridal') !== false) {
		$duration += 90;
		$price += 8000;
	}

	if (stripos($serviceName, 'full body') !== false || stripos($serviceName, 'full bridal') !== false || stripos($serviceName, 'premium') !== false) {
		$duration += 60;
		$price += 4000;
	}

	if (stripos($serviceName, 'basic') !== false || stripos($serviceName, 'trim') !== false || stripos($serviceName, 'threading') !== false) {
		$duration = max(15, $duration - 15);
		$price = max(150, $price - 300);
	}

	return [
		'description' => $serviceName . ' service for Bangladesh salon and beauty parlour customers.',
		'duration_minutes' => $duration,
		'price' => number_format((float) $price, 2, '.', ''),
	];
}

function seed_services_redirectless_exit(string $message, int $code = 1): void
{
	fwrite($code === 0 ? STDOUT : STDERR, $message . PHP_EOL);
	exit($code);
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	seed_services_redirectless_exit('Database connection failed. Check config/database.php.');
}

try {
	$serviceColumns = $pdo->query('DESCRIBE services')->fetchAll();
	$outletIsNullable = false;
	$hasOutletId = false;

	foreach ($serviceColumns as $column) {
		if (($column['Field'] ?? '') === 'outlet_id') {
			$hasOutletId = true;
			$outletIsNullable = strtoupper((string) ($column['Null'] ?? '')) === 'YES';
			break;
		}
	}

	$outletId = null;

	if ($hasOutletId && !$outletIsNullable) {
		$outletId = $pdo->query('SELECT id FROM branches ORDER BY id ASC LIMIT 1')->fetchColumn();

		if (!$outletId) {
			seed_services_redirectless_exit('services.outlet_id is required, but no branch exists. Add a branch before seeding services.');
		}
	}

	$categoryStatement = $pdo->query('SELECT id, category_name, category_slug FROM service_categories');
	$categoriesByName = [];

	foreach ($categoryStatement->fetchAll() as $category) {
		$categoriesByName[(string) $category['category_name']] = $category;
	}

	$duplicateStatement = $pdo->prepare(
		'SELECT id FROM services WHERE service_category_id = :service_category_id AND service_name = :service_name LIMIT 1'
	);
	$slugStatement = $pdo->prepare('SELECT id FROM services WHERE service_slug = :service_slug LIMIT 1');
	$insertStatement = $pdo->prepare(
		'INSERT INTO services
			(service_category_id, outlet_id, service_name, service_slug, description, duration_minutes, price)
		VALUES
			(:service_category_id, :outlet_id, :service_name, :service_slug, :description, :duration_minutes, :price)'
	);

	$inserted = 0;
	$skippedDuplicates = 0;
	$missingCategories = [];

	$pdo->beginTransaction();

	foreach ($servicesByCategory as $categoryName => $serviceNames) {
		if (!isset($categoriesByName[$categoryName])) {
			$missingCategories[] = $categoryName;
			continue;
		}

		$category = $categoriesByName[$categoryName];
		$categoryId = (int) $category['id'];
		$categorySlug = (string) ($category['category_slug'] ?? seed_services_slug($categoryName));

		foreach ($serviceNames as $serviceName) {
			$duplicateStatement->execute([
				'service_category_id' => $categoryId,
				'service_name' => $serviceName,
			]);

			if ($duplicateStatement->fetch()) {
				$skippedDuplicates++;
				continue;
			}

			$baseSlug = seed_services_slug($serviceName);
			$serviceSlug = $baseSlug;
			$suffix = 2;

			while (true) {
				$slugStatement->execute(['service_slug' => $serviceSlug]);

				if (!$slugStatement->fetch()) {
					break;
				}

				$serviceSlug = $baseSlug . '-' . seed_services_slug($categorySlug);

				if ($suffix > 2) {
					$serviceSlug .= '-' . $suffix;
				}

				$suffix++;
			}

			$details = seed_services_details($categoryName, $serviceName);

			$insertStatement->execute([
				'service_category_id' => $categoryId,
				'outlet_id' => $outletIsNullable ? null : (int) $outletId,
				'service_name' => $serviceName,
				'service_slug' => $serviceSlug,
				'description' => $details['description'],
				'duration_minutes' => $details['duration_minutes'],
				'price' => $details['price'],
			]);

			$inserted++;
		}
	}

	$pdo->commit();

	echo 'Services seed completed.' . PHP_EOL;
	echo 'Inserted: ' . $inserted . PHP_EOL;
	echo 'Skipped duplicates: ' . $skippedDuplicates . PHP_EOL;
	echo 'Missing categories: ' . count($missingCategories) . PHP_EOL;

	if (!empty($missingCategories)) {
		echo 'Missing category names: ' . implode(', ', $missingCategories) . PHP_EOL;
	}

	if ($hasOutletId) {
		echo 'Outlet handling: ' . ($outletIsNullable ? 'inserted NULL outlet_id' : 'used branch/outlet ID ' . (int) $outletId) . PHP_EOL;
	}
} catch (Throwable $exception) {
	if ($pdo->inTransaction()) {
		$pdo->rollBack();
	}

	error_log('Services seed failed: ' . $exception->getMessage());
	seed_services_redirectless_exit('Services seed failed. Check the PHP error log for details.');
}
