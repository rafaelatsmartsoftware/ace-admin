<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

function employee_save_redirect(string $target, string $key, string $message): void
{
	header('Location: ' . $target . '?' . $key . '=' . urlencode($message));
	exit;
}

function employee_save_form_error(string $error, int $id = 0): void
{
	$target = 'employee_form.php';

	if ($id > 0) {
		$target .= '?id=' . $id . '&error=' . urlencode($error);
	} else {
		$target .= '?error=' . urlencode($error);
	}

	header('Location: ' . $target);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	employee_save_redirect('employees.php', 'error', 'invalid_request');
}

$currentUser = current_user();

if (($currentUser['role'] ?? '') !== 'admin') {
	employee_save_redirect('employees.php', 'error', 'permission_denied');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$outletId = isset($_POST['outlet_id']) ? (int) $_POST['outlet_id'] : 0;
$employeeName = trim((string) ($_POST['employee_name'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$jobTitle = trim((string) ($_POST['job_title'] ?? ''));
$specialties = trim((string) ($_POST['specialties'] ?? ''));
$joiningDate = trim((string) ($_POST['joining_date'] ?? ''));
$notes = trim((string) ($_POST['notes'] ?? ''));

if ($employeeName === '' || $outletId <= 0) {
	employee_save_form_error('invalid', $id);
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
	employee_save_form_error('invalid_email', $id);
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	employee_save_form_error('database', $id);
}

try {
	if ($id > 0) {
		$existing = $pdo->prepare('SELECT id FROM employees WHERE id = :id LIMIT 1');
		$existing->execute(['id' => $id]);

		if (!$existing->fetch()) {
			employee_save_redirect('employees.php', 'error', 'employee_not_found');
		}
	}

	$outlet = $pdo->prepare('SELECT id FROM branches WHERE id = :id LIMIT 1');
	$outlet->execute(['id' => $outletId]);

	if (!$outlet->fetch()) {
		employee_save_form_error('invalid', $id);
	}

	$employeeData = [
		'outlet_id' => $outletId,
		'employee_name' => $employeeName,
		'phone' => $phone !== '' ? $phone : null,
		'email' => $email !== '' ? $email : null,
		'job_title' => $jobTitle !== '' ? $jobTitle : null,
		'specialties' => $specialties !== '' ? $specialties : null,
		'joining_date' => $joiningDate !== '' ? $joiningDate : null,
		'notes' => $notes !== '' ? $notes : null,
	];

	if ($id > 0) {
		$statement = $pdo->prepare(
			'UPDATE employees SET
				outlet_id = :outlet_id,
				employee_name = :employee_name,
				phone = :phone,
				email = :email,
				job_title = :job_title,
				specialties = :specialties,
				joining_date = :joining_date,
				notes = :notes
			WHERE id = :id'
		);
		$statement->execute(array_merge($employeeData, ['id' => $id]));

		employee_save_redirect('employees.php', 'success', 'employee_updated');
	}

	$statement = $pdo->prepare(
		'INSERT INTO employees
			(outlet_id, employee_name, phone, email, job_title, specialties, joining_date, notes)
		VALUES
			(:outlet_id, :employee_name, :phone, :email, :job_title, :specialties, :joining_date, :notes)'
	);
	$statement->execute($employeeData);

	employee_save_redirect('employees.php', 'success', 'employee_created');
} catch (PDOException $exception) {
	error_log('Employee save failed: ' . $exception->getMessage());
	employee_save_form_error('database', $id);
}
