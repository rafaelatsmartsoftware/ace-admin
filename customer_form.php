<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Customer Form - Ace Admin';
$pageDescription = 'add or edit customer';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$currentUser = current_user();
$isAdminUser = ($currentUser['role'] ?? '') === 'admin';

if (!$isAdminUser) {
	header('Location: customers.php?error=permission_denied');
	exit;
}

$customerId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $customerId > 0;
$customer = [
	'id' => 0,
	'customer_name' => '',
	'phone' => '',
	'email' => '',
	'gender' => '',
	'date_of_birth' => '',
	'address' => '',
	'notes' => '',
];
$loadError = '';
$formErrors = [
	'invalid' => 'Please complete the required fields.',
	'invalid_email' => 'Please enter a valid email address.',
	'customer_not_found' => 'Customer not found.',
	'database' => 'Unable to save the customer. Please try again.',
	'permission_denied' => 'You do not have permission to manage customers.',
];
$formError = $formErrors[$_GET['error'] ?? ''] ?? '';
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$loadError = 'Unable to load the customer form right now. Please check the database connection.';
} elseif ($isEdit) {
	try {
		$statement = $pdo->prepare(
			'SELECT id, customer_name, phone, email, gender, date_of_birth, address, notes
			FROM customers
			WHERE id = :id
			LIMIT 1'
		);
		$statement->execute(['id' => $customerId]);
		$loadedCustomer = $statement->fetch();

		if (!$loadedCustomer) {
			$loadError = 'Customer not found.';
		} else {
			$customer = array_merge($customer, $loadedCustomer);
		}
	} catch (PDOException $exception) {
		error_log('Customer form load failed: ' . $exception->getMessage());
		$loadError = 'Unable to load the customer right now. Please try again later.';
	}
}

function customer_form_escape($value): string
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
								<a href="customers.php">Customers</a>
							</li>
							<li class="active"><?php echo $isEdit ? 'Edit Customer' : 'Add Customer'; ?></li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								<?php echo $isEdit ? 'Edit Customer' : 'Add Customer'; ?>
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									customers management
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($loadError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo customer_form_escape($loadError); ?>
								</div>
<?php else: ?>
<?php if ($formError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo customer_form_escape($formError); ?>
								</div>
<?php endif; ?>
								<form class="form-horizontal" role="form" method="POST" action="customer_save.php">
									<input type="hidden" name="id" value="<?php echo customer_form_escape($customer['id'] ?? 0); ?>" />

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="customer_name">Customer Name</label>

										<div class="col-sm-9">
											<input type="text" id="customer_name" name="customer_name" class="col-xs-10 col-sm-5" value="<?php echo customer_form_escape($customer['customer_name'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="phone">Phone</label>

										<div class="col-sm-9">
											<input type="text" id="phone" name="phone" class="col-xs-10 col-sm-5" value="<?php echo customer_form_escape($customer['phone'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="email">Email</label>

										<div class="col-sm-9">
											<input type="email" id="email" name="email" class="col-xs-10 col-sm-5" value="<?php echo customer_form_escape($customer['email'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="gender">Gender</label>

										<div class="col-sm-9">
											<input type="text" id="gender" name="gender" class="col-xs-10 col-sm-5" value="<?php echo customer_form_escape($customer['gender'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="date_of_birth">Date of Birth</label>

										<div class="col-sm-9">
											<input type="date" id="date_of_birth" name="date_of_birth" class="col-xs-10 col-sm-5" value="<?php echo customer_form_escape($customer['date_of_birth'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="address">Address</label>

										<div class="col-sm-9">
											<textarea id="address" name="address" class="col-xs-10 col-sm-5" rows="4"><?php echo customer_form_escape($customer['address'] ?? ''); ?></textarea>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="notes">Notes</label>

										<div class="col-sm-9">
											<textarea id="notes" name="notes" class="col-xs-10 col-sm-5" rows="4"><?php echo customer_form_escape($customer['notes'] ?? ''); ?></textarea>
										</div>
									</div>

									<div class="clearfix form-actions">
										<div class="col-md-offset-3 col-md-9">
											<button class="btn btn-info" type="submit">
												<i class="ace-icon fa fa-check bigger-110"></i>
												Save Customer
											</button>

											&nbsp; &nbsp; &nbsp;
											<a class="btn" href="customers.php">
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
