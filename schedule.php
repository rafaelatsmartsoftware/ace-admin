<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Schedule - Ace Admin';
$pageDescription = 'appointments calendar view';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$appointments = [];
$outlets = [];
$employees = [];
$databaseError = '';
$dateFilter = trim((string) ($_GET['appointment_date'] ?? ''));
$outletFilter = isset($_GET['outlet_id']) ? (int) $_GET['outlet_id'] : 0;
$employeeFilter = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;
$statusFilter = trim((string) ($_GET['booking_status'] ?? ''));
$allowedStatuses = ['', 'pending', 'confirmed', 'completed', 'cancelled'];
$today = date('Y-m-d');

if (!in_array($statusFilter, $allowedStatuses, true)) {
	$statusFilter = '';
}

$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$databaseError = 'Unable to load schedule right now. Please check the database connection.';
} else {
	try {
		$outletStatement = $pdo->query('SELECT id, branch_name FROM branches ORDER BY branch_name ASC');
		$outlets = $outletStatement->fetchAll();

		$employeeStatement = $pdo->query('SELECT id, employee_name FROM employees ORDER BY employee_name ASC');
		$employees = $employeeStatement->fetchAll();

		$where = [];
		$params = [];

		if ($dateFilter !== '') {
			$where[] = 'bookings.appointment_date = :appointment_date';
			$params['appointment_date'] = $dateFilter;
		}

		if ($outletFilter > 0) {
			$where[] = 'bookings.outlet_id = :outlet_id';
			$params['outlet_id'] = $outletFilter;
		}

		if ($employeeFilter > 0) {
			$where[] = 'bookings.employee_id = :employee_id';
			$params['employee_id'] = $employeeFilter;
		}

		if ($statusFilter !== '') {
			$where[] = 'bookings.booking_status = :booking_status';
			$params['booking_status'] = $statusFilter;
		}

		if (empty($where)) {
			$where[] = 'bookings.appointment_date >= :today';
			$params['today'] = $today;
		}

		$sql = 'SELECT bookings.id, bookings.booking_type, bookings.guest_name, bookings.guest_phone,
				bookings.guest_email, bookings.appointment_date, bookings.appointment_time, bookings.booking_status,
				bookings.payment_method, bookings.notes,
				customers.customer_name, customers.phone AS customer_phone,
				branches.branch_name, services.service_name, employees.employee_name
			FROM bookings
			LEFT JOIN customers ON customers.id = bookings.customer_id
			INNER JOIN branches ON branches.id = bookings.outlet_id
			INNER JOIN services ON services.id = bookings.service_id
			LEFT JOIN employees ON employees.id = bookings.employee_id
			WHERE ' . implode(' AND ', $where) . '
			ORDER BY bookings.appointment_date ASC, bookings.appointment_time ASC, bookings.id ASC';

		$statement = $pdo->prepare($sql);

		foreach ($params as $key => $value) {
			$statement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}

		$statement->execute();
		$appointments = $statement->fetchAll();
	} catch (PDOException $exception) {
		error_log('Schedule query failed: ' . $exception->getMessage());
		$databaseError = 'Unable to load schedule right now. Please try again later.';
	}
}

function schedule_escape($value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function schedule_display($value): string
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
							<li class="active">Schedule</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Schedule
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									appointments calendar view
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($databaseError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo schedule_escape($databaseError); ?>
								</div>
<?php else: ?>
								<div class="widget-box">
									<div class="widget-header">
										<h4 class="widget-title">
											<i class="ace-icon fa fa-filter"></i>
											Filters
										</h4>
									</div>

									<div class="widget-body">
										<div class="widget-main">
											<form class="form-inline" method="GET" action="schedule.php">
												<label class="inline">
													Date
													<input type="date" name="appointment_date" class="input-small" value="<?php echo schedule_escape($dateFilter); ?>" />
												</label>

												&nbsp;
												<label class="inline">
													Outlet
													<select name="outlet_id" class="input-medium">
														<option value="">All outlets</option>
<?php foreach ($outlets as $outlet): ?>
<?php $outletId = (int) ($outlet['id'] ?? 0); ?>
														<option value="<?php echo schedule_escape($outletId); ?>"<?php echo $outletFilter === $outletId ? ' selected="selected"' : ''; ?>><?php echo schedule_escape($outlet['branch_name'] ?? ''); ?></option>
<?php endforeach; ?>
													</select>
												</label>

												&nbsp;
												<label class="inline">
													Employee
													<select name="employee_id" class="input-medium">
														<option value="">All employees</option>
<?php foreach ($employees as $employee): ?>
<?php $employeeId = (int) ($employee['id'] ?? 0); ?>
														<option value="<?php echo schedule_escape($employeeId); ?>"<?php echo $employeeFilter === $employeeId ? ' selected="selected"' : ''; ?>><?php echo schedule_escape($employee['employee_name'] ?? ''); ?></option>
<?php endforeach; ?>
													</select>
												</label>

												&nbsp;
												<label class="inline">
													Status
													<select name="booking_status" class="input-medium">
														<option value="">All statuses</option>
<?php foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $statusOption): ?>
														<option value="<?php echo schedule_escape($statusOption); ?>"<?php echo $statusFilter === $statusOption ? ' selected="selected"' : ''; ?>><?php echo schedule_escape($statusOption); ?></option>
<?php endforeach; ?>
													</select>
												</label>

												&nbsp;
												<button class="btn btn-sm btn-primary" type="submit">
													<i class="ace-icon fa fa-search"></i>
													Filter
												</button>

												<a class="btn btn-sm" href="schedule.php">
													<i class="ace-icon fa fa-undo"></i>
													Reset
												</a>
											</form>
										</div>
									</div>
								</div>

								<div class="space-12"></div>

								<div class="table-responsive">
									<table id="schedule-table" class="table table-bordered table-hover">
										<thead>
											<tr>
												<th>Appointment Date</th>
												<th>Appointment Time</th>
												<th>Customer / Guest Name</th>
												<th>Phone</th>
												<th>Outlet</th>
												<th>Service</th>
												<th>Employee</th>
												<th>Booking Status</th>
												<th>Payment Method</th>
												<th>Notes</th>
											</tr>
										</thead>

										<tbody>
<?php if (empty($appointments)): ?>
											<tr>
												<td colspan="10" class="center">No appointments found.</td>
											</tr>
<?php else: ?>
<?php foreach ($appointments as $appointment): ?>
<?php
	$bookingType = (string) ($appointment['booking_type'] ?? '');
	$displayName = $bookingType === 'registered' ? ($appointment['customer_name'] ?? '') : ($appointment['guest_name'] ?? '');
	$displayPhone = $bookingType === 'registered' ? ($appointment['customer_phone'] ?? '') : ($appointment['guest_phone'] ?? '');
	$status = (string) ($appointment['booking_status'] ?? '');
	$statusClass = [
		'pending' => 'label-warning',
		'confirmed' => 'label-info',
		'completed' => 'label-success',
		'cancelled' => 'label-default',
	][$status] ?? 'label-default';
?>
											<tr>
												<td><?php echo schedule_escape(schedule_display($appointment['appointment_date'] ?? '')); ?></td>
												<td><?php echo schedule_escape(schedule_display($appointment['appointment_time'] ?? '')); ?></td>
												<td><?php echo schedule_escape(schedule_display($displayName)); ?></td>
												<td><?php echo schedule_escape(schedule_display($displayPhone)); ?></td>
												<td><?php echo schedule_escape(schedule_display($appointment['branch_name'] ?? '')); ?></td>
												<td><?php echo schedule_escape(schedule_display($appointment['service_name'] ?? '')); ?></td>
												<td><?php echo schedule_escape(schedule_display($appointment['employee_name'] ?? '')); ?></td>
												<td>
													<span class="label label-sm <?php echo $statusClass; ?>">
														<?php echo schedule_escape(schedule_display($status)); ?>
													</span>
												</td>
												<td><?php echo schedule_escape(schedule_display($appointment['payment_method'] ?? '')); ?></td>
												<td><?php echo nl2br(schedule_escape(schedule_display($appointment['notes'] ?? ''))); ?></td>
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
