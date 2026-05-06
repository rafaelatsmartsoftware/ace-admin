<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Bookings - Ace Admin';
$pageDescription = 'appointments management';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$bookings = [];
$databaseError = '';
$successMessages = [
	'booking_created' => 'Booking created successfully.',
	'booking_updated' => 'Booking updated successfully.',
];
$errorMessages = [
	'invalid_request' => 'Invalid request.',
	'booking_not_found' => 'Booking not found.',
	'database' => 'Unable to complete the request. Please try again.',
	'permission_denied' => 'You do not have permission to manage bookings.',
];
$successMessage = $successMessages[$_GET['success'] ?? ''] ?? '';
$errorMessage = $errorMessages[$_GET['error'] ?? ''] ?? '';
$searchQuery = trim((string) ($_GET['search'] ?? ''));
$currentUser = current_user();
$currentRole = $currentUser['role'] ?? '';
$canManageBookings = in_array($currentRole, ['admin', 'manager'], true);
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$databaseError = 'Unable to load bookings right now. Please check the database connection.';
} else {
	try {
		$baseQuery = 'SELECT bookings.id, bookings.booking_type, bookings.guest_name, bookings.guest_phone,
				bookings.guest_email, bookings.appointment_date, bookings.appointment_time, bookings.booking_status,
				bookings.payment_method, bookings.notes, bookings.created_at, bookings.updated_at,
				customers.customer_name, customers.phone AS customer_phone, customers.email AS customer_email,
				branches.branch_name, services.service_name, employees.employee_name
			FROM bookings
			LEFT JOIN customers ON customers.id = bookings.customer_id
			INNER JOIN branches ON branches.id = bookings.outlet_id
			INNER JOIN services ON services.id = bookings.service_id
			LEFT JOIN employees ON employees.id = bookings.employee_id';

		if ($searchQuery !== '') {
			$statement = $pdo->prepare(
				$baseQuery . '
				WHERE bookings.guest_name LIKE :search_guest_name
					OR bookings.guest_phone LIKE :search_guest_phone
					OR bookings.guest_email LIKE :search_guest_email
					OR customers.customer_name LIKE :search_customer_name
					OR branches.branch_name LIKE :search_branch_name
					OR services.service_name LIKE :search_service_name
					OR employees.employee_name LIKE :search_employee_name
					OR bookings.booking_status LIKE :search_booking_status
					OR bookings.payment_method LIKE :search_payment_method
					OR CAST(bookings.appointment_date AS CHAR) LIKE :search_appointment_date
				ORDER BY bookings.appointment_date DESC, bookings.appointment_time DESC, bookings.id DESC'
			);
			$searchTerm = '%' . $searchQuery . '%';
			$statement->bindValue(':search_guest_name', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_guest_phone', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_guest_email', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_customer_name', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_branch_name', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_service_name', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_employee_name', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_booking_status', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_payment_method', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_appointment_date', $searchTerm, PDO::PARAM_STR);
			$statement->execute();
		} else {
			$statement = $pdo->query(
				$baseQuery . '
				ORDER BY bookings.appointment_date DESC, bookings.appointment_time DESC, bookings.id DESC'
			);
		}

		$bookings = $statement->fetchAll();
	} catch (PDOException $exception) {
		error_log('Bookings query failed: ' . $exception->getMessage());
		$databaseError = 'Unable to load bookings right now. Please try again later.';
	}
}

function bookings_escape($value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function bookings_display($value): string
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
							<li class="active">Bookings</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Bookings
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									appointments management
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($successMessage !== ''): ?>
								<div class="alert alert-success">
									<i class="ace-icon fa fa-check"></i>
									<?php echo bookings_escape($successMessage); ?>
								</div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo bookings_escape($errorMessage); ?>
								</div>
<?php endif; ?>
<?php if ($searchQuery !== ''): ?>
								<div class="alert alert-info">
									<i class="ace-icon fa fa-search"></i>
									Search results for: "<?php echo bookings_escape($searchQuery); ?>"
								</div>
<?php endif; ?>

								<div class="clearfix">
									<form class="form-search pull-left" method="GET" action="bookings.php">
										<span class="input-icon">
											<input type="text" name="search" class="nav-search-input" placeholder="Search bookings ..." value="<?php echo bookings_escape($searchQuery); ?>" autocomplete="off" />
											<i class="ace-icon fa fa-search nav-search-icon"></i>
										</span>
									</form>

<?php if ($canManageBookings): ?>
									<a href="booking_form.php" class="btn btn-sm btn-primary pull-right">
										<i class="ace-icon fa fa-plus"></i>
										Add Booking
									</a>
<?php endif; ?>
								</div>

								<div class="space-6"></div>

<?php if ($databaseError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo bookings_escape($databaseError); ?>
								</div>
<?php else: ?>
								<div class="table-responsive">
									<table id="bookings-table" class="table table-bordered table-hover">
										<thead>
											<tr>
												<th>ID</th>
												<th>Booking Type</th>
												<th>Customer/Guest Name</th>
												<th>Phone</th>
												<th>Outlet</th>
												<th>Service</th>
												<th>Employee</th>
												<th>Appointment Date</th>
												<th>Appointment Time</th>
												<th>Booking Status</th>
												<th>Payment Method</th>
												<th>Created At</th>
												<th>Updated At</th>
												<th>Actions</th>
											</tr>
										</thead>

										<tbody>
<?php if (empty($bookings)): ?>
											<tr>
												<td colspan="14" class="center">No bookings found.</td>
											</tr>
<?php else: ?>
<?php foreach ($bookings as $booking): ?>
<?php
	$bookingId = (int) ($booking['id'] ?? 0);
	$bookingType = (string) ($booking['booking_type'] ?? '');
	$displayName = $bookingType === 'registered' ? ($booking['customer_name'] ?? '') : ($booking['guest_name'] ?? '');
	$displayPhone = $bookingType === 'registered' ? ($booking['customer_phone'] ?? '') : ($booking['guest_phone'] ?? '');
	$status = (string) ($booking['booking_status'] ?? '');
	$statusClass = [
		'pending' => 'label-warning',
		'confirmed' => 'label-info',
		'completed' => 'label-success',
		'cancelled' => 'label-default',
	][$status] ?? 'label-default';
?>
											<tr>
												<td><?php echo bookings_escape($bookingId); ?></td>
												<td><?php echo bookings_escape(bookings_display($bookingType)); ?></td>
												<td>
													<strong><?php echo bookings_escape(bookings_display($displayName)); ?></strong>
<?php if (trim((string) ($booking['notes'] ?? '')) !== ''): ?>
													<div class="space-2"></div>
													<?php echo nl2br(bookings_escape($booking['notes'])); ?>
<?php endif; ?>
												</td>
												<td><?php echo bookings_escape(bookings_display($displayPhone)); ?></td>
												<td><?php echo bookings_escape(bookings_display($booking['branch_name'] ?? '')); ?></td>
												<td><?php echo bookings_escape(bookings_display($booking['service_name'] ?? '')); ?></td>
												<td><?php echo bookings_escape(bookings_display($booking['employee_name'] ?? '')); ?></td>
												<td><?php echo bookings_escape(bookings_display($booking['appointment_date'] ?? '')); ?></td>
												<td><?php echo bookings_escape(bookings_display($booking['appointment_time'] ?? '')); ?></td>
												<td>
													<span class="label label-sm <?php echo $statusClass; ?>">
														<?php echo bookings_escape(bookings_display($status)); ?>
													</span>
												</td>
												<td><?php echo bookings_escape(bookings_display($booking['payment_method'] ?? '')); ?></td>
												<td><?php echo bookings_escape(bookings_display($booking['created_at'] ?? '')); ?></td>
												<td><?php echo bookings_escape(bookings_display($booking['updated_at'] ?? '')); ?></td>
												<td>
<?php if ($canManageBookings): ?>
													<a href="booking_form.php?id=<?php echo bookings_escape($bookingId); ?>" class="btn btn-xs btn-info">
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
