<?php

$databaseConfig = [
	'host' => 'localhost',
	'database' => 'ace_admin',
	'username' => 'ace_user',
	'password' => 'ace_password_123',
	'charset' => 'utf8mb4',
];

function ace_admin_db(): ?PDO
{
	global $databaseConfig;

	static $pdo = null;
	static $attempted = false;

	if ($pdo instanceof PDO) {
		return $pdo;
	}

	if ($attempted) {
		return null;
	}

	$attempted = true;

	$dsn = sprintf(
		'mysql:host=%s;dbname=%s;charset=%s',
		$databaseConfig['host'],
		$databaseConfig['database'],
		$databaseConfig['charset']
	);

	try {
		$pdo = new PDO($dsn, $databaseConfig['username'], $databaseConfig['password'], [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES => false,
		]);

		return $pdo;
	} catch (PDOException $exception) {
		error_log('Database connection failed: ' . $exception->getMessage());
		return null;
	}
}
