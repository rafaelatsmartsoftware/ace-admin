<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

function is_logged_in(): bool
{
	return !empty($_SESSION['user_id']);
}

function current_user(): ?array
{
	if (!is_logged_in()) {
		return null;
	}

	return [
		'id' => $_SESSION['user_id'] ?? null,
		'name' => $_SESSION['user_name'] ?? '',
		'email' => $_SESSION['user_email'] ?? '',
		'role' => $_SESSION['user_role'] ?? '',
	];
}

function require_login(string $redirectTo = 'login.php'): void
{
	if (is_logged_in()) {
		return;
	}

	header('Location: ' . $redirectTo);
	exit;
}
