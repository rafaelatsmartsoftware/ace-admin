<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

function customer_save_redirect(string $target, string $key, string $message): void
{
	header('Location: ' . $target . '?' . $key . '=' . urlencode($message));
	exit;
}

function customer_save_form_error(string $error, int $id = 0): void
{
	$target = 'customer_form.php';

	if ($id > 0) {
		$target .= '?id=' . $id . '&error=' . urlencode($error);
	} else {
		$target .= '?error=' . urlencode($error);
	}

	header('Location: ' . $target);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	customer_save_redirect('customers.php', 'error', 'invalid_request');
}

$currentUser = current_user();

if (($currentUser['role'] ?? '') !== 'admin') {
	customer_save_redirect('customers.php', 'error', 'permission_denied');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$customerName = trim((string) ($_POST['customer_name'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$gender = trim((string) ($_POST['gender'] ?? ''));
$dateOfBirth = trim((string) ($_POST['date_of_birth'] ?? ''));
$address = trim((string) ($_POST['address'] ?? ''));
$notes = trim((string) ($_POST['notes'] ?? ''));

if ($customerName === '' || $phone === '') {
	customer_save_form_error('invalid', $id);
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
	customer_save_form_error('invalid_email', $id);
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	customer_save_form_error('database', $id);
}

try {
	if ($id > 0) {
		$existing = $pdo->prepare('SELECT id FROM customers WHERE id = :id LIMIT 1');
		$existing->execute(['id' => $id]);

		if (!$existing->fetch()) {
			customer_save_redirect('customers.php', 'error', 'customer_not_found');
		}
	}

	$customerData = [
		'customer_name' => $customerName,
		'phone' => $phone,
		'email' => $email !== '' ? $email : null,
		'gender' => $gender !== '' ? $gender : null,
		'date_of_birth' => $dateOfBirth !== '' ? $dateOfBirth : null,
		'address' => $address !== '' ? $address : null,
		'notes' => $notes !== '' ? $notes : null,
	];

	if ($id > 0) {
		$statement = $pdo->prepare(
			'UPDATE customers SET
				customer_name = :customer_name,
				phone = :phone,
				email = :email,
				gender = :gender,
				date_of_birth = :date_of_birth,
				address = :address,
				notes = :notes
			WHERE id = :id'
		);
		$statement->execute(array_merge($customerData, ['id' => $id]));

		customer_save_redirect('customers.php', 'success', 'customer_updated');
	}

	$statement = $pdo->prepare(
		'INSERT INTO customers
			(customer_name, phone, email, gender, date_of_birth, address, notes)
		VALUES
			(:customer_name, :phone, :email, :gender, :date_of_birth, :address, :notes)'
	);
	$statement->execute($customerData);

	customer_save_redirect('customers.php', 'success', 'customer_created');
} catch (PDOException $exception) {
	error_log('Customer save failed: ' . $exception->getMessage());
	customer_save_form_error('database', $id);
}
