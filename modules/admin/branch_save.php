<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

function branch_save_redirect(string $target, string $key, string $message): void
{
	header('Location: ' . $target . '?' . $key . '=' . urlencode($message));
	exit;
}

function branch_save_form_error(string $error, int $id = 0): void
{
	$target = 'branch_form.php';

	if ($id > 0) {
		$target .= '?id=' . $id . '&error=' . urlencode($error);
	} else {
		$target .= '?error=' . urlencode($error);
	}

	header('Location: ' . $target);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	branch_save_redirect('branches.php', 'error', 'invalid_request');
}

$currentUser = current_user();

if (($currentUser['role'] ?? '') !== 'admin') {
	branch_save_redirect('branches.php', 'error', 'permission_denied');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$branchName = trim((string) ($_POST['branch_name'] ?? ''));
$branchCode = trim((string) ($_POST['branch_code'] ?? ''));
$fullAddress = trim((string) ($_POST['full_address'] ?? ''));
$areaCity = trim((string) ($_POST['area_city'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$googleMapsLink = trim((string) ($_POST['google_maps_link'] ?? ''));
$openingTime = trim((string) ($_POST['opening_time'] ?? ''));
$closingTime = trim((string) ($_POST['closing_time'] ?? ''));
$weeklyOffDay = trim((string) ($_POST['weekly_off_day'] ?? ''));
$branchManager = trim((string) ($_POST['branch_manager'] ?? ''));
$status = trim((string) ($_POST['status'] ?? 'active'));
$notes = trim((string) ($_POST['notes'] ?? ''));

if ($branchName === '' || $fullAddress === '') {
	branch_save_form_error('invalid', $id);
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
	branch_save_form_error('invalid_email', $id);
}

if (!in_array($status, ['active', 'inactive'], true)) {
	branch_save_form_error('invalid_status', $id);
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	branch_save_form_error('database', $id);
}

$branchData = [
	'branch_name' => $branchName,
	'branch_code' => $branchCode !== '' ? $branchCode : null,
	'full_address' => $fullAddress,
	'area_city' => $areaCity !== '' ? $areaCity : null,
	'phone' => $phone !== '' ? $phone : null,
	'email' => $email !== '' ? $email : null,
	'google_maps_link' => $googleMapsLink !== '' ? $googleMapsLink : null,
	'opening_time' => $openingTime !== '' ? $openingTime : null,
	'closing_time' => $closingTime !== '' ? $closingTime : null,
	'weekly_off_day' => $weeklyOffDay !== '' ? $weeklyOffDay : null,
	'branch_manager' => $branchManager !== '' ? $branchManager : null,
	'status' => $status,
	'notes' => $notes !== '' ? $notes : null,
];

try {
	if ($id > 0) {
		$existing = $pdo->prepare('SELECT id FROM branches WHERE id = :id LIMIT 1');
		$existing->execute(['id' => $id]);

		if (!$existing->fetch()) {
			branch_save_redirect('branches.php', 'error', 'branch_not_found');
		}

		$statement = $pdo->prepare(
			'UPDATE branches SET
				branch_name = :branch_name,
				branch_code = :branch_code,
				full_address = :full_address,
				area_city = :area_city,
				phone = :phone,
				email = :email,
				google_maps_link = :google_maps_link,
				opening_time = :opening_time,
				closing_time = :closing_time,
				weekly_off_day = :weekly_off_day,
				branch_manager = :branch_manager,
				status = :status,
				notes = :notes
			WHERE id = :id'
		);
		$statement->execute(array_merge($branchData, ['id' => $id]));

		branch_save_redirect('branches.php', 'success', 'branch_updated');
	}

	$statement = $pdo->prepare(
		'INSERT INTO branches
			(branch_name, branch_code, full_address, area_city, phone, email, google_maps_link,
				opening_time, closing_time, weekly_off_day, branch_manager, status, notes)
		VALUES
			(:branch_name, :branch_code, :full_address, :area_city, :phone, :email, :google_maps_link,
				:opening_time, :closing_time, :weekly_off_day, :branch_manager, :status, :notes)'
	);
	$statement->execute($branchData);

	branch_save_redirect('branches.php', 'success', 'branch_created');
} catch (PDOException $exception) {
	error_log('Branch save failed: ' . $exception->getMessage());
	branch_save_form_error('database', $id);
}
