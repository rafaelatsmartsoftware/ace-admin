<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

function payment_save_redirect(string $target, string $key, string $message): void
{
	header('Location: ' . $target . '?' . $key . '=' . urlencode($message));
	exit;
}

function payment_save_form_error(string $error, int $id = 0): void
{
	$target = 'payment_form.php';

	if ($id > 0) {
		$target .= '?id=' . $id . '&error=' . urlencode($error);
	} else {
		$target .= '?error=' . urlencode($error);
	}

	header('Location: ' . $target);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	payment_save_redirect('payments.php', 'error', 'invalid_request');
}

$currentUser = current_user();
$currentRole = $currentUser['role'] ?? '';

if (!in_array($currentRole, ['admin', 'manager'], true)) {
	payment_save_redirect('payments.php', 'error', 'permission_denied');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$bookingId = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;
$invoiceNumber = trim((string) ($_POST['invoice_number'] ?? ''));
$totalValue = trim((string) ($_POST['total_amount'] ?? ''));
$discountValue = trim((string) ($_POST['discount_amount'] ?? '0'));
$paidValue = trim((string) ($_POST['paid_amount'] ?? '0'));
$paymentMethod = trim((string) ($_POST['payment_method'] ?? 'pay_at_salon'));
$paymentDate = trim((string) ($_POST['payment_date'] ?? ''));
$notes = trim((string) ($_POST['notes'] ?? ''));

if ($bookingId <= 0 || $invoiceNumber === '' || $totalValue === '' || !is_numeric($totalValue) || !is_numeric($discountValue) || !is_numeric($paidValue)) {
	payment_save_form_error('invalid', $id);
}

$totalAmount = (float) $totalValue;
$discountAmount = (float) $discountValue;
$paidAmount = (float) $paidValue;

if ($totalAmount < 0 || $discountAmount < 0 || $paidAmount < 0) {
	payment_save_form_error('invalid', $id);
}

$dueAmount = max($totalAmount - $discountAmount - $paidAmount, 0);

if ($paidAmount <= 0) {
	$paymentStatus = 'unpaid';
} elseif ($dueAmount > 0) {
	$paymentStatus = 'partial';
} else {
	$paymentStatus = 'paid';
}

if ($paymentMethod === '') {
	$paymentMethod = 'pay_at_salon';
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	payment_save_form_error('database', $id);
}

try {
	if ($id > 0) {
		$existing = $pdo->prepare('SELECT id FROM payments WHERE id = :id LIMIT 1');
		$existing->execute(['id' => $id]);

		if (!$existing->fetch()) {
			payment_save_redirect('payments.php', 'error', 'payment_not_found');
		}
	}

	$booking = $pdo->prepare('SELECT id FROM bookings WHERE id = :id LIMIT 1');
	$booking->execute(['id' => $bookingId]);

	if (!$booking->fetch()) {
		payment_save_form_error('invalid', $id);
	}

	$duplicate = $pdo->prepare('SELECT id FROM payments WHERE invoice_number = :invoice_number AND id <> :id LIMIT 1');
	$duplicate->execute([
		'invoice_number' => $invoiceNumber,
		'id' => $id,
	]);

	if ($duplicate->fetch()) {
		payment_save_form_error('duplicate_invoice', $id);
	}

	$paymentData = [
		'booking_id' => $bookingId,
		'invoice_number' => $invoiceNumber,
		'total_amount' => number_format($totalAmount, 2, '.', ''),
		'discount_amount' => number_format($discountAmount, 2, '.', ''),
		'paid_amount' => number_format($paidAmount, 2, '.', ''),
		'due_amount' => number_format($dueAmount, 2, '.', ''),
		'payment_status' => $paymentStatus,
		'payment_method' => $paymentMethod,
		'payment_date' => $paymentDate !== '' ? $paymentDate : null,
		'notes' => $notes !== '' ? $notes : null,
	];

	if ($id > 0) {
		$statement = $pdo->prepare(
			'UPDATE payments SET
				booking_id = :booking_id,
				invoice_number = :invoice_number,
				total_amount = :total_amount,
				discount_amount = :discount_amount,
				paid_amount = :paid_amount,
				due_amount = :due_amount,
				payment_status = :payment_status,
				payment_method = :payment_method,
				payment_date = :payment_date,
				notes = :notes
			WHERE id = :id'
		);
		$statement->execute(array_merge($paymentData, ['id' => $id]));

		payment_save_redirect('payments.php', 'success', 'payment_updated');
	}

	$statement = $pdo->prepare(
		'INSERT INTO payments
			(booking_id, invoice_number, total_amount, discount_amount, paid_amount, due_amount,
				payment_status, payment_method, payment_date, notes)
		VALUES
			(:booking_id, :invoice_number, :total_amount, :discount_amount, :paid_amount, :due_amount,
				:payment_status, :payment_method, :payment_date, :notes)'
	);
	$statement->execute($paymentData);

	payment_save_redirect('payments.php', 'success', 'payment_created');
} catch (PDOException $exception) {
	error_log('Payment save failed: ' . $exception->getMessage());

	if ($exception->getCode() === '23000') {
		payment_save_form_error('duplicate_invoice', $id);
	}

	payment_save_form_error('database', $id);
}
