<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Inventory Form - Ace Admin';
$pageDescription = 'add or edit inventory item';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$currentUser = current_user();
$currentRole = $currentUser['role'] ?? '';
$canManageInventory = in_array($currentRole, ['admin', 'manager'], true);

if (!$canManageInventory) {
	header('Location: inventory.php?error=permission_denied');
	exit;
}

$itemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $itemId > 0;
$inventoryItem = [
	'id' => 0,
	'outlet_id' => 0,
	'item_name' => '',
	'item_category' => '',
	'quantity' => '0',
	'unit' => '',
	'item_condition' => '',
	'purchase_date' => '',
	'notes' => '',
];
$outlets = [];
$loadError = '';
$formErrors = [
	'invalid' => 'Please complete the required fields.',
	'invalid_date' => 'Please enter a valid purchase date.',
	'inventory_not_found' => 'Inventory item not found.',
	'database' => 'Unable to save the inventory item. Please try again.',
	'permission_denied' => 'You do not have permission to manage inventory.',
];
$formError = $formErrors[$_GET['error'] ?? ''] ?? '';
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$loadError = 'Unable to load the inventory form right now. Please check the database connection.';
} else {
	try {
		$outletStatement = $pdo->query(
			'SELECT id, branch_name FROM branches ORDER BY branch_name ASC'
		);
		$outlets = $outletStatement->fetchAll();

		if ($isEdit) {
			$statement = $pdo->prepare(
				'SELECT id, outlet_id, item_name, item_category, quantity, unit, item_condition, purchase_date, notes
				FROM inventory_items
				WHERE id = :id
				LIMIT 1'
			);
			$statement->execute(['id' => $itemId]);
			$loadedItem = $statement->fetch();

			if (!$loadedItem) {
				$loadError = 'Inventory item not found.';
			} else {
				$inventoryItem = array_merge($inventoryItem, $loadedItem);
			}
		}
	} catch (PDOException $exception) {
		error_log('Inventory form load failed: ' . $exception->getMessage());
		$loadError = 'Unable to load the inventory item right now. Please try again later.';
	}
}

function inventory_form_escape($value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
?>
		<div class="main-container ace-save-state" id="main-container">
			<script type="text/javascript">
				try{ace.settings.loadState('main-container')}catch(e){}
			</script>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>
			<div class="main-content">
				<div class="main-content-inner">
					<div class="breadcrumbs ace-save-state" id="breadcrumbs">
						<ul class="breadcrumb">
							<li>
								<i class="ace-icon fa fa-home home-icon"></i>
								<a href="index.php">Home</a>
							</li>
							<li>
								<a href="inventory.php">Inventory</a>
							</li>
							<li class="active"><?php echo $isEdit ? 'Edit Item' : 'Add Item'; ?></li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								<?php echo $isEdit ? 'Edit Item' : 'Add Item'; ?>
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									inventory management
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($loadError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo inventory_form_escape($loadError); ?>
								</div>
<?php else: ?>
<?php if ($formError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo inventory_form_escape($formError); ?>
								</div>
<?php endif; ?>
<?php if (empty($outlets)): ?>
								<div class="alert alert-warning">
									<i class="ace-icon fa fa-info-circle"></i>
									Please add at least one outlet before creating inventory items.
								</div>
<?php endif; ?>
								<form class="form-horizontal" role="form" method="POST" action="inventory_save.php">
									<input type="hidden" name="id" value="<?php echo inventory_form_escape($inventoryItem['id'] ?? 0); ?>" />

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="outlet_id">Outlet</label>

										<div class="col-sm-9">
											<select id="outlet_id" name="outlet_id" class="col-xs-10 col-sm-5">
												<option value="">Select outlet</option>
<?php foreach ($outlets as $outlet): ?>
<?php $outletId = (int) ($outlet['id'] ?? 0); ?>
												<option value="<?php echo inventory_form_escape($outletId); ?>"<?php echo ((int) ($inventoryItem['outlet_id'] ?? 0) === $outletId) ? ' selected="selected"' : ''; ?>><?php echo inventory_form_escape($outlet['branch_name'] ?? ''); ?></option>
<?php endforeach; ?>
											</select>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="item_name">Item Name</label>

										<div class="col-sm-9">
											<input type="text" id="item_name" name="item_name" class="col-xs-10 col-sm-5" value="<?php echo inventory_form_escape($inventoryItem['item_name'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="item_category">Category</label>

										<div class="col-sm-9">
											<input type="text" id="item_category" name="item_category" class="col-xs-10 col-sm-5" value="<?php echo inventory_form_escape($inventoryItem['item_category'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="quantity">Quantity</label>

										<div class="col-sm-9">
											<input type="number" min="0" step="1" id="quantity" name="quantity" class="col-xs-10 col-sm-5" value="<?php echo inventory_form_escape($inventoryItem['quantity'] ?? '0'); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="unit">Unit</label>

										<div class="col-sm-9">
											<input type="text" id="unit" name="unit" class="col-xs-10 col-sm-5" value="<?php echo inventory_form_escape($inventoryItem['unit'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="item_condition">Condition</label>

										<div class="col-sm-9">
											<input type="text" id="item_condition" name="item_condition" class="col-xs-10 col-sm-5" value="<?php echo inventory_form_escape($inventoryItem['item_condition'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="purchase_date">Purchase Date</label>

										<div class="col-sm-9">
											<input type="date" id="purchase_date" name="purchase_date" class="col-xs-10 col-sm-5" value="<?php echo inventory_form_escape($inventoryItem['purchase_date'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="notes">Notes</label>

										<div class="col-sm-9">
											<textarea id="notes" name="notes" class="col-xs-10 col-sm-5" rows="4"><?php echo inventory_form_escape($inventoryItem['notes'] ?? ''); ?></textarea>
										</div>
									</div>

									<div class="clearfix form-actions">
										<div class="col-md-offset-3 col-md-9">
											<button class="btn btn-info" type="submit">
												<i class="ace-icon fa fa-check bigger-110"></i>
												Save Item
											</button>

											&nbsp; &nbsp; &nbsp;
											<a class="btn" href="inventory.php">
												<i class="ace-icon fa fa-undo bigger-110"></i>
												Back
											</a>
										</div>
									</div>
								</form>
<?php endif; ?>
								<!-- PAGE CONTENT ENDS -->
							</div><!-- /.col -->
						</div><!-- /.row -->
					</div><!-- /.page-content -->
				</div>
			</div><!-- /.main-content -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>

			<a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
				<i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
			</a>
		</div><!-- /.main-container -->

<?php
require_once __DIR__ . '/includes/scripts.php';
?>
	</body>
</html>
