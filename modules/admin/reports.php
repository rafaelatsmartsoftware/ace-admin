<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

$currentUser = current_user();
$currentRole = $currentUser['role'] ?? '';

if (!in_array($currentRole, ['admin', 'manager'], true)) {
	header('Location: index.php');
	exit;
}

$pageTitle = 'Reports - Ace Admin';
$pageDescription = 'business summaries';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$reportStats = [
	'total_bookings' => 0,
	'pending_bookings' => 0,
	'confirmed_bookings' => 0,
	'completed_bookings' => 0,
	'cancelled_bookings' => 0,
	'total_payment_collected' => 0,
	'total_due_amount' => 0,
	'total_customers' => 0,
	'total_employees' => 0,
	'total_inventory_items' => 0,
];
$bookingsByOutlet = [];
$popularServices = [];
$recentPayments = [];
$inventoryByOutlet = [];
$databaseError = '';
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$databaseError = 'Unable to load reports right now. Please check the database connection.';
} else {
	try {
		$bookingStats = $pdo->query(
			"SELECT
				COUNT(*) AS total_bookings,
				COALESCE(SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_bookings,
				COALESCE(SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END), 0) AS confirmed_bookings,
				COALESCE(SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_bookings,
				COALESCE(SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_bookings
			FROM bookings"
		)->fetch();

		if ($bookingStats) {
			$reportStats = array_merge($reportStats, $bookingStats);
		}

		$paymentStats = $pdo->query(
			'SELECT
				COALESCE(SUM(paid_amount), 0) AS total_payment_collected,
				COALESCE(SUM(due_amount), 0) AS total_due_amount
			FROM payments'
		)->fetch();

		if ($paymentStats) {
			$reportStats = array_merge($reportStats, $paymentStats);
		}

		$reportStats['total_customers'] = (int) $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
		$reportStats['total_employees'] = (int) $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn();
		$reportStats['total_inventory_items'] = (int) $pdo->query('SELECT COUNT(*) FROM inventory_items')->fetchColumn();

		$bookingsByOutlet = $pdo->query(
			'SELECT branches.branch_name, COUNT(bookings.id) AS total_bookings
			FROM branches
			LEFT JOIN bookings ON bookings.outlet_id = branches.id
			GROUP BY branches.id, branches.branch_name
			ORDER BY total_bookings DESC, branches.branch_name ASC'
		)->fetchAll();

		$popularServices = $pdo->query(
			'SELECT services.service_name, COUNT(bookings.id) AS total_bookings
			FROM services
			LEFT JOIN bookings ON bookings.service_id = services.id
			GROUP BY services.id, services.service_name
			ORDER BY total_bookings DESC, services.service_name ASC
			LIMIT 10'
		)->fetchAll();

		$recentPayments = $pdo->query(
			'SELECT payments.invoice_number, payments.paid_amount, payments.due_amount,
				payments.payment_status, payments.payment_date,
				bookings.booking_type, bookings.guest_name, customers.customer_name
			FROM payments
			INNER JOIN bookings ON bookings.id = payments.booking_id
			LEFT JOIN customers ON customers.id = bookings.customer_id
			ORDER BY payments.created_at DESC, payments.id DESC
			LIMIT 10'
		)->fetchAll();

		$inventoryByOutlet = $pdo->query(
			'SELECT branches.branch_name, COUNT(inventory_items.id) AS total_item_types,
				COALESCE(SUM(inventory_items.quantity), 0) AS total_quantity
			FROM branches
			LEFT JOIN inventory_items ON inventory_items.outlet_id = branches.id
			GROUP BY branches.id, branches.branch_name
			ORDER BY total_item_types DESC, branches.branch_name ASC'
		)->fetchAll();
	} catch (PDOException $exception) {
		error_log('Reports query failed: ' . $exception->getMessage());
		$databaseError = 'Unable to load reports right now. Please try again later.';
	}
}

function reports_escape($value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function reports_display($value): string
{
	$value = trim((string) $value);

	return $value !== '' ? $value : '-';
}

function reports_money($value): string
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
							<li class="active">Reports</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Reports
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									business summaries
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($databaseError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo reports_escape($databaseError); ?>
								</div>
<?php else: ?>
								<div class="row">
									<div class="col-xs-12 infobox-container">
										<div class="infobox infobox-blue">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-calendar-check-o"></i>
											</div>
											<div class="infobox-data">
												<span class="infobox-data-number"><?php echo reports_escape(number_format((int) $reportStats['total_bookings'])); ?></span>
												<div class="infobox-content">Total Bookings</div>
											</div>
										</div>

										<div class="infobox infobox-orange">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-clock-o"></i>
											</div>
											<div class="infobox-data">
												<span class="infobox-data-number"><?php echo reports_escape(number_format((int) $reportStats['pending_bookings'])); ?></span>
												<div class="infobox-content">Pending Bookings</div>
											</div>
										</div>

										<div class="infobox infobox-purple">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-check-square-o"></i>
											</div>
											<div class="infobox-data">
												<span class="infobox-data-number"><?php echo reports_escape(number_format((int) $reportStats['confirmed_bookings'])); ?></span>
												<div class="infobox-content">Confirmed Bookings</div>
											</div>
										</div>

										<div class="infobox infobox-green">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-check-circle"></i>
											</div>
											<div class="infobox-data">
												<span class="infobox-data-number"><?php echo reports_escape(number_format((int) $reportStats['completed_bookings'])); ?></span>
												<div class="infobox-content">Completed Bookings</div>
											</div>
										</div>

										<div class="infobox infobox-red">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-ban"></i>
											</div>
											<div class="infobox-data">
												<span class="infobox-data-number"><?php echo reports_escape(number_format((int) $reportStats['cancelled_bookings'])); ?></span>
												<div class="infobox-content">Cancelled Bookings</div>
											</div>
										</div>

										<div class="infobox infobox-green">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-money"></i>
											</div>
											<div class="infobox-data">
												<span class="infobox-data-number"><?php echo reports_escape(reports_money($reportStats['total_payment_collected'])); ?></span>
												<div class="infobox-content">Payment Collected</div>
											</div>
										</div>

										<div class="infobox infobox-red">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-exclamation-circle"></i>
											</div>
											<div class="infobox-data">
												<span class="infobox-data-number"><?php echo reports_escape(reports_money($reportStats['total_due_amount'])); ?></span>
												<div class="infobox-content">Total Due Amount</div>
											</div>
										</div>

										<div class="infobox infobox-pink">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-smile-o"></i>
											</div>
											<div class="infobox-data">
												<span class="infobox-data-number"><?php echo reports_escape(number_format((int) $reportStats['total_customers'])); ?></span>
												<div class="infobox-content">Total Customers</div>
											</div>
										</div>

										<div class="infobox infobox-blue">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-user-md"></i>
											</div>
											<div class="infobox-data">
												<span class="infobox-data-number"><?php echo reports_escape(number_format((int) $reportStats['total_employees'])); ?></span>
												<div class="infobox-content">Total Employees</div>
											</div>
										</div>

										<div class="infobox infobox-grey">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-cubes"></i>
											</div>
											<div class="infobox-data">
												<span class="infobox-data-number"><?php echo reports_escape(number_format((int) $reportStats['total_inventory_items'])); ?></span>
												<div class="infobox-content">Inventory Items</div>
											</div>
										</div>
									</div>
								</div>

								<div class="space-12"></div>

								<div class="row">
									<div class="col-sm-6">
										<div class="widget-box">
											<div class="widget-header">
												<h4 class="widget-title">
													<i class="ace-icon fa fa-map-marker"></i>
													Bookings by Outlet
												</h4>
											</div>

											<div class="widget-body">
												<div class="widget-main no-padding">
													<table class="table table-bordered table-hover">
														<thead>
															<tr>
																<th>Outlet Name</th>
																<th>Total Bookings</th>
															</tr>
														</thead>
														<tbody>
<?php if (empty($bookingsByOutlet)): ?>
															<tr>
																<td colspan="2" class="center">No outlet booking data found.</td>
															</tr>
<?php else: ?>
<?php foreach ($bookingsByOutlet as $row): ?>
															<tr>
																<td><?php echo reports_escape(reports_display($row['branch_name'] ?? '')); ?></td>
																<td><?php echo reports_escape(number_format((int) ($row['total_bookings'] ?? 0))); ?></td>
															</tr>
<?php endforeach; ?>
<?php endif; ?>
														</tbody>
													</table>
												</div>
											</div>
										</div>
									</div>

									<div class="col-sm-6">
										<div class="widget-box">
											<div class="widget-header">
												<h4 class="widget-title">
													<i class="ace-icon fa fa-scissors"></i>
													Popular Services
												</h4>
											</div>

											<div class="widget-body">
												<div class="widget-main no-padding">
													<table class="table table-bordered table-hover">
														<thead>
															<tr>
																<th>Service Name</th>
																<th>Number of Bookings</th>
															</tr>
														</thead>
														<tbody>
<?php if (empty($popularServices)): ?>
															<tr>
																<td colspan="2" class="center">No service booking data found.</td>
															</tr>
<?php else: ?>
<?php foreach ($popularServices as $row): ?>
															<tr>
																<td><?php echo reports_escape(reports_display($row['service_name'] ?? '')); ?></td>
																<td><?php echo reports_escape(number_format((int) ($row['total_bookings'] ?? 0))); ?></td>
															</tr>
<?php endforeach; ?>
<?php endif; ?>
														</tbody>
													</table>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="space-12"></div>

								<div class="row">
									<div class="col-sm-7">
										<div class="widget-box">
											<div class="widget-header">
												<h4 class="widget-title">
													<i class="ace-icon fa fa-credit-card"></i>
													Recent Payments
												</h4>
											</div>

											<div class="widget-body">
												<div class="widget-main no-padding">
													<table class="table table-bordered table-hover">
														<thead>
															<tr>
																<th>Invoice Number</th>
																<th>Customer / Guest</th>
																<th>Paid Amount</th>
																<th>Due Amount</th>
																<th>Payment Status</th>
																<th>Payment Date</th>
															</tr>
														</thead>
														<tbody>
<?php if (empty($recentPayments)): ?>
															<tr>
																<td colspan="6" class="center">No recent payments found.</td>
															</tr>
<?php else: ?>
<?php foreach ($recentPayments as $payment): ?>
<?php
	$customerName = ($payment['booking_type'] ?? '') === 'registered' ? ($payment['customer_name'] ?? '') : ($payment['guest_name'] ?? '');
	$paymentStatus = (string) ($payment['payment_status'] ?? '');
	$statusClass = [
		'unpaid' => 'label-warning',
		'partial' => 'label-info',
		'paid' => 'label-success',
	][$paymentStatus] ?? 'label-default';
?>
															<tr>
																<td><?php echo reports_escape(reports_display($payment['invoice_number'] ?? '')); ?></td>
																<td><?php echo reports_escape(reports_display($customerName)); ?></td>
																<td><?php echo reports_escape(reports_money($payment['paid_amount'] ?? 0)); ?></td>
																<td><?php echo reports_escape(reports_money($payment['due_amount'] ?? 0)); ?></td>
																<td>
																	<span class="label label-sm <?php echo $statusClass; ?>">
																		<?php echo reports_escape(reports_display($paymentStatus)); ?>
																	</span>
																</td>
																<td><?php echo reports_escape(reports_display($payment['payment_date'] ?? '')); ?></td>
															</tr>
<?php endforeach; ?>
<?php endif; ?>
														</tbody>
													</table>
												</div>
											</div>
										</div>
									</div>

									<div class="col-sm-5">
										<div class="widget-box">
											<div class="widget-header">
												<h4 class="widget-title">
													<i class="ace-icon fa fa-cubes"></i>
													Inventory by Outlet
												</h4>
											</div>

											<div class="widget-body">
												<div class="widget-main no-padding">
													<table class="table table-bordered table-hover">
														<thead>
															<tr>
																<th>Outlet Name</th>
																<th>Total Item Types</th>
																<th>Total Quantity</th>
															</tr>
														</thead>
														<tbody>
<?php if (empty($inventoryByOutlet)): ?>
															<tr>
																<td colspan="3" class="center">No inventory data found.</td>
															</tr>
<?php else: ?>
<?php foreach ($inventoryByOutlet as $row): ?>
															<tr>
																<td><?php echo reports_escape(reports_display($row['branch_name'] ?? '')); ?></td>
																<td><?php echo reports_escape(number_format((int) ($row['total_item_types'] ?? 0))); ?></td>
																<td><?php echo reports_escape(number_format((int) ($row['total_quantity'] ?? 0))); ?></td>
															</tr>
<?php endforeach; ?>
<?php endif; ?>
														</tbody>
													</table>
												</div>
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
