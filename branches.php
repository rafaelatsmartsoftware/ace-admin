<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Branches / Outlets - Ace Admin';
$pageDescription = 'salon and spa locations';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$branches = [];
$databaseError = '';
$successMessages = [
	'branch_created' => 'Branch created successfully.',
	'branch_updated' => 'Branch updated successfully.',
	'status_updated' => 'Branch status updated successfully.',
];
$errorMessages = [
	'invalid_request' => 'Invalid request.',
	'branch_not_found' => 'Branch not found.',
	'database' => 'Unable to complete the request. Please try again.',
	'permission_denied' => 'You do not have permission to manage branches.',
];
$successMessage = $successMessages[$_GET['success'] ?? ''] ?? '';
$errorMessage = $errorMessages[$_GET['error'] ?? ''] ?? '';
$searchQuery = trim((string) ($_GET['search'] ?? ''));
$currentUser = current_user();
$isAdminUser = ($currentUser['role'] ?? '') === 'admin';
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$databaseError = 'Unable to load branches right now. Please check the database connection.';
} else {
	try {
		if ($searchQuery !== '') {
			$statement = $pdo->prepare(
				'SELECT id, branch_name, branch_code, full_address, area_city, phone, email, google_maps_link,
					opening_time, closing_time, weekly_off_day, branch_manager, status, notes, created_at, updated_at
				FROM branches
				WHERE branch_name LIKE :search_branch_name
					OR branch_code LIKE :search_branch_code
					OR area_city LIKE :search_area_city
					OR phone LIKE :search_phone
					OR email LIKE :search_email
					OR status LIKE :search_status
				ORDER BY created_at DESC, id DESC'
			);
			$searchTerm = '%' . $searchQuery . '%';
			$statement->bindValue(':search_branch_name', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_branch_code', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_area_city', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_phone', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_email', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_status', $searchTerm, PDO::PARAM_STR);
			$statement->execute();
		} else {
			$statement = $pdo->query(
				'SELECT id, branch_name, branch_code, full_address, area_city, phone, email, google_maps_link,
					opening_time, closing_time, weekly_off_day, branch_manager, status, notes, created_at, updated_at
				FROM branches
				ORDER BY created_at DESC, id DESC'
			);
		}

		$branches = $statement->fetchAll();
	} catch (PDOException $exception) {
		error_log('Branches query failed: ' . $exception->getMessage());
		$databaseError = 'Unable to load branches right now. Please try again later.';
	}
}

function branches_escape($value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function branches_display($value): string
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
							<li class="active">Branches / Outlets</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Branches / Outlets
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									salon and spa locations
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($successMessage !== ''): ?>
								<div class="alert alert-success">
									<i class="ace-icon fa fa-check"></i>
									<?php echo branches_escape($successMessage); ?>
								</div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo branches_escape($errorMessage); ?>
								</div>
<?php endif; ?>
<?php if ($searchQuery !== ''): ?>
								<div class="alert alert-info">
									<i class="ace-icon fa fa-search"></i>
									Search results for: "<?php echo branches_escape($searchQuery); ?>"
								</div>
<?php endif; ?>

								<div class="clearfix">
									<form class="form-search pull-left" method="GET" action="branches.php">
										<span class="input-icon">
											<input type="text" name="search" class="nav-search-input" placeholder="Search branches ..." value="<?php echo branches_escape($searchQuery); ?>" autocomplete="off" />
											<i class="ace-icon fa fa-search nav-search-icon"></i>
										</span>
									</form>

<?php if ($isAdminUser): ?>
									<a href="branch_form.php" class="btn btn-sm btn-primary pull-right">
										<i class="ace-icon fa fa-plus"></i>
										Add Branch
									</a>
<?php endif; ?>
								</div>

								<div class="space-6"></div>

<?php if ($databaseError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo branches_escape($databaseError); ?>
								</div>
<?php else: ?>
								<div class="table-responsive">
									<table id="branches-table" class="table table-bordered table-hover">
										<thead>
											<tr>
												<th>ID</th>
												<th>Branch / Outlet Name</th>
												<th>Code</th>
												<th>Area / City</th>
												<th>Phone</th>
												<th>Email</th>
												<th>Opening Time</th>
												<th>Closing Time</th>
												<th>Weekly Off Day</th>
												<th>Branch Manager</th>
												<th>Status</th>
												<th>Created At</th>
												<th>Updated At</th>
												<th>Actions</th>
											</tr>
										</thead>

										<tbody>
<?php if (empty($branches)): ?>
											<tr>
												<td colspan="14" class="center">No branches found.</td>
											</tr>
<?php else: ?>
<?php foreach ($branches as $branch): ?>
<?php
	$branchId = (int) ($branch['id'] ?? 0);
	$status = (string) ($branch['status'] ?? '');
	$statusClass = $status === 'active' ? 'label-success' : 'label-default';
	$nextAction = $status === 'active' ? 'Deactivate' : 'Activate';
	$nextButtonClass = $status === 'active' ? 'btn-warning' : 'btn-success';
?>
											<tr>
												<td><?php echo branches_escape($branchId); ?></td>
												<td><?php echo branches_escape(branches_display($branch['branch_name'] ?? '')); ?></td>
												<td><?php echo branches_escape(branches_display($branch['branch_code'] ?? '')); ?></td>
												<td><?php echo branches_escape(branches_display($branch['area_city'] ?? '')); ?></td>
												<td><?php echo branches_escape(branches_display($branch['phone'] ?? '')); ?></td>
												<td><?php echo branches_escape(branches_display($branch['email'] ?? '')); ?></td>
												<td><?php echo branches_escape(branches_display($branch['opening_time'] ?? '')); ?></td>
												<td><?php echo branches_escape(branches_display($branch['closing_time'] ?? '')); ?></td>
												<td><?php echo branches_escape(branches_display($branch['weekly_off_day'] ?? '')); ?></td>
												<td><?php echo branches_escape(branches_display($branch['branch_manager'] ?? '')); ?></td>
												<td>
													<span class="label label-sm <?php echo $statusClass; ?>">
														<?php echo branches_escape(branches_display($status)); ?>
													</span>
												</td>
												<td><?php echo branches_escape(branches_display($branch['created_at'] ?? '')); ?></td>
												<td><?php echo branches_escape(branches_display($branch['updated_at'] ?? '')); ?></td>
												<td>
<?php if ($isAdminUser): ?>
													<div class="hidden-sm hidden-xs btn-group">
														<a href="branch_form.php?id=<?php echo branches_escape($branchId); ?>" class="btn btn-xs btn-info">
															<i class="ace-icon fa fa-pencil bigger-120"></i>
														</a>

														<form action="branch_toggle_status.php" method="POST" style="display:inline">
															<input type="hidden" name="id" value="<?php echo branches_escape($branchId); ?>" />
															<button type="submit" class="btn btn-xs <?php echo $nextButtonClass; ?>">
																<i class="ace-icon fa fa-power-off bigger-120"></i>
																<span class="sr-only"><?php echo branches_escape($nextAction); ?></span>
															</button>
														</form>
													</div>

													<div class="hidden-md hidden-lg">
														<a href="branch_form.php?id=<?php echo branches_escape($branchId); ?>" class="btn btn-minier btn-info">
															<i class="ace-icon fa fa-pencil"></i>
															Edit
														</a>

														<form action="branch_toggle_status.php" method="POST" style="display:inline">
															<input type="hidden" name="id" value="<?php echo branches_escape($branchId); ?>" />
															<button type="submit" class="btn btn-minier <?php echo $nextButtonClass; ?>">
																<?php echo branches_escape($nextAction); ?>
															</button>
														</form>
													</div>
<?php else: ?>
													<span class="text-muted">View only</span>
<?php endif; ?>
												</td>
											</tr>
											<tr>
												<td colspan="14">
													<strong>Address:</strong>
													<?php echo nl2br(branches_escape(branches_display($branch['full_address'] ?? ''))); ?>
<?php if (trim((string) ($branch['google_maps_link'] ?? '')) !== ''): ?>
													&nbsp; | &nbsp;
													<a href="<?php echo branches_escape($branch['google_maps_link']); ?>" target="_blank" rel="noopener">
														Google Maps
													</a>
<?php endif; ?>
<?php if (trim((string) ($branch['notes'] ?? '')) !== ''): ?>
													<div class="space-2"></div>
													<strong>Notes:</strong>
													<?php echo nl2br(branches_escape($branch['notes'])); ?>
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
