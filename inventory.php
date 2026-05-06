<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Inventory - Ace Admin';
$pageDescription = 'salon and spa items';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$inventoryItems = [];
$databaseError = '';
$successMessages = [
	'inventory_created' => 'Inventory item created successfully.',
	'inventory_updated' => 'Inventory item updated successfully.',
];
$errorMessages = [
	'invalid_request' => 'Invalid request.',
	'inventory_not_found' => 'Inventory item not found.',
	'database' => 'Unable to complete the request. Please try again.',
	'permission_denied' => 'You do not have permission to manage inventory.',
];
$successMessage = $successMessages[$_GET['success'] ?? ''] ?? '';
$errorMessage = $errorMessages[$_GET['error'] ?? ''] ?? '';
$searchQuery = trim((string) ($_GET['search'] ?? ''));
$currentUser = current_user();
$currentRole = $currentUser['role'] ?? '';
$canManageInventory = in_array($currentRole, ['admin', 'manager'], true);
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$databaseError = 'Unable to load inventory right now. Please check the database connection.';
} else {
	try {
		$baseQuery = 'SELECT inventory_items.id, inventory_items.item_name, inventory_items.item_category,
				inventory_items.quantity, inventory_items.unit, inventory_items.item_condition,
				inventory_items.purchase_date, inventory_items.notes, inventory_items.created_at,
				inventory_items.updated_at, branches.branch_name
			FROM inventory_items
			INNER JOIN branches ON branches.id = inventory_items.outlet_id';

		if ($searchQuery !== '') {
			$statement = $pdo->prepare(
				$baseQuery . '
				WHERE inventory_items.item_name LIKE :search_item_name
					OR inventory_items.item_category LIKE :search_item_category
					OR branches.branch_name LIKE :search_outlet_name
					OR inventory_items.unit LIKE :search_unit
					OR inventory_items.item_condition LIKE :search_condition
					OR inventory_items.notes LIKE :search_notes
				ORDER BY inventory_items.created_at DESC, inventory_items.id DESC'
			);
			$searchTerm = '%' . $searchQuery . '%';
			$statement->bindValue(':search_item_name', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_item_category', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_outlet_name', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_unit', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_condition', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_notes', $searchTerm, PDO::PARAM_STR);
			$statement->execute();
		} else {
			$statement = $pdo->query(
				$baseQuery . '
				ORDER BY inventory_items.created_at DESC, inventory_items.id DESC'
			);
		}

		$inventoryItems = $statement->fetchAll();
	} catch (PDOException $exception) {
		error_log('Inventory query failed: ' . $exception->getMessage());
		$databaseError = 'Unable to load inventory right now. Please try again later.';
	}
}

function inventory_escape($value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function inventory_display($value): string
{
	$value = trim((string) $value);

	return $value !== '' ? $value : '-';
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
							<li class="active">Inventory</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Inventory
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									salon and spa items
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($successMessage !== ''): ?>
								<div class="alert alert-success">
									<i class="ace-icon fa fa-check"></i>
									<?php echo inventory_escape($successMessage); ?>
								</div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo inventory_escape($errorMessage); ?>
								</div>
<?php endif; ?>
<?php if ($searchQuery !== ''): ?>
								<div class="alert alert-info">
									<i class="ace-icon fa fa-search"></i>
									Search results for: "<?php echo inventory_escape($searchQuery); ?>"
								</div>
<?php endif; ?>

								<div class="clearfix">
									<form class="form-search pull-left" method="GET" action="inventory.php">
										<span class="input-icon">
											<input type="text" name="search" class="nav-search-input" placeholder="Search inventory ..." value="<?php echo inventory_escape($searchQuery); ?>" autocomplete="off" />
											<i class="ace-icon fa fa-search nav-search-icon"></i>
										</span>
									</form>

<?php if ($canManageInventory): ?>
									<a href="inventory_form.php" class="btn btn-sm btn-primary pull-right">
										<i class="ace-icon fa fa-plus"></i>
										Add Item
									</a>
<?php endif; ?>
								</div>

								<div class="space-6"></div>

<?php if ($databaseError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo inventory_escape($databaseError); ?>
								</div>
<?php else: ?>
								<div class="table-responsive">
									<table id="inventory-table" class="table table-bordered table-hover">
										<thead>
											<tr>
												<th>ID</th>
												<th>Item Name</th>
												<th>Category</th>
												<th>Outlet</th>
												<th>Quantity</th>
												<th>Unit</th>
												<th>Condition</th>
												<th>Purchase Date</th>
												<th>Created At</th>
												<th>Updated At</th>
												<th>Actions</th>
											</tr>
										</thead>

										<tbody>
<?php if (empty($inventoryItems)): ?>
											<tr>
												<td colspan="11" class="center">No inventory items found.</td>
											</tr>
<?php else: ?>
<?php foreach ($inventoryItems as $item): ?>
<?php $itemId = (int) ($item['id'] ?? 0); ?>
											<tr>
												<td><?php echo inventory_escape($itemId); ?></td>
												<td>
													<strong><?php echo inventory_escape(inventory_display($item['item_name'] ?? '')); ?></strong>
<?php if (trim((string) ($item['notes'] ?? '')) !== ''): ?>
													<div class="space-2"></div>
													<?php echo nl2br(inventory_escape($item['notes'])); ?>
<?php endif; ?>
												</td>
												<td><?php echo inventory_escape(inventory_display($item['item_category'] ?? '')); ?></td>
												<td><?php echo inventory_escape(inventory_display($item['branch_name'] ?? '')); ?></td>
												<td><?php echo inventory_escape((int) ($item['quantity'] ?? 0)); ?></td>
												<td><?php echo inventory_escape(inventory_display($item['unit'] ?? '')); ?></td>
												<td><?php echo inventory_escape(inventory_display($item['item_condition'] ?? '')); ?></td>
												<td><?php echo inventory_escape(inventory_display($item['purchase_date'] ?? '')); ?></td>
												<td><?php echo inventory_escape(inventory_display($item['created_at'] ?? '')); ?></td>
												<td><?php echo inventory_escape(inventory_display($item['updated_at'] ?? '')); ?></td>
												<td>
<?php if ($canManageInventory): ?>
													<a href="inventory_form.php?id=<?php echo inventory_escape($itemId); ?>" class="btn btn-xs btn-info">
														<i class="ace-icon fa fa-pencil bigger-120"></i>
													</a>
<?php else: ?>
													<span class="text-muted">View only</span>
<?php endif; ?>
												</td>
											</tr>
<?php endforeach; ?>
<?php endif; ?>
										</tbody>
									</table>
								</div>
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
