<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Payments / Invoices - Ace Admin';
$pageDescription = 'payment records';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$payments = [];
$databaseError = '';
$successMessages = [
	'payment_created' => 'Payment / invoice created successfully.',
	'payment_updated' => 'Payment / invoice updated successfully.',
];
$errorMessages = [
	'invalid_request' => 'Invalid request.',
	'payment_not_found' => 'Payment / invoice not found.',
	'database' => 'Unable to complete the request. Please try again.',
	'duplicate_invoice' => 'That invoice number is already in use.',
	'permission_denied' => 'You do not have permission to manage payments.',
];
$successMessage = $successMessages[$_GET['success'] ?? ''] ?? '';
$errorMessage = $errorMessages[$_GET['error'] ?? ''] ?? '';
$searchQuery = trim((string) ($_GET['search'] ?? ''));
$currentUser = current_user();
$currentRole = $currentUser['role'] ?? '';
$canManagePayments = in_array($currentRole, ['admin', 'manager'], true);
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$databaseError = 'Unable to load payments right now. Please check the database connection.';
} else {
	try {
		$baseQuery = 'SELECT payments.id, payments.booking_id, payments.invoice_number, payments.total_amount,
				payments.discount_amount, payments.paid_amount, payments.due_amount, payments.payment_status,
				payments.payment_method, payments.payment_date, payments.created_at,
				bookings.booking_type, bookings.guest_name, customers.customer_name,
				branches.branch_name, services.service_name, employees.employee_name
			FROM payments
			INNER JOIN bookings ON bookings.id = payments.booking_id
			LEFT JOIN customers ON customers.id = bookings.customer_id
			INNER JOIN branches ON branches.id = bookings.outlet_id
			INNER JOIN services ON services.id = bookings.service_id
			LEFT JOIN employees ON employees.id = bookings.employee_id';

		if ($searchQuery !== '') {
			$statement = $pdo->prepare(
				$baseQuery . '
				WHERE payments.invoice_number LIKE :search_invoice
					OR CAST(payments.booking_id AS CHAR) LIKE :search_booking
					OR customers.customer_name LIKE :search_customer
					OR bookings.guest_name LIKE :search_guest
					OR services.service_name LIKE :search_service
					OR branches.branch_name LIKE :search_outlet
					OR payments.payment_status LIKE :search_status
					OR payments.payment_method LIKE :search_method
					OR CAST(payments.payment_date AS CHAR) LIKE :search_date
				ORDER BY payments.created_at DESC, payments.id DESC'
			);
			$searchTerm = '%' . $searchQuery . '%';
			$statement->bindValue(':search_invoice', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_booking', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_customer', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_guest', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_service', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_outlet', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_status', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_method', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_date', $searchTerm, PDO::PARAM_STR);
			$statement->execute();
		} else {
			$statement = $pdo->query($baseQuery . ' ORDER BY payments.created_at DESC, payments.id DESC');
		}

		$payments = $statement->fetchAll();
	} catch (PDOException $exception) {
		error_log('Payments query failed: ' . $exception->getMessage());
		$databaseError = 'Unable to load payments right now. Please try again later.';
	}
}

function payments_escape($value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function payments_display($value): string
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
							<li class="active">Payments / Invoices</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Payments / Invoices
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									payment records
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($successMessage !== ''): ?>
								<div class="alert alert-success">
									<i class="ace-icon fa fa-check"></i>
									<?php echo payments_escape($successMessage); ?>
								</div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo payments_escape($errorMessage); ?>
								</div>
<?php endif; ?>
<?php if ($searchQuery !== ''): ?>
								<div class="alert alert-info">
									<i class="ace-icon fa fa-search"></i>
									Search results for: "<?php echo payments_escape($searchQuery); ?>"
								</div>
<?php endif; ?>

								<div class="clearfix">
									<form class="form-search pull-left" method="GET" action="payments.php">
										<span class="input-icon">
											<input type="text" name="search" class="nav-search-input" placeholder="Search payments ..." value="<?php echo payments_escape($searchQuery); ?>" autocomplete="off" />
											<i class="ace-icon fa fa-search nav-search-icon"></i>
										</span>
									</form>

<?php if ($canManagePayments): ?>
									<a href="payment_form.php" class="btn btn-sm btn-primary pull-right">
										<i class="ace-icon fa fa-plus"></i>
										Add Payment / Invoice
									</a>
<?php endif; ?>
								</div>

								<div class="space-6"></div>

<?php if ($databaseError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo payments_escape($databaseError); ?>
								</div>
<?php else: ?>
								<div class="table-responsive">
									<table id="payments-table" class="table table-bordered table-hover">
										<thead>
											<tr>
												<th>ID</th>
												<th>Invoice Number</th>
												<th>Booking ID</th>
												<th>Customer / Guest Name</th>
												<th>Service</th>
												<th>Outlet</th>
												<th>Total Amount</th>
												<th>Discount</th>
												<th>Paid Amount</th>
												<th>Due Amount</th>
												<th>Payment Status</th>
												<th>Payment Method</th>
												<th>Payment Date</th>
												<th>Created At</th>
												<th>Actions</th>
											</tr>
										</thead>

										<tbody>
<?php if (empty($payments)): ?>
											<tr>
												<td colspan="15" class="center">No payments found.</td>
											</tr>
<?php else: ?>
<?php foreach ($payments as $payment): ?>
<?php
	$paymentId = (int) ($payment['id'] ?? 0);
	$bookingType = (string) ($payment['booking_type'] ?? '');
	$displayName = $bookingType === 'registered' ? ($payment['customer_name'] ?? '') : ($payment['guest_name'] ?? '');
	$status = (string) ($payment['payment_status'] ?? '');
	$statusClass = [
		'unpaid' => 'label-warning',
		'partial' => 'label-info',
		'paid' => 'label-success',
	][$status] ?? 'label-default';
?>
											<tr>
												<td><?php echo payments_escape($paymentId); ?></td>
												<td><?php echo payments_escape(payments_display($payment['invoice_number'] ?? '')); ?></td>
												<td><?php echo payments_escape((int) ($payment['booking_id'] ?? 0)); ?></td>
												<td><?php echo payments_escape(payments_display($displayName)); ?></td>
												<td><?php echo payments_escape(payments_display($payment['service_name'] ?? '')); ?></td>
												<td><?php echo payments_escape(payments_display($payment['branch_name'] ?? '')); ?></td>
												<td><?php echo payments_escape(number_format((float) ($payment['total_amount'] ?? 0), 2)); ?></td>
												<td><?php echo payments_escape(number_format((float) ($payment['discount_amount'] ?? 0), 2)); ?></td>
												<td><?php echo payments_escape(number_format((float) ($payment['paid_amount'] ?? 0), 2)); ?></td>
												<td><?php echo payments_escape(number_format((float) ($payment['due_amount'] ?? 0), 2)); ?></td>
												<td>
													<span class="label label-sm <?php echo $statusClass; ?>">
														<?php echo payments_escape(payments_display($status)); ?>
													</span>
												</td>
												<td><?php echo payments_escape(payments_display($payment['payment_method'] ?? '')); ?></td>
												<td><?php echo payments_escape(payments_display($payment['payment_date'] ?? '')); ?></td>
												<td><?php echo payments_escape(payments_display($payment['created_at'] ?? '')); ?></td>
												<td>
													<a href="payment_view.php?id=<?php echo payments_escape($paymentId); ?>" class="btn btn-xs btn-success">
														<i class="ace-icon fa fa-eye bigger-120"></i>
													</a>
<?php if ($canManagePayments): ?>
													<a href="payment_form.php?id=<?php echo payments_escape($paymentId); ?>" class="btn btn-xs btn-info">
														<i class="ace-icon fa fa-pencil bigger-120"></i>
													</a>
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
