<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'View Invoice - Ace Admin';
$pageDescription = 'payment details';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$paymentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$payment = null;
$loadError = '';
$currentUser = current_user();
$currentRole = $currentUser['role'] ?? '';
$canManagePayments = in_array($currentRole, ['admin', 'manager'], true);
$pdo = ace_admin_db();

if ($paymentId <= 0) {
	$loadError = 'Payment / invoice not found.';
} elseif (!$pdo instanceof PDO) {
	$loadError = 'Unable to load the invoice right now. Please check the database connection.';
} else {
	try {
		$statement = $pdo->prepare(
			'SELECT payments.id, payments.booking_id, payments.invoice_number, payments.total_amount,
				payments.discount_amount, payments.paid_amount, payments.due_amount, payments.payment_status,
				payments.payment_method, payments.payment_date, payments.notes AS payment_notes,
				payments.created_at, payments.updated_at,
				bookings.booking_type, bookings.guest_name, bookings.guest_phone, bookings.guest_email,
				bookings.appointment_date, bookings.appointment_time, bookings.booking_status,
				bookings.notes AS booking_notes,
				customers.customer_name, customers.phone AS customer_phone, customers.email AS customer_email,
				branches.branch_name, branches.full_address, branches.phone AS branch_phone,
				services.service_name, employees.employee_name
			FROM payments
			INNER JOIN bookings ON bookings.id = payments.booking_id
			LEFT JOIN customers ON customers.id = bookings.customer_id
			INNER JOIN branches ON branches.id = bookings.outlet_id
			INNER JOIN services ON services.id = bookings.service_id
			LEFT JOIN employees ON employees.id = bookings.employee_id
			WHERE payments.id = :id
			LIMIT 1'
		);
		$statement->execute(['id' => $paymentId]);
		$payment = $statement->fetch();

		if (!$payment) {
			$loadError = 'Payment / invoice not found.';
		}
	} catch (PDOException $exception) {
		error_log('Payment view failed: ' . $exception->getMessage());
		$loadError = 'Unable to load the invoice right now. Please try again later.';
	}
}

function payment_view_escape($value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function payment_view_display($value): string
{
	$value = trim((string) $value);

	return $value !== '' ? $value : '-';
}

function payment_view_money($value): string
{
	return number_format((float) $value, 2);
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
								<a href="payments.php">Payments / Invoices</a>
							</li>
							<li class="active">View Invoice</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								View Invoice
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									payment details
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($loadError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo payment_view_escape($loadError); ?>
								</div>
<?php else: ?>
<?php
	$bookingType = (string) ($payment['booking_type'] ?? '');
	$customerName = $bookingType === 'registered' ? ($payment['customer_name'] ?? '') : ($payment['guest_name'] ?? '');
	$customerPhone = $bookingType === 'registered' ? ($payment['customer_phone'] ?? '') : ($payment['guest_phone'] ?? '');
	$customerEmail = $bookingType === 'registered' ? ($payment['customer_email'] ?? '') : ($payment['guest_email'] ?? '');
	$paymentStatus = (string) ($payment['payment_status'] ?? '');
	$paymentStatusClass = [
		'unpaid' => 'label-warning',
		'partial' => 'label-info',
		'paid' => 'label-success',
	][$paymentStatus] ?? 'label-default';
?>
								<div class="widget-box">
									<div class="widget-header">
										<h4 class="widget-title">
											<i class="ace-icon fa fa-file-text-o"></i>
											Invoice <?php echo payment_view_escape(payment_view_display($payment['invoice_number'] ?? '')); ?>
										</h4>

										<div class="widget-toolbar">
											<a href="payments.php">
												<i class="ace-icon fa fa-list"></i>
											</a>
										</div>
									</div>

									<div class="widget-body">
										<div class="widget-main">
											<div class="row">
												<div class="col-sm-6">
													<h4 class="header blue smaller">Customer / Booking</h4>
													<dl class="dl-horizontal">
														<dt>Booking ID</dt>
														<dd>#<?php echo payment_view_escape((int) ($payment['booking_id'] ?? 0)); ?></dd>

														<dt>Booking Type</dt>
														<dd><?php echo payment_view_escape(payment_view_display($bookingType)); ?></dd>

														<dt>Name</dt>
														<dd><?php echo payment_view_escape(payment_view_display($customerName)); ?></dd>

														<dt>Phone</dt>
														<dd><?php echo payment_view_escape(payment_view_display($customerPhone)); ?></dd>

														<dt>Email</dt>
														<dd><?php echo payment_view_escape(payment_view_display($customerEmail)); ?></dd>

														<dt>Appointment</dt>
														<dd>
															<?php echo payment_view_escape(payment_view_display($payment['appointment_date'] ?? '')); ?>
															<?php echo payment_view_escape(payment_view_display($payment['appointment_time'] ?? '')); ?>
														</dd>

														<dt>Booking Status</dt>
														<dd><?php echo payment_view_escape(payment_view_display($payment['booking_status'] ?? '')); ?></dd>
													</dl>
												</div>

												<div class="col-sm-6">
													<h4 class="header blue smaller">Service / Outlet</h4>
													<dl class="dl-horizontal">
														<dt>Service</dt>
														<dd><?php echo payment_view_escape(payment_view_display($payment['service_name'] ?? '')); ?></dd>

														<dt>Outlet</dt>
														<dd><?php echo payment_view_escape(payment_view_display($payment['branch_name'] ?? '')); ?></dd>

														<dt>Outlet Phone</dt>
														<dd><?php echo payment_view_escape(payment_view_display($payment['branch_phone'] ?? '')); ?></dd>

														<dt>Address</dt>
														<dd><?php echo nl2br(payment_view_escape(payment_view_display($payment['full_address'] ?? ''))); ?></dd>

														<dt>Employee</dt>
														<dd><?php echo payment_view_escape(payment_view_display($payment['employee_name'] ?? '')); ?></dd>
													</dl>
												</div>
											</div>

											<div class="space-8"></div>

											<div class="row">
												<div class="col-sm-6">
													<h4 class="header blue smaller">Payment</h4>
													<dl class="dl-horizontal">
														<dt>Total Amount</dt>
														<dd><?php echo payment_view_escape(payment_view_money($payment['total_amount'] ?? 0)); ?></dd>

														<dt>Discount</dt>
														<dd><?php echo payment_view_escape(payment_view_money($payment['discount_amount'] ?? 0)); ?></dd>

														<dt>Paid Amount</dt>
														<dd><?php echo payment_view_escape(payment_view_money($payment['paid_amount'] ?? 0)); ?></dd>

														<dt>Due Amount</dt>
														<dd><?php echo payment_view_escape(payment_view_money($payment['due_amount'] ?? 0)); ?></dd>

														<dt>Status</dt>
														<dd>
															<span class="label label-sm <?php echo $paymentStatusClass; ?>">
																<?php echo payment_view_escape(payment_view_display($paymentStatus)); ?>
															</span>
														</dd>

														<dt>Method</dt>
														<dd><?php echo payment_view_escape(payment_view_display($payment['payment_method'] ?? '')); ?></dd>

														<dt>Payment Date</dt>
														<dd><?php echo payment_view_escape(payment_view_display($payment['payment_date'] ?? '')); ?></dd>
													</dl>
												</div>

												<div class="col-sm-6">
													<h4 class="header blue smaller">Notes</h4>
													<p><?php echo nl2br(payment_view_escape(payment_view_display($payment['payment_notes'] ?? ''))); ?></p>

													<h4 class="header blue smaller">Booking Notes</h4>
													<p><?php echo nl2br(payment_view_escape(payment_view_display($payment['booking_notes'] ?? ''))); ?></p>

													<h4 class="header blue smaller">Record</h4>
													<dl class="dl-horizontal">
														<dt>Created At</dt>
														<dd><?php echo payment_view_escape(payment_view_display($payment['created_at'] ?? '')); ?></dd>

														<dt>Updated At</dt>
														<dd><?php echo payment_view_escape(payment_view_display($payment['updated_at'] ?? '')); ?></dd>
													</dl>
												</div>
											</div>

											<div class="clearfix form-actions">
<?php if ($canManagePayments): ?>
												<a class="btn btn-info" href="payment_form.php?id=<?php echo payment_view_escape((int) ($payment['id'] ?? 0)); ?>">
													<i class="ace-icon fa fa-pencil bigger-110"></i>
													Edit
												</a>

												&nbsp; &nbsp; &nbsp;
<?php endif; ?>
												<a class="btn" href="payments.php">
													<i class="ace-icon fa fa-undo bigger-110"></i>
													Back
												</a>
											</div>
										</div>
									</div>
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
