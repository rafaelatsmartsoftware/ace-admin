<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Services - Ace Admin';
$pageDescription = 'salon and spa services';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$services = [];
$databaseError = '';
$successMessages = [
	'service_created' => 'Service created successfully.',
	'service_updated' => 'Service updated successfully.',
	'service_deleted' => 'Service deleted successfully.',
];
$errorMessages = [
	'invalid_request' => 'Invalid request.',
	'service_not_found' => 'Service not found.',
	'database' => 'Unable to complete the request. Please try again.',
	'duplicate_slug' => 'That service slug is already in use.',
	'permission_denied' => 'You do not have permission to manage services.',
	'service_has_bookings' => 'Cannot delete this service because it is used in bookings.',
];
$successMessage = $successMessages[$_GET['success'] ?? ''] ?? '';
$errorMessage = $errorMessages[$_GET['error'] ?? ''] ?? '';
$searchQuery = trim((string) ($_GET['search'] ?? ''));
$currentUser = current_user();
$isAdminUser = ($currentUser['role'] ?? '') === 'admin';
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$databaseError = 'Unable to load services right now. Please check the database connection.';
} else {
	try {
		if ($searchQuery !== '') {
			$statement = $pdo->prepare(
				'SELECT services.id, services.service_name, services.service_slug, services.description,
					services.duration_minutes, services.price, services.created_at, services.updated_at,
					service_categories.category_name, branches.branch_name
				FROM services
				INNER JOIN service_categories ON service_categories.id = services.service_category_id
				INNER JOIN branches ON branches.id = services.outlet_id
				WHERE services.service_name LIKE :search_service_name
					OR services.service_slug LIKE :search_service_slug
					OR services.description LIKE :search_description
					OR service_categories.category_name LIKE :search_category_name
					OR branches.branch_name LIKE :search_branch_name
				ORDER BY services.created_at DESC, services.id DESC'
			);
			$searchTerm = '%' . $searchQuery . '%';
			$statement->bindValue(':search_service_name', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_service_slug', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_description', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_category_name', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_branch_name', $searchTerm, PDO::PARAM_STR);
			$statement->execute();
		} else {
			$statement = $pdo->query(
				'SELECT services.id, services.service_name, services.service_slug, services.description,
					services.duration_minutes, services.price, services.created_at, services.updated_at,
					service_categories.category_name, branches.branch_name
				FROM services
				INNER JOIN service_categories ON service_categories.id = services.service_category_id
				INNER JOIN branches ON branches.id = services.outlet_id
				ORDER BY services.created_at DESC, services.id DESC'
			);
		}

		$services = $statement->fetchAll();
	} catch (PDOException $exception) {
		error_log('Services query failed: ' . $exception->getMessage());
		$databaseError = 'Unable to load services right now. Please try again later.';
	}
}

function services_escape($value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function services_display($value): string
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
							<li class="active">Services</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Services
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									salon and spa services
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($successMessage !== ''): ?>
								<div class="alert alert-success">
									<i class="ace-icon fa fa-check"></i>
									<?php echo services_escape($successMessage); ?>
								</div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo services_escape($errorMessage); ?>
								</div>
<?php endif; ?>
<?php if ($searchQuery !== ''): ?>
								<div class="alert alert-info">
									<i class="ace-icon fa fa-search"></i>
									Search results for: "<?php echo services_escape($searchQuery); ?>"
								</div>
<?php endif; ?>

								<div class="clearfix">
									<form class="form-search pull-left" method="GET" action="services.php">
										<span class="input-icon">
											<input type="text" name="search" class="nav-search-input" placeholder="Search services ..." value="<?php echo services_escape($searchQuery); ?>" autocomplete="off" />
											<i class="ace-icon fa fa-search nav-search-icon"></i>
										</span>
									</form>

<?php if ($isAdminUser): ?>
									<a href="service_form.php" class="btn btn-sm btn-primary pull-right">
										<i class="ace-icon fa fa-plus"></i>
										Add Service
									</a>
<?php endif; ?>
								</div>

								<div class="space-6"></div>

<?php if ($databaseError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo services_escape($databaseError); ?>
								</div>
<?php else: ?>
								<div class="table-responsive">
									<table id="services-table" class="table table-bordered table-hover">
										<thead>
											<tr>
												<th>ID</th>
												<th>Service Name</th>
												<th>Category</th>
												<th>Outlet</th>
												<th>Duration</th>
												<th>Price</th>
												<th>Created At</th>
												<th>Updated At</th>
												<th>Actions</th>
											</tr>
										</thead>

										<tbody>
<?php if (empty($services)): ?>
											<tr>
												<td colspan="9" class="center">No services found.</td>
											</tr>
<?php else: ?>
<?php foreach ($services as $service): ?>
<?php $serviceId = (int) ($service['id'] ?? 0); ?>
											<tr>
												<td><?php echo services_escape($serviceId); ?></td>
												<td>
													<strong><?php echo services_escape(services_display($service['service_name'] ?? '')); ?></strong>
													<div class="text-muted"><?php echo services_escape(services_display($service['service_slug'] ?? '')); ?></div>
<?php if (trim((string) ($service['description'] ?? '')) !== ''): ?>
													<div class="space-2"></div>
													<?php echo nl2br(services_escape($service['description'])); ?>
<?php endif; ?>
												</td>
												<td><?php echo services_escape(services_display($service['category_name'] ?? '')); ?></td>
												<td><?php echo services_escape(services_display($service['branch_name'] ?? '')); ?></td>
												<td><?php echo services_escape((int) ($service['duration_minutes'] ?? 0)); ?> min</td>
												<td><?php echo services_escape(number_format((float) ($service['price'] ?? 0), 2)); ?></td>
												<td><?php echo services_escape(services_display($service['created_at'] ?? '')); ?></td>
												<td><?php echo services_escape(services_display($service['updated_at'] ?? '')); ?></td>
												<td>
<?php if ($isAdminUser): ?>
													<a href="service_form.php?id=<?php echo services_escape($serviceId); ?>" class="btn btn-xs btn-info">
														<i class="ace-icon fa fa-pencil bigger-120"></i>
													</a>
													<form action="service_delete.php" method="POST" style="display:inline" onsubmit="return confirm('Delete this service permanently?');">
														<input type="hidden" name="id" value="<?php echo services_escape($serviceId); ?>" />
														<button type="submit" class="btn btn-xs btn-danger">
															<i class="ace-icon fa fa-trash-o bigger-120"></i>
														</button>
													</form>
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
