<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Users - Ace Admin';
$pageDescription = 'Users management';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$users = [];
$databaseError = '';
$successMessages = [
	'user_created' => 'User created successfully.',
	'user_updated' => 'User updated successfully.',
	'status_updated' => 'User status updated successfully.',
];
$errorMessages = [
	'invalid_request' => 'Invalid request.',
	'user_not_found' => 'User not found.',
	'database' => 'Unable to complete the request. Please try again.',
	'self_deactivate' => 'You cannot deactivate your own account.',
	'permission_denied' => 'You do not have permission to manage that user.',
];
$successMessage = $successMessages[$_GET['success'] ?? ''] ?? '';
$errorMessage = $errorMessages[$_GET['error'] ?? ''] ?? '';
$searchQuery = trim((string) ($_GET['search'] ?? ''));
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$databaseError = 'Unable to load users right now. Please check the database connection.';
} else {
	try {
		if ($searchQuery !== '') {
			$statement = $pdo->prepare(
				'SELECT id, name, email, role, status, created_at
				FROM users
				WHERE name LIKE :search_name
					OR email LIKE :search_email
					OR role LIKE :search_role
					OR status LIKE :search_status
				ORDER BY created_at DESC'
			);
			$searchTerm = '%' . $searchQuery . '%';
			$statement->bindValue(':search_name', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_email', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_role', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_status', $searchTerm, PDO::PARAM_STR);
			$statement->execute();
		} else {
			$statement = $pdo->query(
				'SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC'
			);
		}
		$users = $statement->fetchAll();
	} catch (PDOException $exception) {
		error_log('Users query failed: ' . $exception->getMessage());
		$databaseError = 'Unable to load users right now. Please try again later.';
	}
}

function user_page_escape($value): string
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
							<li class="active">Users</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Users
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									management
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($successMessage !== ''): ?>
								<div class="alert alert-success">
									<i class="ace-icon fa fa-check"></i>
									<?php echo user_page_escape($successMessage); ?>
								</div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo user_page_escape($errorMessage); ?>
								</div>
<?php endif; ?>
<?php if ($searchQuery !== ''): ?>
								<div class="alert alert-info">
									<i class="ace-icon fa fa-search"></i>
									Search results for: "<?php echo user_page_escape($searchQuery); ?>"
								</div>
<?php endif; ?>
<?php if (can_create_users(current_user())): ?>
								<div class="clearfix">
									<a href="user_form.php" class="btn btn-sm btn-primary pull-right">
										<i class="ace-icon fa fa-plus"></i>
										Add User
									</a>
								</div>
								<div class="space-6"></div>
<?php endif; ?>
<?php if ($databaseError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo user_page_escape($databaseError); ?>
								</div>
<?php else: ?>
								<div>
									<table id="simple-table" class="table table-bordered table-hover">
										<thead>
											<tr>
												<th>ID</th>
												<th>Name</th>
												<th>Email</th>
												<th>Role</th>
												<th>Status</th>
												<th>
													<i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i>
													Created At
												</th>
												<th></th>
											</tr>
										</thead>

										<tbody>
<?php if (empty($users)): ?>
											<tr>
												<td colspan="7" class="center">
													No users found.
												</td>
											</tr>
<?php else: ?>
<?php foreach ($users as $user): ?>
<?php
	$status = (string) ($user['status'] ?? '');
	$statusClass = $status === 'active' ? 'label-success' : 'label-default';
	$userId = (int) ($user['id'] ?? 0);
	$canEditUser = can_edit_user(current_user(), $user);
	$canToggleStatus = can_manage_user_status(current_user(), $user);
	$nextAction = $status === 'active' ? 'Deactivate' : 'Activate';
	$nextButtonClass = $status === 'active' ? 'btn-warning' : 'btn-success';
?>
											<tr>
												<td><?php echo user_page_escape($userId); ?></td>
												<td><?php echo user_page_escape($user['name'] ?? ''); ?></td>
												<td><?php echo user_page_escape($user['email'] ?? ''); ?></td>
												<td><?php echo user_page_escape($user['role'] ?? ''); ?></td>
												<td>
													<span class="label label-sm <?php echo $statusClass; ?>">
														<?php echo user_page_escape($status); ?>
													</span>
												</td>
												<td><?php echo user_page_escape($user['created_at'] ?? ''); ?></td>
												<td>
													<div class="hidden-sm hidden-xs btn-group">
<?php if ($canEditUser): ?>
														<a href="user_form.php?id=<?php echo user_page_escape($userId); ?>" class="btn btn-xs btn-info">
															<i class="ace-icon fa fa-pencil bigger-120"></i>
														</a>
<?php endif; ?>

<?php if ($canToggleStatus): ?>
														<form action="user_toggle_status.php" method="POST" style="display:inline">
															<input type="hidden" name="id" value="<?php echo user_page_escape($userId); ?>" />
															<button type="submit" class="btn btn-xs <?php echo $nextButtonClass; ?>">
																<i class="ace-icon fa fa-power-off bigger-120"></i>
																<span class="sr-only"><?php echo user_page_escape($nextAction); ?></span>
															</button>
														</form>
<?php endif; ?>
													</div>

													<div class="hidden-md hidden-lg">
<?php if ($canEditUser): ?>
														<a href="user_form.php?id=<?php echo user_page_escape($userId); ?>" class="btn btn-minier btn-info">
															<i class="ace-icon fa fa-pencil"></i>
															Edit
														</a>
<?php endif; ?>

<?php if ($canToggleStatus): ?>
														<form action="user_toggle_status.php" method="POST" style="display:inline">
															<input type="hidden" name="id" value="<?php echo user_page_escape($userId); ?>" />
															<button type="submit" class="btn btn-minier <?php echo $nextButtonClass; ?>">
																<?php echo user_page_escape($nextAction); ?>
															</button>
														</form>
<?php endif; ?>
													</div>
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
