<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

function booking_save_redirect(string $target, string $key, string $message): void
{
	header('Location: ' . $target . '?' . $key . '=' . urlencode($message));
	exit;
}

function booking_save_form_error(string $error, int $id = 0): void
{
	$target = 'booking_form.php';

	if ($id > 0) {
		$target .= '?id=' . $id . '&error=' . urlencode($error);
	} else {
		$target .= '?error=' . urlencode($error);
	}

	header('Location: ' . $target);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	booking_save_redirect('bookings.php', 'error', 'invalid_request');
}

$currentUser = current_user();
$currentRole = $currentUser['role'] ?? '';

if (!in_array($currentRole, ['admin', 'manager'], true)) {
	booking_save_redirect('bookings.php', 'error', 'permission_denied');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$bookingType = trim((string) ($_POST['booking_type'] ?? 'guest'));
$customerId = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
$guestName = trim((string) ($_POST['guest_name'] ?? ''));
$guestPhone = trim((string) ($_POST['guest_phone'] ?? ''));
$guestEmail = trim((string) ($_POST['guest_email'] ?? ''));
$outletId = isset($_POST['outlet_id']) ? (int) $_POST['outlet_id'] : 0;
$serviceId = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;
$employeeId = isset($_POST['employee_id']) ? (int) $_POST['employee_id'] : 0;
$appointmentDate = trim((string) ($_POST['appointment_date'] ?? ''));
$appointmentTime = trim((string) ($_POST['appointment_time'] ?? ''));
$bookingStatus = trim((string) ($_POST['booking_status'] ?? 'pending'));
$paymentMethod = trim((string) ($_POST['payment_method'] ?? 'pay_at_salon'));
$notes = trim((string) ($_POST['notes'] ?? ''));
$allowedTypes = ['registered', 'guest'];
$allowedStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];

if (!in_array($bookingType, $allowedTypes, true) || !in_array($bookingStatus, $allowedStatuses, true)) {
	booking_save_form_error('invalid', $id);
}

if ($bookingType === 'registered' && $customerId <= 0) {
	booking_save_form_error('invalid', $id);
}

if ($bookingType === 'guest' && ($guestName === '' || $guestPhone === '')) {
	booking_save_form_error('invalid', $id);
}

if ($guestEmail !== '' && !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
	booking_save_form_error('invalid_email', $id);
}

if ($outletId <= 0 || $serviceId <= 0 || $appointmentDate === '' || $appointmentTime === '') {
	booking_save_form_error('invalid', $id);
}

if ($paymentMethod === '') {
	$paymentMethod = 'pay_at_salon';
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	booking_save_form_error('database', $id);
}

try {
	if ($id > 0) {
		$existing = $pdo->prepare('SELECT id FROM bookings WHERE id = :id LIMIT 1');
		$existing->execute(['id' => $id]);

		if (!$existing->fetch()) {
			booking_save_redirect('bookings.php', 'error', 'booking_not_found');
		}
	}

	if ($bookingType === 'registered') {
		$customer = $pdo->prepare('SELECT id FROM customers WHERE id = :id LIMIT 1');
		$customer->execute(['id' => $customerId]);

		if (!$customer->fetch()) {
			booking_save_form_error('invalid', $id);
		}
	} else {
		$customerId = 0;
	}

	$outlet = $pdo->prepare('SELECT id FROM branches WHERE id = :id LIMIT 1');
	$outlet->execute(['id' => $outletId]);

	if (!$outlet->fetch()) {
		booking_save_form_error('invalid', $id);
	}

	$service = $pdo->prepare('SELECT id FROM services WHERE id = :id LIMIT 1');
	$service->execute(['id' => $serviceId]);

	if (!$service->fetch()) {
		booking_save_form_error('invalid', $id);
	}

	if ($employeeId > 0) {
		$employee = $pdo->prepare('SELECT id FROM employees WHERE id = :id LIMIT 1');
		$employee->execute(['id' => $employeeId]);

		if (!$employee->fetch()) {
			booking_save_form_error('invalid', $id);
		}
	}

	$bookingData = [
		'booking_type' => $bookingType,
		'customer_id' => $customerId > 0 ? $customerId : null,
		'guest_name' => $guestName !== '' ? $guestName : null,
		'guest_phone' => $guestPhone !== '' ? $guestPhone : null,
		'guest_email' => $guestEmail !== '' ? $guestEmail : null,
		'outlet_id' => $outletId,
		'service_id' => $serviceId,
		'employee_id' => $employeeId > 0 ? $employeeId : null,
		'appointment_date' => $appointmentDate,
		'appointment_time' => $appointmentTime,
		'booking_status' => $bookingStatus,
		'payment_method' => $paymentMethod,
		'notes' => $notes !== '' ? $notes : null,
	];

	if ($id > 0) {
		$statement = $pdo->prepare(
			'UPDATE bookings SET
				booking_type = :booking_type,
				customer_id = :customer_id,
				guest_name = :guest_name,
				guest_phone = :guest_phone,
				guest_email = :guest_email,
				outlet_id = :outlet_id,
				service_id = :service_id,
				employee_id = :employee_id,
				appointment_date = :appointment_date,
				appointment_time = :appointment_time,
				booking_status = :booking_status,
				payment_method = :payment_method,
				notes = :notes
			WHERE id = :id'
		);
		$statement->execute(array_merge($bookingData, ['id' => $id]));

		booking_save_redirect('bookings.php', 'success', 'booking_updated');
	}

	$statement = $pdo->prepare(
		'INSERT INTO bookings
			(booking_type, customer_id, guest_name, guest_phone, guest_email, outlet_id, service_id,
				employee_id, appointment_date, appointment_time, booking_status, payment_method, notes)
		VALUES
			(:booking_type, :customer_id, :guest_name, :guest_phone, :guest_email, :outlet_id, :service_id,
				:employee_id, :appointment_date, :appointment_time, :booking_status, :payment_method, :notes)'
	);
	$statement->execute($bookingData);

	booking_save_redirect('bookings.php', 'success', 'booking_created');
} catch (PDOException $exception) {
	error_log('Booking save failed: ' . $exception->getMessage());
	booking_save_form_error('database', $id);
}
