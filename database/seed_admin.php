<?php
require_once __DIR__ . '/../config/database.php';

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	echo "Database connection failed. Import database/schema.sql first and check config/database.php.\n";
	exit(1);
}

$email = 'admin@example.com';

try {
	$statement = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
	$statement->execute(['email' => $email]);

	if ($statement->fetch()) {
		echo "Admin user already exists.\n";
		exit(0);
	}

	$insert = $pdo->prepare(
		'INSERT INTO users (name, email, password, role, status) VALUES (:name, :email, :password, :role, :status)'
	);

	$insert->execute([
		'name' => 'Admin User',
		'email' => $email,
		'password' => password_hash('admin12345', PASSWORD_DEFAULT),
		'role' => 'admin',
		'status' => 'active',
	]);

	echo "Demo admin user created successfully.\n";
} catch (PDOException $exception) {
	error_log('Admin seed failed: ' . $exception->getMessage());
	echo "Admin seed failed. Check database/schema.sql and config/database.php.\n";
	exit(1);
}
