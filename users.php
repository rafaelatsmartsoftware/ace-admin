<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Users - Ace Admin';
$pageDescription = 'Users management';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$users = [];
$databaseError = '';
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$databaseError = 'Unable to load users right now. Please check the database connection.';
} else {
	try {
		$statement = $pdo->query(
			'SELECT id, name, email, role, status, created_at FROM users ORDER BY id ASC'
		);
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
											</tr>
										</thead>

										<tbody>
<?php if (empty($users)): ?>
											<tr>
												<td colspan="6" class="center">
													No users found.
												</td>
											</tr>
<?php else: ?>
<?php foreach ($users as $user): ?>
<?php
	$status = (string) ($user['status'] ?? '');
	$statusClass = $status === 'active' ? 'label-success' : 'label-default';
?>
											<tr>
												<td><?php echo user_page_escape($user['id'] ?? ''); ?></td>
												<td><?php echo user_page_escape($user['name'] ?? ''); ?></td>
												<td><?php echo user_page_escape($user['email'] ?? ''); ?></td>
												<td><?php echo user_page_escape($user['role'] ?? ''); ?></td>
												<td>
													<span class="label label-sm <?php echo $statusClass; ?>">
														<?php echo user_page_escape($status); ?>
													</span>
												</td>
												<td><?php echo user_page_escape($user['created_at'] ?? ''); ?></td>
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
