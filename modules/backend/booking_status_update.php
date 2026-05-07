<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

function booking_status_allowed_transitions(string $status): array
{
	return [
		'pending' => ['confirmed', 'cancelled'],
		'confirmed' => ['completed', 'cancelled'],
		'completed' => [],
		'cancelled' => [],
	][$status] ?? [];
}

function booking_status_redirect(string $key, string $message, array $filters = []): void
{
	$query = [$key => $message];

	if (($filters['search'] ?? '') !== '') {
		$query['search'] = $filters['search'];
	}

	if (($filters['status'] ?? '') !== '') {
		$query['status'] = $filters['status'];
	}

	if (($filters['outlet_id'] ?? 0) > 0) {
		$query['outlet_id'] = (int) $filters['outlet_id'];
	}

	header('Location: bookings.php?' . http_build_query($query));
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	booking_status_redirect('error', 'invalid_request');
}

$currentUser = current_user();
$currentRole = $currentUser['role'] ?? '';

if (!in_array($currentRole, ['admin', 'manager'], true)) {
	booking_status_redirect('error', 'permission_denied');
}

$filters = [
	'search' => trim((string) ($_POST['return_search'] ?? '')),
	'status' => trim((string) ($_POST['return_status'] ?? '')),
	'outlet_id' => isset($_POST['return_outlet_id']) ? (int) $_POST['return_outlet_id'] : 0,
];

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$requestedStatus = trim((string) ($_POST['status'] ?? ''));
$allowedStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];

if ($id <= 0 || !in_array($requestedStatus, $allowedStatuses, true)) {
	booking_status_redirect('error', 'invalid_status', $filters);
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	booking_status_redirect('error', 'database', $filters);
}

try {
	$bookingStatement = $pdo->prepare(
		'SELECT id, booking_status
		FROM bookings
		WHERE id = :id
		LIMIT 1'
	);
	$bookingStatement->execute(['id' => $id]);
	$booking = $bookingStatement->fetch();

	if (!$booking) {
		booking_status_redirect('error', 'booking_not_found', $filters);
	}

	$currentStatus = (string) ($booking['booking_status'] ?? '');
	$allowedTransitions = booking_status_allowed_transitions($currentStatus);

	if (!in_array($requestedStatus, $allowedTransitions, true)) {
		booking_status_redirect('error', 'status_transition_not_allowed', $filters);
	}

	$updateStatement = $pdo->prepare(
		'UPDATE bookings
		SET booking_status = :booking_status
		WHERE id = :id'
	);
	$updateStatement->execute([
		'booking_status' => $requestedStatus,
		'id' => $id,
	]);

	booking_status_redirect('success', 'booking_status_updated', $filters);
} catch (PDOException $exception) {
	error_log('Booking status update failed: ' . $exception->getMessage());
	booking_status_redirect('error', 'database', $filters);
}
