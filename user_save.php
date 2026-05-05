<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

function user_save_redirect(string $target, string $key, string $message): void
{
	header('Location: ' . $target . '?' . $key . '=' . urlencode($message));
	exit;
}

function user_save_form_error(string $error, int $id = 0): void
{
	$target = 'user_form.php';

	if ($id > 0) {
		$target .= '?id=' . $id . '&error=' . urlencode($error);
	} else {
		$target .= '?error=' . urlencode($error);
	}

	header('Location: ' . $target);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	user_save_redirect('users.php', 'error', 'invalid_request');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? '');
$status = trim($_POST['status'] ?? '');
$password = $_POST['password'] ?? '';
$allowedRoles = ['admin', 'manager', 'user'];
$allowedStatuses = ['active', 'inactive'];

if (
	$name === ''
	|| $email === ''
	|| !in_array($role, $allowedRoles, true)
	|| !in_array($status, $allowedStatuses, true)
	|| ($id <= 0 && $password === '')
) {
	user_save_form_error('invalid', $id);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	user_save_form_error('invalid_email', $id);
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	user_save_form_error('database', $id);
}

try {
	$existingUser = null;

	if ($id > 0) {
		$existing = $pdo->prepare('SELECT id, role, status FROM users WHERE id = :id LIMIT 1');
		$existing->execute(['id' => $id]);
		$existingUser = $existing->fetch();

		if (!$existingUser) {
			user_save_redirect('users.php', 'error', 'user_not_found');
		}

		if (!can_edit_user(current_user(), $existingUser)) {
			user_save_redirect('users.php', 'error', 'permission_denied');
		}

		if ((string) $existingUser['role'] !== $role && !can_create_user_role(current_user(), $role)) {
			user_save_form_error('permission_denied', $id);
		}

		if ((string) $existingUser['status'] !== $status && !can_manage_user_status(current_user(), $existingUser)) {
			user_save_form_error('permission_denied', $id);
		}
	} elseif (!can_create_user_role(current_user(), $role)) {
		user_save_redirect('users.php', 'error', 'permission_denied');
	}

	$duplicate = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
	$duplicate->execute([
		'email' => $email,
		'id' => $id,
	]);

	if ($duplicate->fetch()) {
		user_save_form_error('duplicate_email', $id);
	}

	if ($id > 0) {
		if ($password !== '') {
			$statement = $pdo->prepare(
				'UPDATE users SET name = :name, email = :email, role = :role, status = :status, password = :password WHERE id = :id'
			);
			$statement->execute([
				'name' => $name,
				'email' => $email,
				'role' => $role,
				'status' => $status,
				'password' => password_hash($password, PASSWORD_DEFAULT),
				'id' => $id,
			]);
		} else {
			$statement = $pdo->prepare(
				'UPDATE users SET name = :name, email = :email, role = :role, status = :status WHERE id = :id'
			);
			$statement->execute([
				'name' => $name,
				'email' => $email,
				'role' => $role,
				'status' => $status,
				'id' => $id,
			]);
		}

		user_save_redirect('users.php', 'success', 'user_updated');
	}

	$statement = $pdo->prepare(
		'INSERT INTO users (name, email, password, role, status) VALUES (:name, :email, :password, :role, :status)'
	);
	$statement->execute([
		'name' => $name,
		'email' => $email,
		'password' => password_hash($password, PASSWORD_DEFAULT),
		'role' => $role,
		'status' => $status,
	]);

	user_save_redirect('users.php', 'success', 'user_created');
} catch (PDOException $exception) {
	error_log('User save failed: ' . $exception->getMessage());

	if ($exception->getCode() === '23000') {
		user_save_form_error('duplicate_email', $id);
	}

	user_save_form_error('database', $id);
}
