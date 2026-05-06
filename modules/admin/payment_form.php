<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Payment Form - Ace Admin';
$pageDescription = 'add or edit payment';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$currentUser = current_user();
$currentRole = $currentUser['role'] ?? '';
$canManagePayments = in_array($currentRole, ['admin', 'manager'], true);

if (!$canManagePayments) {
	header('Location: payments.php?error=permission_denied');
	exit;
}

$paymentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $paymentId > 0;
$payment = [
	'id' => 0,
	'booking_id' => 0,
	'invoice_number' => 'INV-' . date('Ymd-His'),
	'total_amount' => '',
	'discount_amount' => '0.00',
	'paid_amount' => '0.00',
	'due_amount' => '0.00',
	'payment_status' => 'unpaid',
	'payment_method' => 'pay_at_salon',
	'payment_date' => '',
	'notes' => '',
];
$bookings = [];
$loadError = '';
$formErrors = [
	'invalid' => 'Please complete the required fields.',
	'duplicate_invoice' => 'That invoice number is already in use.',
	'payment_not_found' => 'Payment / invoice not found.',
	'database' => 'Unable to save the payment. Please try again.',
	'permission_denied' => 'You do not have permission to manage payments.',
];
$formError = $formErrors[$_GET['error'] ?? ''] ?? '';
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$loadError = 'Unable to load the payment form right now. Please check the database connection.';
} else {
	try {
		$bookingStatement = $pdo->query(
			'SELECT bookings.id, bookings.booking_type, bookings.guest_name, bookings.appointment_date,
				customers.customer_name, services.service_name, services.price
			FROM bookings
			LEFT JOIN customers ON customers.id = bookings.customer_id
			INNER JOIN services ON services.id = bookings.service_id
			ORDER BY bookings.id DESC'
		);
		$bookings = $bookingStatement->fetchAll();

		if ($isEdit) {
			$statement = $pdo->prepare(
				'SELECT id, booking_id, invoice_number, total_amount, discount_amount, paid_amount,
					due_amount, payment_status, payment_method, payment_date, notes
				FROM payments
				WHERE id = :id
				LIMIT 1'
			);
			$statement->execute(['id' => $paymentId]);
			$loadedPayment = $statement->fetch();

			if (!$loadedPayment) {
				$loadError = 'Payment / invoice not found.';
			} else {
				$payment = array_merge($payment, $loadedPayment);
			}
		}
	} catch (PDOException $exception) {
		error_log('Payment form load failed: ' . $exception->getMessage());
		$loadError = 'Unable to load the payment right now. Please try again later.';
	}
}

function payment_form_escape($value): string
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
								<a href="payments.php">Payments / Invoices</a>
							</li>
							<li class="active"><?php echo $isEdit ? 'Edit Payment' : 'Add Payment'; ?></li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								<?php echo $isEdit ? 'Edit Payment' : 'Add Payment'; ?>
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									payments / invoices
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($loadError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo payment_form_escape($loadError); ?>
								</div>
<?php else: ?>
<?php if ($formError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo payment_form_escape($formError); ?>
								</div>
<?php endif; ?>
<?php if (empty($bookings)): ?>
								<div class="alert alert-warning">
									<i class="ace-icon fa fa-info-circle"></i>
									Please add at least one booking before creating payments.
								</div>
<?php endif; ?>
								<form class="form-horizontal" role="form" method="POST" action="payment_save.php">
									<input type="hidden" name="id" value="<?php echo payment_form_escape($payment['id'] ?? 0); ?>" />

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="booking_id">Booking</label>

										<div class="col-sm-9">
											<select id="booking_id" name="booking_id" class="col-xs-10 col-sm-5">
												<option value="">Select booking</option>
<?php foreach ($bookings as $booking): ?>
<?php
	$bookingId = (int) ($booking['id'] ?? 0);
	$bookingName = ($booking['booking_type'] ?? '') === 'registered' ? ($booking['customer_name'] ?? '') : ($booking['guest_name'] ?? '');
?>
												<option value="<?php echo payment_form_escape($bookingId); ?>"<?php echo ((int) ($payment['booking_id'] ?? 0) === $bookingId) ? ' selected="selected"' : ''; ?>>
													#<?php echo payment_form_escape($bookingId); ?> - <?php echo payment_form_escape($bookingName); ?> - <?php echo payment_form_escape($booking['service_name'] ?? ''); ?> - <?php echo payment_form_escape(number_format((float) ($booking['price'] ?? 0), 2)); ?>
												</option>
<?php endforeach; ?>
											</select>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="invoice_number">Invoice Number</label>

										<div class="col-sm-9">
											<input type="text" id="invoice_number" name="invoice_number" class="col-xs-10 col-sm-5" value="<?php echo payment_form_escape($payment['invoice_number'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="total_amount">Total Amount</label>

										<div class="col-sm-9">
											<input type="number" step="0.01" min="0" id="total_amount" name="total_amount" class="col-xs-10 col-sm-5" value="<?php echo payment_form_escape($payment['total_amount'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="discount_amount">Discount Amount</label>

										<div class="col-sm-9">
											<input type="number" step="0.01" min="0" id="discount_amount" name="discount_amount" class="col-xs-10 col-sm-5" value="<?php echo payment_form_escape($payment['discount_amount'] ?? '0.00'); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="paid_amount">Paid Amount</label>

										<div class="col-sm-9">
											<input type="number" step="0.01" min="0" id="paid_amount" name="paid_amount" class="col-xs-10 col-sm-5" value="<?php echo payment_form_escape($payment['paid_amount'] ?? '0.00'); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="due_amount">Due Amount</label>

										<div class="col-sm-9">
											<input type="number" step="0.01" id="due_amount" name="due_amount" class="col-xs-10 col-sm-5" value="<?php echo payment_form_escape($payment['due_amount'] ?? '0.00'); ?>" readonly="readonly" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="payment_status">Payment Status</label>

										<div class="col-sm-9">
											<select id="payment_status" name="payment_status" class="col-xs-10 col-sm-5">
<?php foreach (['unpaid', 'partial', 'paid'] as $statusOption): ?>
												<option value="<?php echo payment_form_escape($statusOption); ?>"<?php echo (($payment['payment_status'] ?? '') === $statusOption) ? ' selected="selected"' : ''; ?>><?php echo payment_form_escape($statusOption); ?></option>
<?php endforeach; ?>
											</select>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="payment_method">Payment Method</label>

										<div class="col-sm-9">
											<input type="text" id="payment_method" name="payment_method" class="col-xs-10 col-sm-5" value="<?php echo payment_form_escape($payment['payment_method'] ?? 'pay_at_salon'); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="payment_date">Payment Date</label>

										<div class="col-sm-9">
											<input type="date" id="payment_date" name="payment_date" class="col-xs-10 col-sm-5" value="<?php echo payment_form_escape($payment['payment_date'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="notes">Notes</label>

										<div class="col-sm-9">
											<textarea id="notes" name="notes" class="col-xs-10 col-sm-5" rows="4"><?php echo payment_form_escape($payment['notes'] ?? ''); ?></textarea>
										</div>
									</div>

									<div class="clearfix form-actions">
										<div class="col-md-offset-3 col-md-9">
											<button class="btn btn-info" type="submit">
												<i class="ace-icon fa fa-check bigger-110"></i>
												Save Payment
											</button>

											&nbsp; &nbsp; &nbsp;
											<a class="btn" href="payments.php">
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
