<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Customers - Ace Admin';
$pageDescription = 'salon and spa customers';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$customers = [];
$databaseError = '';
$successMessages = [
	'customer_created' => 'Customer created successfully.',
	'customer_updated' => 'Customer updated successfully.',
];
$errorMessages = [
	'invalid_request' => 'Invalid request.',
	'customer_not_found' => 'Customer not found.',
	'database' => 'Unable to complete the request. Please try again.',
	'permission_denied' => 'You do not have permission to manage customers.',
];
$successMessage = $successMessages[$_GET['success'] ?? ''] ?? '';
$errorMessage = $errorMessages[$_GET['error'] ?? ''] ?? '';
$searchQuery = trim((string) ($_GET['search'] ?? ''));
$currentUser = current_user();
$isAdminUser = ($currentUser['role'] ?? '') === 'admin';
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$databaseError = 'Unable to load customers right now. Please check the database connection.';
} else {
	try {
		if ($searchQuery !== '') {
			$statement = $pdo->prepare(
				'SELECT id, customer_name, phone, email, gender, date_of_birth, address, notes, created_at, updated_at
				FROM customers
				WHERE customer_name LIKE :search_customer_name
					OR phone LIKE :search_phone
					OR email LIKE :search_email
					OR gender LIKE :search_gender
					OR address LIKE :search_address
					OR notes LIKE :search_notes
				ORDER BY created_at DESC, id DESC'
			);
			$searchTerm = '%' . $searchQuery . '%';
			$statement->bindValue(':search_customer_name', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_phone', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_email', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_gender', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_address', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_notes', $searchTerm, PDO::PARAM_STR);
			$statement->execute();
		} else {
			$statement = $pdo->query(
				'SELECT id, customer_name, phone, email, gender, date_of_birth, address, notes, created_at, updated_at
				FROM customers
				ORDER BY created_at DESC, id DESC'
			);
		}

		$customers = $statement->fetchAll();
	} catch (PDOException $exception) {
		error_log('Customers query failed: ' . $exception->getMessage());
		$databaseError = 'Unable to load customers right now. Please try again later.';
	}
}

function customers_escape($value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function customers_display($value): string
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
							<li class="active">Customers</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Customers
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									salon and spa customers
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($successMessage !== ''): ?>
								<div class="alert alert-success">
									<i class="ace-icon fa fa-check"></i>
									<?php echo customers_escape($successMessage); ?>
								</div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo customers_escape($errorMessage); ?>
								</div>
<?php endif; ?>
<?php if ($searchQuery !== ''): ?>
								<div class="alert alert-info">
									<i class="ace-icon fa fa-search"></i>
									Search results for: "<?php echo customers_escape($searchQuery); ?>"
								</div>
<?php endif; ?>

								<div class="clearfix">
									<form class="form-search pull-left" method="GET" action="customers.php">
										<span class="input-icon">
											<input type="text" name="search" class="nav-search-input" placeholder="Search customers ..." value="<?php echo customers_escape($searchQuery); ?>" autocomplete="off" />
											<i class="ace-icon fa fa-search nav-search-icon"></i>
										</span>
									</form>

<?php if ($isAdminUser): ?>
									<a href="customer_form.php" class="btn btn-sm btn-primary pull-right">
										<i class="ace-icon fa fa-plus"></i>
										Add Customer
									</a>
<?php endif; ?>
								</div>

								<div class="space-6"></div>

<?php if ($databaseError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo customers_escape($databaseError); ?>
								</div>
<?php else: ?>
								<div class="table-responsive">
									<table id="customers-table" class="table table-bordered table-hover">
										<thead>
											<tr>
												<th>ID</th>
												<th>Customer Name</th>
												<th>Phone</th>
												<th>Email</th>
												<th>Gender</th>
												<th>Date of Birth</th>
												<th>Address</th>
												<th>Created At</th>
												<th>Updated At</th>
												<th>Actions</th>
											</tr>
										</thead>

										<tbody>
<?php if (empty($customers)): ?>
											<tr>
												<td colspan="10" class="center">No customers found.</td>
											</tr>
<?php else: ?>
<?php foreach ($customers as $customer): ?>
<?php $customerId = (int) ($customer['id'] ?? 0); ?>
											<tr>
												<td><?php echo customers_escape($customerId); ?></td>
												<td>
													<strong><?php echo customers_escape(customers_display($customer['customer_name'] ?? '')); ?></strong>
<?php if (trim((string) ($customer['notes'] ?? '')) !== ''): ?>
													<div class="space-2"></div>
													<?php echo nl2br(customers_escape($customer['notes'])); ?>
<?php endif; ?>
												</td>
												<td><?php echo customers_escape(customers_display($customer['phone'] ?? '')); ?></td>
												<td><?php echo customers_escape(customers_display($customer['email'] ?? '')); ?></td>
												<td><?php echo customers_escape(customers_display($customer['gender'] ?? '')); ?></td>
												<td><?php echo customers_escape(customers_display($customer['date_of_birth'] ?? '')); ?></td>
												<td><?php echo nl2br(customers_escape(customers_display($customer['address'] ?? ''))); ?></td>
												<td><?php echo customers_escape(customers_display($customer['created_at'] ?? '')); ?></td>
												<td><?php echo customers_escape(customers_display($customer['updated_at'] ?? '')); ?></td>
												<td>
<?php if ($isAdminUser): ?>
													<a href="customer_form.php?id=<?php echo customers_escape($customerId); ?>" class="btn btn-xs btn-info">
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
