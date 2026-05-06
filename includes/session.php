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

function user_role_level(?string $role): int
{
	$levels = [
		'admin' => 2,
		'manager' => 1,
	];

	return $levels[$role ?? ''] ?? 0;
}

function available_user_roles_for(?array $currentUser): array
{
	$role = $currentUser['role'] ?? '';

	if ($role === 'admin') {
		return ['admin', 'manager'];
	}

	return [];
}

function can_create_users(?array $currentUser): bool
{
	return !empty(available_user_roles_for($currentUser));
}

function can_create_user_role(?array $currentUser, ?string $role): bool
{
	return in_array($role, available_user_roles_for($currentUser), true);
}

function can_edit_user(?array $currentUser, array $targetUser): bool
{
	if (!$currentUser) {
		return false;
	}

	$currentRole = $currentUser['role'] ?? '';
	$currentUserId = (int) ($currentUser['id'] ?? 0);
	$targetUserId = (int) ($targetUser['id'] ?? 0);
	$targetRole = $targetUser['role'] ?? '';

	if ($currentRole === 'admin') {
		return true;
	}

	if ($currentUserId <= 0 || $targetUserId <= 0) {
		return false;
	}

	if ($currentRole === 'manager') {
		return $currentUserId === $targetUserId;
	}

	return false;
}

function can_manage_user_status(?array $currentUser, array $targetUser): bool
{
	if (!$currentUser) {
		return false;
	}

	$currentUserId = (int) ($currentUser['id'] ?? 0);
	$targetUserId = (int) ($targetUser['id'] ?? 0);

	if ($currentUserId <= 0 || $targetUserId <= 0 || $currentUserId === $targetUserId) {
		return false;
	}

	return ($currentUser['role'] ?? '') === 'admin' && ($targetUser['role'] ?? '') === 'manager';
}

function require_login(string $redirectTo = 'login.php'): void
{
	if (is_logged_in()) {
		return;
	}

	header('Location: ' . $redirectTo);
	exit;
}
