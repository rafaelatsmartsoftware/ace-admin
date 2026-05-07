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
$branches = [];
$databaseError = '';
$successMessages = [
	'booking_created' => 'Booking created successfully.',
	'booking_updated' => 'Booking updated successfully.',
	'booking_status_updated' => 'Booking status updated successfully.',
];
$errorMessages = [
	'invalid_request' => 'Invalid request.',
	'booking_not_found' => 'Booking not found.',
	'database' => 'Unable to complete the request. Please try again.',
	'permission_denied' => 'You do not have permission to manage bookings.',
	'invalid_status' => 'Invalid booking status update.',
	'status_transition_not_allowed' => 'That booking status change is not allowed.',
];
$successMessage = $successMessages[$_GET['success'] ?? ''] ?? '';
$errorMessage = $errorMessages[$_GET['error'] ?? ''] ?? '';
$searchQuery = trim((string) ($_GET['search'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$outletFilter = isset($_GET['outlet_id']) ? (int) $_GET['outlet_id'] : 0;
$allowedStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];

if (!in_array($statusFilter, $allowedStatuses, true)) {
	$statusFilter = '';
}

if ($outletFilter < 0) {
	$outletFilter = 0;
}

$currentUser = current_user();
$currentRole = $currentUser['role'] ?? '';
$canManageBookings = in_array($currentRole, ['admin', 'manager'], true);
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$databaseError = 'Unable to load bookings right now. Please check the database connection.';
} else {
	try {
		$branchStatement = $pdo->query(
			'SELECT id, branch_name, area_city
			FROM branches
			ORDER BY branch_name ASC, id ASC'
		);
		$branches = $branchStatement->fetchAll();

		$whereClauses = [];
		$params = [];

		if ($searchQuery !== '') {
			$whereClauses[] = '(bookings.guest_name LIKE :search_guest_name
				OR bookings.guest_phone LIKE :search_guest_phone
				OR bookings.guest_email LIKE :search_guest_email
				OR customers.customer_name LIKE :search_customer_name
				OR branches.branch_name LIKE :search_branch_name
				OR services.service_name LIKE :search_service_name
				OR CAST(bookings.appointment_date AS CHAR) LIKE :search_appointment_date)';
			$searchTerm = '%' . $searchQuery . '%';
			$params['search_guest_name'] = $searchTerm;
			$params['search_guest_phone'] = $searchTerm;
			$params['search_guest_email'] = $searchTerm;
			$params['search_customer_name'] = $searchTerm;
			$params['search_branch_name'] = $searchTerm;
			$params['search_service_name'] = $searchTerm;
			$params['search_appointment_date'] = $searchTerm;
		}

		if ($statusFilter !== '') {
			$whereClauses[] = 'bookings.booking_status = :status';
			$params['status'] = $statusFilter;
		}

		if ($outletFilter > 0) {
			$whereClauses[] = 'bookings.outlet_id = :outlet_id';
			$params['outlet_id'] = $outletFilter;
		}

		$sql = 'SELECT bookings.id, bookings.booking_type, bookings.guest_name, bookings.guest_phone,
				bookings.appointment_date, bookings.appointment_time, bookings.booking_status,
				customers.customer_name, customers.phone AS customer_phone,
				branches.branch_name, branches.area_city, services.service_name
			FROM bookings
			LEFT JOIN customers ON customers.id = bookings.customer_id
			INNER JOIN branches ON branches.id = bookings.outlet_id
			INNER JOIN services ON services.id = bookings.service_id';

		if (!empty($whereClauses)) {
			$sql .= ' WHERE ' . implode(' AND ', $whereClauses);
		}

		$sql .= ' ORDER BY bookings.id DESC';

		$statement = $pdo->prepare($sql);

		foreach ($params as $key => $value) {
			$statement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}

		$statement->execute();
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

function bookings_branch_label(array $branch): string
{
	$branchName = trim((string) ($branch['branch_name'] ?? ''));
	$areaCity = trim((string) ($branch['area_city'] ?? ''));
	$label = $branchName !== '' ? $branchName : 'Branch';

	if ($areaCity !== '') {
		$label .= ' - ' . $areaCity;
	}

	return $label;
}

function bookings_status_badge_class(string $status): string
{
	return [
		'pending' => 'label-warning',
		'confirmed' => 'label-info',
		'completed' => 'label-success',
		'cancelled' => 'label-default',
	][$status] ?? 'label-default';
}

function bookings_allowed_transitions(string $status): array
{
	return [
		'pending' => ['confirmed', 'cancelled'],
		'confirmed' => ['completed', 'cancelled'],
		'completed' => [],
		'cancelled' => [],
	][$status] ?? [];
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
						<style>
							.booking-actions {
								display: flex;
								flex-wrap: wrap;
								gap: 6px;
								align-items: center;
							}

							.booking-actions form {
								margin: 0;
							}

							.booking-actions .btn {
								display: inline-flex;
								align-items: center;
								gap: 4px;
							}
						</style>
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
<?php if ($searchQuery !== '' || $statusFilter !== '' || $outletFilter > 0): ?>
								<div class="alert alert-info">
									<i class="ace-icon fa fa-search"></i>
									Showing filtered bookings results.
								</div>
<?php endif; ?>

								<div class="widget-box">
									<div class="widget-header">
										<h4 class="widget-title">
											<i class="ace-icon fa fa-filter"></i>
											Filters
										</h4>
									</div>

									<div class="widget-body">
										<div class="widget-main">
											<div class="clearfix">
												<form class="form-inline pull-left" method="GET" action="bookings.php">
													<label class="inline">
														Search
														<input type="text" name="search" class="input-large" placeholder="Search bookings ..." value="<?php echo bookings_escape($searchQuery); ?>" autocomplete="off" />
													</label>

													&nbsp;
													<label class="inline">
														Status
														<select name="status" class="input-medium">
															<option value="">All Statuses</option>
<?php foreach ($allowedStatuses as $statusOption): ?>
															<option value="<?php echo bookings_escape($statusOption); ?>"<?php echo $statusFilter === $statusOption ? ' selected="selected"' : ''; ?>><?php echo bookings_escape(ucfirst($statusOption)); ?></option>
<?php endforeach; ?>
														</select>
													</label>

													&nbsp;
													<label class="inline">
														Select Branch
														<select name="outlet_id" class="input-medium">
															<option value="">All Branches</option>
<?php foreach ($branches as $branch): ?>
<?php $branchId = (int) ($branch['id'] ?? 0); ?>
															<option value="<?php echo bookings_escape($branchId); ?>"<?php echo $outletFilter === $branchId ? ' selected="selected"' : ''; ?>><?php echo bookings_escape(bookings_branch_label($branch)); ?></option>
<?php endforeach; ?>
														</select>
													</label>

													&nbsp;
													<button class="btn btn-sm btn-primary" type="submit">
														<i class="ace-icon fa fa-search"></i>
														Filter
													</button>

													<a class="btn btn-sm" href="bookings.php">
														<i class="ace-icon fa fa-undo"></i>
														Reset
													</a>
												</form>

<?php if ($canManageBookings): ?>
												<a href="booking_form.php" class="btn btn-sm btn-primary pull-right">
													<i class="ace-icon fa fa-plus"></i>
													Add Booking
												</a>
<?php endif; ?>
											</div>
										</div>
									</div>
								</div>

								<div class="space-12"></div>

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
												<th>Customer / Guest Name</th>
												<th>Phone</th>
												<th>Branch</th>
												<th>Service</th>
												<th>Date</th>
												<th>Time</th>
												<th>Status</th>
												<th>Actions</th>
											</tr>
										</thead>

										<tbody>
<?php if (empty($bookings)): ?>
											<tr>
												<td colspan="9" class="center">No bookings found.</td>
											</tr>
<?php else: ?>
<?php foreach ($bookings as $booking): ?>
<?php
	$bookingId = (int) ($booking['id'] ?? 0);
	$bookingType = (string) ($booking['booking_type'] ?? '');
	$displayName = $bookingType === 'registered' ? ($booking['customer_name'] ?? '') : ($booking['guest_name'] ?? '');
	$displayPhone = $bookingType === 'registered' ? ($booking['customer_phone'] ?? '') : ($booking['guest_phone'] ?? '');
	$status = (string) ($booking['booking_status'] ?? '');
	$allowedTransitions = bookings_allowed_transitions($status);
	$canEditBooking = in_array($status, ['pending', 'confirmed'], true);
?>
											<tr>
												<td><?php echo bookings_escape($bookingId); ?></td>
												<td><strong><?php echo bookings_escape(bookings_display($displayName)); ?></strong></td>
												<td><?php echo bookings_escape(bookings_display($displayPhone)); ?></td>
												<td><?php echo bookings_escape(bookings_display(bookings_branch_label($booking))); ?></td>
												<td><?php echo bookings_escape(bookings_display($booking['service_name'] ?? '')); ?></td>
												<td><?php echo bookings_escape(bookings_display($booking['appointment_date'] ?? '')); ?></td>
												<td><?php echo bookings_escape(bookings_display($booking['appointment_time'] ?? '')); ?></td>
												<td>
													<span class="label label-sm <?php echo bookings_status_badge_class($status); ?>">
														<?php echo bookings_escape(bookings_display($status)); ?>
													</span>
												</td>
												<td>
<?php if ($canManageBookings): ?>
													<div class="booking-actions">
<?php if ($canEditBooking): ?>
													<a href="booking_form.php?id=<?php echo bookings_escape($bookingId); ?>" class="btn btn-xs btn-info" title="Edit Booking">
														<i class="ace-icon fa fa-pencil bigger-120"></i>
														<span>Edit</span>
													</a>
<?php endif; ?>
<?php foreach ($allowedTransitions as $nextStatus): ?>
<?php
	$buttonClass = $nextStatus === 'cancelled' ? 'btn-danger' : ($nextStatus === 'completed' ? 'btn-success' : 'btn-primary');
	$buttonLabel = ucfirst($nextStatus === 'confirmed' ? 'confirm' : ($nextStatus === 'completed' ? 'complete' : 'cancel'));
?>
													<form method="post" action="booking_status_update.php" class="inline">
														<input type="hidden" name="id" value="<?php echo bookings_escape($bookingId); ?>" />
														<input type="hidden" name="status" value="<?php echo bookings_escape($nextStatus); ?>" />
														<input type="hidden" name="return_search" value="<?php echo bookings_escape($searchQuery); ?>" />
														<input type="hidden" name="return_status" value="<?php echo bookings_escape($statusFilter); ?>" />
														<input type="hidden" name="return_outlet_id" value="<?php echo bookings_escape((string) $outletFilter); ?>" />
														<button type="submit" class="btn btn-xs <?php echo bookings_escape($buttonClass); ?>" onclick="return confirm('Are you sure you want to mark this booking as <?php echo bookings_escape($nextStatus); ?>?');">
															<i class="ace-icon fa <?php echo $nextStatus === 'cancelled' ? 'fa-times' : ($nextStatus === 'completed' ? 'fa-check-square-o' : 'fa-check'); ?> bigger-120"></i>
															<?php echo bookings_escape($buttonLabel); ?>
														</button>
													</form>
<?php endforeach; ?>
<?php if (empty($allowedTransitions)): ?>
													<span class="label label-sm <?php echo bookings_status_badge_class($status); ?>">
														<?php echo bookings_escape(ucfirst($status)); ?>
													</span>
<?php endif; ?>
													</div>
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
