<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Service Category Form - Ace Admin';
$pageDescription = 'add or edit service category';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$currentUser = current_user();
$isAdminUser = ($currentUser['role'] ?? '') === 'admin';

if (!$isAdminUser) {
	header('Location: service_categories.php?error=permission_denied');
	exit;
}

$categoryId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $categoryId > 0;
$category = [
	'id' => 0,
	'category_name' => '',
	'category_slug' => '',
	'description' => '',
	'display_order' => 0,
];
$loadError = '';
$formErrors = [
	'invalid' => 'Please complete the required fields.',
	'duplicate_slug' => 'That category slug is already in use.',
	'category_not_found' => 'Service category not found.',
	'database' => 'Unable to save the service category. Please try again.',
	'permission_denied' => 'You do not have permission to manage service categories.',
];
$formError = $formErrors[$_GET['error'] ?? ''] ?? '';
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$loadError = 'Unable to load the service category form right now. Please check the database connection.';
} elseif ($isEdit) {
	try {
		$statement = $pdo->prepare(
			'SELECT id, category_name, category_slug, description, display_order
			FROM service_categories
			WHERE id = :id
			LIMIT 1'
		);
		$statement->execute(['id' => $categoryId]);
		$loadedCategory = $statement->fetch();

		if (!$loadedCategory) {
			$loadError = 'Service category not found.';
		} else {
			$category = array_merge($category, $loadedCategory);
		}
	} catch (PDOException $exception) {
		error_log('Service category form load failed: ' . $exception->getMessage());
		$loadError = 'Unable to load the service category right now. Please try again later.';
	}
}

function service_category_form_escape($value): string
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
								<a href="service_categories.php">Service Categories</a>
							</li>
							<li class="active"><?php echo $isEdit ? 'Edit Category' : 'Add Category'; ?></li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								<?php echo $isEdit ? 'Edit Category' : 'Add Category'; ?>
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									service categories
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($loadError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo service_category_form_escape($loadError); ?>
								</div>
<?php else: ?>
<?php if ($formError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo service_category_form_escape($formError); ?>
								</div>
<?php endif; ?>
								<form class="form-horizontal" role="form" method="POST" action="service_category_save.php">
									<input type="hidden" name="id" value="<?php echo service_category_form_escape($category['id'] ?? 0); ?>" />

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="category_name">Category Name</label>

										<div class="col-sm-9">
											<input type="text" id="category_name" name="category_name" class="col-xs-10 col-sm-5" value="<?php echo service_category_form_escape($category['category_name'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="category_slug">Slug</label>

										<div class="col-sm-9">
											<input type="text" id="category_slug" name="category_slug" class="col-xs-10 col-sm-5" value="<?php echo service_category_form_escape($category['category_slug'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="description">Description</label>

										<div class="col-sm-9">
											<textarea id="description" name="description" class="col-xs-10 col-sm-5" rows="4"><?php echo service_category_form_escape($category['description'] ?? ''); ?></textarea>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="display_order">Display Order</label>

										<div class="col-sm-9">
											<input type="number" id="display_order" name="display_order" class="col-xs-10 col-sm-5" value="<?php echo service_category_form_escape((int) ($category['display_order'] ?? 0)); ?>" />
										</div>
									</div>

									<div class="clearfix form-actions">
										<div class="col-md-offset-3 col-md-9">
											<button class="btn btn-info" type="submit">
												<i class="ace-icon fa fa-check bigger-110"></i>
												Save Category
											</button>

											&nbsp; &nbsp; &nbsp;
											<a class="btn" href="service_categories.php">
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
