<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../../../config/database.php';

function redirect_with_error(string $error): void
{
	header('Location: ../login.php?error=' . urlencode($error));
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	redirect_with_error('invalid_request');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
	redirect_with_error('missing_fields');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	redirect_with_error('invalid_email');
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	redirect_with_error('service_unavailable');
}

try {
	$statement = $pdo->prepare(
		'SELECT id, name, email, password, role, status FROM users WHERE email = :email LIMIT 1'
	);
	$statement->execute(['email' => $email]);
	$user = $statement->fetch();
} catch (PDOException $exception) {
	error_log('Login query failed: ' . $exception->getMessage());
	redirect_with_error('service_unavailable');
}

if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password'])) {
	redirect_with_error('invalid_credentials');
}

session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];

header('Location: ../index.php');
exit;
