<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Booking Form - Ace Admin';
$pageDescription = 'add or edit appointment';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$currentUser = current_user();
$currentRole = $currentUser['role'] ?? '';
$canManageBookings = in_array($currentRole, ['admin', 'manager'], true);

if (!$canManageBookings) {
	header('Location: bookings.php?error=permission_denied');
	exit;
}

$bookingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $bookingId > 0;
$booking = [
	'id' => 0,
	'booking_type' => 'guest',
	'customer_id' => 0,
	'guest_name' => '',
	'guest_phone' => '',
	'guest_email' => '',
	'outlet_id' => 0,
	'service_id' => 0,
	'employee_id' => 0,
	'appointment_date' => '',
	'appointment_time' => '',
	'booking_status' => 'pending',
	'payment_method' => 'pay_at_salon',
	'notes' => '',
];
$customers = [];
$outlets = [];
$services = [];
$employees = [];
$loadError = '';
$formErrors = [
	'invalid' => 'Please complete the required fields.',
	'invalid_email' => 'Please enter a valid email address.',
	'booking_not_found' => 'Booking not found.',
	'database' => 'Unable to save the booking. Please try again.',
	'permission_denied' => 'You do not have permission to manage bookings.',
];
$formError = $formErrors[$_GET['error'] ?? ''] ?? '';
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$loadError = 'Unable to load the booking form right now. Please check the database connection.';
} else {
	try {
		$customerStatement = $pdo->query('SELECT id, customer_name, phone FROM customers ORDER BY customer_name ASC');
		$customers = $customerStatement->fetchAll();

		$outletStatement = $pdo->query('SELECT id, branch_name FROM branches ORDER BY branch_name ASC');
		$outlets = $outletStatement->fetchAll();

		$serviceStatement = $pdo->query('SELECT id, service_name FROM services ORDER BY service_name ASC');
		$services = $serviceStatement->fetchAll();

		$employeeStatement = $pdo->query('SELECT id, employee_name FROM employees ORDER BY employee_name ASC');
		$employees = $employeeStatement->fetchAll();

		if ($isEdit) {
			$statement = $pdo->prepare(
				'SELECT id, booking_type, customer_id, guest_name, guest_phone, guest_email, outlet_id,
					service_id, employee_id, appointment_date, appointment_time, booking_status, payment_method, notes
				FROM bookings
				WHERE id = :id
				LIMIT 1'
			);
			$statement->execute(['id' => $bookingId]);
			$loadedBooking = $statement->fetch();

			if (!$loadedBooking) {
				$loadError = 'Booking not found.';
			} else {
				$booking = array_merge($booking, $loadedBooking);
			}
		}
	} catch (PDOException $exception) {
		error_log('Booking form load failed: ' . $exception->getMessage());
		$loadError = 'Unable to load the booking right now. Please try again later.';
	}
}

function booking_form_escape($value): string
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
								<a href="bookings.php">Bookings</a>
							</li>
							<li class="active"><?php echo $isEdit ? 'Edit Booking' : 'Add Booking'; ?></li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								<?php echo $isEdit ? 'Edit Booking' : 'Add Booking'; ?>
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									appointments management
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($loadError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo booking_form_escape($loadError); ?>
								</div>
<?php else: ?>
<?php if ($formError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo booking_form_escape($formError); ?>
								</div>
<?php endif; ?>
<?php if (empty($outlets) || empty($services)): ?>
								<div class="alert alert-warning">
									<i class="ace-icon fa fa-info-circle"></i>
									Please add at least one outlet and one service before creating bookings.
								</div>
<?php endif; ?>
								<form class="form-horizontal" role="form" method="POST" action="booking_save.php">
									<input type="hidden" name="id" value="<?php echo booking_form_escape($booking['id'] ?? 0); ?>" />

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="booking_type">Booking Type</label>

										<div class="col-sm-9">
											<select id="booking_type" name="booking_type" class="col-xs-10 col-sm-5">
												<option value="guest"<?php echo (($booking['booking_type'] ?? '') === 'guest') ? ' selected="selected"' : ''; ?>>guest</option>
												<option value="registered"<?php echo (($booking['booking_type'] ?? '') === 'registered') ? ' selected="selected"' : ''; ?>>registered</option>
											</select>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="customer_id">Registered Customer</label>

										<div class="col-sm-9">
											<select id="customer_id" name="customer_id" class="col-xs-10 col-sm-5">
												<option value="">Select customer</option>
<?php foreach ($customers as $customer): ?>
<?php $customerId = (int) ($customer['id'] ?? 0); ?>
												<option value="<?php echo booking_form_escape($customerId); ?>"<?php echo ((int) ($booking['customer_id'] ?? 0) === $customerId) ? ' selected="selected"' : ''; ?>><?php echo booking_form_escape(($customer['customer_name'] ?? '') . ' - ' . ($customer['phone'] ?? '')); ?></option>
<?php endforeach; ?>
											</select>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="guest_name">Guest Name</label>

										<div class="col-sm-9">
											<input type="text" id="guest_name" name="guest_name" class="col-xs-10 col-sm-5" value="<?php echo booking_form_escape($booking['guest_name'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="guest_phone">Guest Phone</label>

										<div class="col-sm-9">
											<input type="text" id="guest_phone" name="guest_phone" class="col-xs-10 col-sm-5" value="<?php echo booking_form_escape($booking['guest_phone'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="guest_email">Guest Email</label>

										<div class="col-sm-9">
											<input type="email" id="guest_email" name="guest_email" class="col-xs-10 col-sm-5" value="<?php echo booking_form_escape($booking['guest_email'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="outlet_id">Outlet</label>

										<div class="col-sm-9">
											<select id="outlet_id" name="outlet_id" class="col-xs-10 col-sm-5">
												<option value="">Select outlet</option>
<?php foreach ($outlets as $outlet): ?>
<?php $outletId = (int) ($outlet['id'] ?? 0); ?>
												<option value="<?php echo booking_form_escape($outletId); ?>"<?php echo ((int) ($booking['outlet_id'] ?? 0) === $outletId) ? ' selected="selected"' : ''; ?>><?php echo booking_form_escape($outlet['branch_name'] ?? ''); ?></option>
<?php endforeach; ?>
											</select>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="service_id">Service</label>

										<div class="col-sm-9">
											<select id="service_id" name="service_id" class="col-xs-10 col-sm-5">
												<option value="">Select service</option>
<?php foreach ($services as $service): ?>
<?php $serviceId = (int) ($service['id'] ?? 0); ?>
												<option value="<?php echo booking_form_escape($serviceId); ?>"<?php echo ((int) ($booking['service_id'] ?? 0) === $serviceId) ? ' selected="selected"' : ''; ?>><?php echo booking_form_escape($service['service_name'] ?? ''); ?></option>
<?php endforeach; ?>
											</select>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="employee_id">Employee</label>

										<div class="col-sm-9">
											<select id="employee_id" name="employee_id" class="col-xs-10 col-sm-5">
												<option value="">No preference</option>
<?php foreach ($employees as $employee): ?>
<?php $employeeId = (int) ($employee['id'] ?? 0); ?>
												<option value="<?php echo booking_form_escape($employeeId); ?>"<?php echo ((int) ($booking['employee_id'] ?? 0) === $employeeId) ? ' selected="selected"' : ''; ?>><?php echo booking_form_escape($employee['employee_name'] ?? ''); ?></option>
<?php endforeach; ?>
											</select>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="appointment_date">Appointment Date</label>

										<div class="col-sm-9">
											<input type="date" id="appointment_date" name="appointment_date" class="col-xs-10 col-sm-5" value="<?php echo booking_form_escape($booking['appointment_date'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="appointment_time">Appointment Time</label>

										<div class="col-sm-9">
											<input type="time" id="appointment_time" name="appointment_time" class="col-xs-10 col-sm-5" value="<?php echo booking_form_escape($booking['appointment_time'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="booking_status">Booking Status</label>

										<div class="col-sm-9">
											<select id="booking_status" name="booking_status" class="col-xs-10 col-sm-5">
<?php foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $statusOption): ?>
												<option value="<?php echo booking_form_escape($statusOption); ?>"<?php echo (($booking['booking_status'] ?? '') === $statusOption) ? ' selected="selected"' : ''; ?>><?php echo booking_form_escape($statusOption); ?></option>
<?php endforeach; ?>
											</select>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="payment_method">Payment Method</label>

										<div class="col-sm-9">
											<input type="text" id="payment_method" name="payment_method" class="col-xs-10 col-sm-5" value="<?php echo booking_form_escape($booking['payment_method'] ?? 'pay_at_salon'); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="notes">Notes</label>

										<div class="col-sm-9">
											<textarea id="notes" name="notes" class="col-xs-10 col-sm-5" rows="4"><?php echo booking_form_escape($booking['notes'] ?? ''); ?></textarea>
										</div>
									</div>

									<div class="clearfix form-actions">
										<div class="col-md-offset-3 col-md-9">
											<button class="btn btn-info" type="submit">
												<i class="ace-icon fa fa-check bigger-110"></i>
												Save Booking
											</button>

											&nbsp; &nbsp; &nbsp;
											<a class="btn" href="bookings.php">
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
