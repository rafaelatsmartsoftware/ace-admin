<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Service Form - Ace Admin';
$pageDescription = 'add or edit service';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$currentUser = current_user();
$isAdminUser = ($currentUser['role'] ?? '') === 'admin';

if (!$isAdminUser) {
	header('Location: services.php?error=permission_denied');
	exit;
}

$serviceId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $serviceId > 0;
$service = [
	'id' => 0,
	'service_category_id' => 0,
	'outlet_id' => 0,
	'service_name' => '',
	'service_slug' => '',
	'description' => '',
	'duration_minutes' => '',
	'price' => '',
];
$categories = [];
$outlets = [];
$loadError = '';
$formErrors = [
	'invalid' => 'Please complete the required fields.',
	'duplicate_slug' => 'That service slug is already in use.',
	'service_not_found' => 'Service not found.',
	'database' => 'Unable to save the service. Please try again.',
	'permission_denied' => 'You do not have permission to manage services.',
];
$formError = $formErrors[$_GET['error'] ?? ''] ?? '';
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$loadError = 'Unable to load the service form right now. Please check the database connection.';
} else {
	try {
		$categoryStatement = $pdo->query(
			'SELECT id, category_name FROM service_categories ORDER BY display_order ASC, category_name ASC'
		);
		$categories = $categoryStatement->fetchAll();

		$outletStatement = $pdo->query(
			'SELECT id, branch_name FROM branches ORDER BY branch_name ASC'
		);
		$outlets = $outletStatement->fetchAll();

		if ($isEdit) {
			$statement = $pdo->prepare(
				'SELECT id, service_category_id, outlet_id, service_name, service_slug, description, duration_minutes, price
				FROM services
				WHERE id = :id
				LIMIT 1'
			);
			$statement->execute(['id' => $serviceId]);
			$loadedService = $statement->fetch();

			if (!$loadedService) {
				$loadError = 'Service not found.';
			} else {
				$service = array_merge($service, $loadedService);
			}
		}
	} catch (PDOException $exception) {
		error_log('Service form load failed: ' . $exception->getMessage());
		$loadError = 'Unable to load the service right now. Please try again later.';
	}
}

function service_form_escape($value): string
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
								<a href="services.php">Services</a>
							</li>
							<li class="active"><?php echo $isEdit ? 'Edit Service' : 'Add Service'; ?></li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								<?php echo $isEdit ? 'Edit Service' : 'Add Service'; ?>
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									services management
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($loadError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo service_form_escape($loadError); ?>
								</div>
<?php else: ?>
<?php if ($formError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo service_form_escape($formError); ?>
								</div>
<?php endif; ?>
<?php if (empty($categories) || empty($outlets)): ?>
								<div class="alert alert-warning">
									<i class="ace-icon fa fa-info-circle"></i>
									Please add at least one service category and one outlet before creating services.
								</div>
<?php endif; ?>
								<form class="form-horizontal" role="form" method="POST" action="service_save.php">
									<input type="hidden" name="id" value="<?php echo service_form_escape($service['id'] ?? 0); ?>" />

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="service_name">Service Name</label>

										<div class="col-sm-9">
											<input type="text" id="service_name" name="service_name" class="col-xs-10 col-sm-5" value="<?php echo service_form_escape($service['service_name'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="service_slug">Slug</label>

										<div class="col-sm-9">
											<input type="text" id="service_slug" name="service_slug" class="col-xs-10 col-sm-5" value="<?php echo service_form_escape($service['service_slug'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="service_category_id">Category</label>

										<div class="col-sm-9">
											<select id="service_category_id" name="service_category_id" class="col-xs-10 col-sm-5">
												<option value="">Select category</option>
<?php foreach ($categories as $category): ?>
<?php $categoryId = (int) ($category['id'] ?? 0); ?>
												<option value="<?php echo service_form_escape($categoryId); ?>"<?php echo ((int) ($service['service_category_id'] ?? 0) === $categoryId) ? ' selected="selected"' : ''; ?>><?php echo service_form_escape($category['category_name'] ?? ''); ?></option>
<?php endforeach; ?>
											</select>
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
												<option value="<?php echo service_form_escape($outletId); ?>"<?php echo ((int) ($service['outlet_id'] ?? 0) === $outletId) ? ' selected="selected"' : ''; ?>><?php echo service_form_escape($outlet['branch_name'] ?? ''); ?></option>
<?php endforeach; ?>
											</select>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="description">Description</label>

										<div class="col-sm-9">
											<textarea id="description" name="description" class="col-xs-10 col-sm-5" rows="4"><?php echo service_form_escape($service['description'] ?? ''); ?></textarea>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="duration_minutes">Duration Minutes</label>

										<div class="col-sm-9">
											<input type="number" id="duration_minutes" name="duration_minutes" min="1" class="col-xs-10 col-sm-5" value="<?php echo service_form_escape($service['duration_minutes'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="price">Price</label>

										<div class="col-sm-9">
											<input type="number" id="price" name="price" min="0" step="0.01" class="col-xs-10 col-sm-5" value="<?php echo service_form_escape($service['price'] ?? ''); ?>" />
										</div>
									</div>

									<div class="clearfix form-actions">
										<div class="col-md-offset-3 col-md-9">
											<button class="btn btn-info" type="submit">
												<i class="ace-icon fa fa-check bigger-110"></i>
												Save Service
											</button>

											&nbsp; &nbsp; &nbsp;
											<a class="btn" href="services.php">
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
