<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Service Categories - Ace Admin';
$pageDescription = 'salon and spa categories';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$categories = [];
$tileCategories = [];
$databaseError = '';
$successMessages = [
	'category_created' => 'Service category created successfully.',
	'category_updated' => 'Service category updated successfully.',
];
$errorMessages = [
	'invalid_request' => 'Invalid request.',
	'category_not_found' => 'Service category not found.',
	'database' => 'Unable to complete the request. Please try again.',
	'duplicate_slug' => 'That category slug is already in use.',
	'permission_denied' => 'You do not have permission to manage service categories.',
];
$successMessage = $successMessages[$_GET['success'] ?? ''] ?? '';
$errorMessage = $errorMessages[$_GET['error'] ?? ''] ?? '';
$searchQuery = trim((string) ($_GET['search'] ?? ''));
$selectedCategoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
$currentUser = current_user();
$isAdminUser = ($currentUser['role'] ?? '') === 'admin';
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$databaseError = 'Unable to load service categories right now. Please check the database connection.';
} else {
	try {
		$tileStatement = $pdo->query(
			'SELECT id, category_name, category_slug
			FROM service_categories
			ORDER BY display_order ASC, category_name ASC
			LIMIT 10'
		);
		$tileCategories = $tileStatement->fetchAll();

		if ($searchQuery !== '') {
			$statement = $pdo->prepare(
				'SELECT id, category_name, category_slug, description, display_order, created_at, updated_at
				FROM service_categories
				WHERE category_name LIKE :search_category_name
					OR category_slug LIKE :search_category_slug
					OR description LIKE :search_description
				ORDER BY display_order ASC, category_name ASC'
			);
			$searchTerm = '%' . $searchQuery . '%';
			$statement->bindValue(':search_category_name', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_category_slug', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_description', $searchTerm, PDO::PARAM_STR);
			$statement->execute();
		} else {
			$statement = $pdo->query(
				'SELECT id, category_name, category_slug, description, display_order, created_at, updated_at
				FROM service_categories
				ORDER BY display_order ASC, category_name ASC'
			);
		}

		$categories = $statement->fetchAll();
	} catch (PDOException $exception) {
		error_log('Service categories query failed: ' . $exception->getMessage());
		$databaseError = 'Unable to load service categories right now. Please try again later.';
	}
}

function service_categories_escape($value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function service_categories_display($value): string
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
							<li class="active">Service Categories</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Service Categories
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									salon and spa categories
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($successMessage !== ''): ?>
								<div class="alert alert-success">
									<i class="ace-icon fa fa-check"></i>
									<?php echo service_categories_escape($successMessage); ?>
								</div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo service_categories_escape($errorMessage); ?>
								</div>
<?php endif; ?>
<?php if ($searchQuery !== ''): ?>
								<div class="alert alert-info">
									<i class="ace-icon fa fa-search"></i>
									Search results for: "<?php echo service_categories_escape($searchQuery); ?>"
								</div>
<?php endif; ?>
<?php if ($databaseError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo service_categories_escape($databaseError); ?>
								</div>
<?php else: ?>
								<h3 class="header smaller lighter blue">
									Main Service Categories
								</h3>

<?php if (empty($tileCategories)): ?>
								<div class="alert alert-info">
									<i class="ace-icon fa fa-info-circle"></i>
									No service categories found. Run the seed script or add a category.
								</div>
<?php else: ?>
								<div class="row">
<?php foreach ($tileCategories as $tileCategory): ?>
<?php $tileActiveClass = ((int) ($tileCategory['id'] ?? 0) === $selectedCategoryId) ? 'btn-primary' : 'btn-white btn-info'; ?>
									<div class="col-xs-12 col-sm-6 col-md-3">
										<a href="service_categories.php?category_id=<?php echo service_categories_escape((int) ($tileCategory['id'] ?? 0)); ?>" class="btn <?php echo $tileActiveClass; ?> btn-bold btn-block">
											<i class="ace-icon fa fa-tags"></i>
											<?php echo service_categories_escape($tileCategory['category_name'] ?? ''); ?>
										</a>
										<div class="space-6"></div>
									</div>
<?php endforeach; ?>
								</div>
<?php endif; ?>

								<div class="hr hr24 hr-dotted"></div>

								<div class="clearfix">
									<form class="form-search pull-left" method="GET" action="service_categories.php">
										<span class="input-icon">
											<input type="text" name="search" class="nav-search-input" placeholder="Search categories ..." value="<?php echo service_categories_escape($searchQuery); ?>" autocomplete="off" />
											<i class="ace-icon fa fa-search nav-search-icon"></i>
										</span>
									</form>

<?php if ($isAdminUser): ?>
									<a href="service_category_form.php" class="btn btn-sm btn-primary pull-right">
										<i class="ace-icon fa fa-plus"></i>
										Add Category
									</a>
<?php endif; ?>
								</div>

								<div class="space-6"></div>

								<div class="table-responsive">
									<table id="service-categories-table" class="table table-bordered table-hover">
										<thead>
											<tr>
												<th>ID</th>
												<th>Category Name</th>
												<th>Slug</th>
												<th>Description</th>
												<th>Display Order</th>
												<th>Created At</th>
												<th>Updated At</th>
												<th>Actions</th>
											</tr>
										</thead>

										<tbody>
<?php if (empty($categories)): ?>
											<tr>
												<td colspan="8" class="center">No service categories found.</td>
											</tr>
<?php else: ?>
<?php foreach ($categories as $category): ?>
<?php $categoryId = (int) ($category['id'] ?? 0); ?>
											<tr>
												<td><?php echo service_categories_escape($categoryId); ?></td>
												<td><?php echo service_categories_escape(service_categories_display($category['category_name'] ?? '')); ?></td>
												<td><?php echo service_categories_escape(service_categories_display($category['category_slug'] ?? '')); ?></td>
												<td><?php echo nl2br(service_categories_escape(service_categories_display($category['description'] ?? ''))); ?></td>
												<td><?php echo service_categories_escape((int) ($category['display_order'] ?? 0)); ?></td>
												<td><?php echo service_categories_escape(service_categories_display($category['created_at'] ?? '')); ?></td>
												<td><?php echo service_categories_escape(service_categories_display($category['updated_at'] ?? '')); ?></td>
												<td>
<?php if ($isAdminUser): ?>
													<a href="service_category_form.php?id=<?php echo service_categories_escape($categoryId); ?>" class="btn btn-xs btn-info">
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
