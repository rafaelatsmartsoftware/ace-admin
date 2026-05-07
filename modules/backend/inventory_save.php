<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

function inventory_save_redirect(string $target, string $key, string $message): void
{
	header('Location: ' . $target . '?' . $key . '=' . urlencode($message));
	exit;
}

function inventory_save_form_error(string $error, int $id = 0): void
{
	$target = 'inventory_form.php';

	if ($id > 0) {
		$target .= '?id=' . $id . '&error=' . urlencode($error);
	} else {
		$target .= '?error=' . urlencode($error);
	}

	header('Location: ' . $target);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	inventory_save_redirect('inventory.php', 'error', 'invalid_request');
}

$currentUser = current_user();
$currentRole = $currentUser['role'] ?? '';

if (!in_array($currentRole, ['admin', 'manager'], true)) {
	inventory_save_redirect('inventory.php', 'error', 'permission_denied');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$outletId = isset($_POST['outlet_id']) ? (int) $_POST['outlet_id'] : 0;
$itemName = trim((string) ($_POST['item_name'] ?? ''));
$itemCategory = trim((string) ($_POST['item_category'] ?? ''));
$quantityValue = trim((string) ($_POST['quantity'] ?? ''));
$unit = trim((string) ($_POST['unit'] ?? ''));
$itemCondition = trim((string) ($_POST['item_condition'] ?? ''));
$purchaseDate = trim((string) ($_POST['purchase_date'] ?? ''));
$notes = trim((string) ($_POST['notes'] ?? ''));

if ($outletId <= 0 || $itemName === '' || $quantityValue === '') {
	inventory_save_form_error('invalid', $id);
}

$quantity = filter_var($quantityValue, FILTER_VALIDATE_INT, [
	'options' => [
		'min_range' => 0,
	],
]);

if ($quantity === false) {
	inventory_save_form_error('invalid', $id);
}

if ($purchaseDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $purchaseDate)) {
	inventory_save_form_error('invalid_date', $id);
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	inventory_save_form_error('database', $id);
}

try {
	if ($id > 0) {
		$existing = $pdo->prepare('SELECT id FROM inventory_items WHERE id = :id LIMIT 1');
		$existing->execute(['id' => $id]);

		if (!$existing->fetch()) {
			inventory_save_redirect('inventory.php', 'error', 'inventory_not_found');
		}
	}

	$outlet = $pdo->prepare('SELECT id FROM branches WHERE id = :id LIMIT 1');
	$outlet->execute(['id' => $outletId]);

	if (!$outlet->fetch()) {
		inventory_save_form_error('invalid', $id);
	}

	$itemData = [
		'outlet_id' => $outletId,
		'item_name' => $itemName,
		'item_category' => $itemCategory !== '' ? $itemCategory : null,
		'quantity' => $quantity,
		'unit' => $unit !== '' ? $unit : null,
		'item_condition' => $itemCondition !== '' ? $itemCondition : null,
		'purchase_date' => $purchaseDate !== '' ? $purchaseDate : null,
		'notes' => $notes !== '' ? $notes : null,
	];

	if ($id > 0) {
		$statement = $pdo->prepare(
			'UPDATE inventory_items SET
				outlet_id = :outlet_id,
				item_name = :item_name,
				item_category = :item_category,
				quantity = :quantity,
				unit = :unit,
				item_condition = :item_condition,
				purchase_date = :purchase_date,
				notes = :notes
			WHERE id = :id'
		);
		$statement->execute(array_merge($itemData, ['id' => $id]));

		inventory_save_redirect('inventory.php', 'success', 'inventory_updated');
	}

	$statement = $pdo->prepare(
		'INSERT INTO inventory_items
			(outlet_id, item_name, item_category, quantity, unit, item_condition, purchase_date, notes)
		VALUES
			(:outlet_id, :item_name, :item_category, :quantity, :unit, :item_condition, :purchase_date, :notes)'
	);
	$statement->execute($itemData);

	inventory_save_redirect('inventory.php', 'success', 'inventory_created');
} catch (PDOException $exception) {
	error_log('Inventory save failed: ' . $exception->getMessage());
	inventory_save_form_error('database', $id);
}
