<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Dashboard - Smart ERP';
$pageDescription = '';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;
$userStats = [
	'total_users' => 0,
	'active_users' => 0,
	'inactive_users' => 0,
	'admins_count' => 0,
	'managers_count' => 0,
	'basic_users_count' => 0,
];
$latestUsers = [];
$recentAccountChanges = [];
$usersOverviewError = '';
$recentChangesError = '';
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$usersOverviewError = 'Unable to load users overview right now. Please check the database connection.';
} else {
	try {
		$statsStatement = $pdo->query(
			"SELECT
				COUNT(*) AS total_users,
				COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) AS active_users,
				COALESCE(SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END), 0) AS inactive_users,
				COALESCE(SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END), 0) AS admins_count,
				COALESCE(SUM(CASE WHEN role = 'manager' THEN 1 ELSE 0 END), 0) AS managers_count,
				COALESCE(SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END), 0) AS basic_users_count
			FROM users"
		);
		$userStats = array_merge($userStats, $statsStatement->fetch() ?: []);

		$latestStatement = $pdo->query(
			'SELECT name, email, role, status, created_at FROM users ORDER BY created_at DESC, id DESC LIMIT 5'
		);
		$latestUsers = $latestStatement->fetchAll();
	} catch (PDOException $exception) {
		error_log('Dashboard users overview failed: ' . $exception->getMessage());
		$usersOverviewError = 'Unable to load users overview right now. Please try again later.';
	}

	try {
		$recentStatement = $pdo->query(
			'SELECT name, email, role, status, updated_at FROM users ORDER BY updated_at DESC, id DESC LIMIT 5'
		);
		$recentAccountChanges = $recentStatement->fetchAll();
	} catch (PDOException $exception) {
		error_log('Dashboard recent account changes failed: ' . $exception->getMessage());
		$recentChangesError = 'Recent account changes are not available right now.';
	}
}

function dashboard_escape($value): string
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
								<a href="#">Home</a>
							</li>
							<li class="active">Dashboard</li>
						</ul><!-- /.breadcrumb -->

						<div class="nav-search" id="nav-search">
							<form class="form-search" action="users.php" method="GET">
								<span class="input-icon">
									<input type="text" name="search" placeholder="Search ..." class="nav-search-input" id="nav-search-input" autocomplete="off" />
									<i class="ace-icon fa fa-search nav-search-icon"></i>
								</span>
							</form>
						</div><!-- /.nav-search -->
					</div>

					<div class="page-content">
						<div class="ace-settings-container" id="ace-settings-container">
							<div class="btn btn-app btn-xs btn-warning ace-settings-btn" id="ace-settings-btn">
								<i class="ace-icon fa fa-cog bigger-130"></i>
							</div>

							<div class="ace-settings-box clearfix" id="ace-settings-box">
								<div class="pull-left width-50">
									<div class="ace-settings-item">
										<div class="pull-left">
											<select id="skin-colorpicker" class="hide">
												<option data-skin="no-skin" value="#438EB9">#438EB9</option>
												<option data-skin="skin-1" value="#222A2D">#222A2D</option>
												<option data-skin="skin-2" value="#C6487E">#C6487E</option>
												<option data-skin="skin-3" value="#D0D0D0">#D0D0D0</option>
											</select>
										</div>
										<span>&nbsp; Choose Skin</span>
									</div>

									<div class="ace-settings-item">
										<input type="checkbox" class="ace ace-checkbox-2 ace-save-state" id="ace-settings-navbar" autocomplete="off" />
										<label class="lbl" for="ace-settings-navbar"> Fixed Navbar</label>
									</div>

									<div class="ace-settings-item">
										<input type="checkbox" class="ace ace-checkbox-2 ace-save-state" id="ace-settings-sidebar" autocomplete="off" />
										<label class="lbl" for="ace-settings-sidebar"> Fixed Sidebar</label>
									</div>

									<div class="ace-settings-item">
										<input type="checkbox" class="ace ace-checkbox-2 ace-save-state" id="ace-settings-breadcrumbs" autocomplete="off" />
										<label class="lbl" for="ace-settings-breadcrumbs"> Fixed Breadcrumbs</label>
									</div>

									<div class="ace-settings-item">
										<input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-rtl" autocomplete="off" />
										<label class="lbl" for="ace-settings-rtl"> Right To Left (rtl)</label>
									</div>

									<div class="ace-settings-item">
										<input type="checkbox" class="ace ace-checkbox-2 ace-save-state" id="ace-settings-add-container" autocomplete="off" />
										<label class="lbl" for="ace-settings-add-container">
											Inside
											<b>.container</b>
										</label>
									</div>
								</div><!-- /.pull-left -->

								<div class="pull-left width-50">
									<div class="ace-settings-item">
										<input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-hover" autocomplete="off" />
										<label class="lbl" for="ace-settings-hover"> Submenu on Hover</label>
									</div>

									<div class="ace-settings-item">
										<input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-compact" autocomplete="off" />
										<label class="lbl" for="ace-settings-compact"> Compact Sidebar</label>
									</div>

									<div class="ace-settings-item">
										<input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-highlight" autocomplete="off" />
										<label class="lbl" for="ace-settings-highlight"> Alt. Active Item</label>
									</div>
								</div><!-- /.pull-left -->
							</div><!-- /.ace-settings-box -->
						</div><!-- /.ace-settings-container -->

						<div class="page-header">
							<h1>
								Dashboard
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
								<div class="row">
									<div class="col-xs-12">
										<h3 class="header smaller lighter blue">
											Users Overview
											<small>
												<i class="ace-icon fa fa-angle-double-right"></i>
												live database stats
											</small>
										</h3>
									</div>
								</div>

<?php if ($usersOverviewError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo dashboard_escape($usersOverviewError); ?>
								</div>
<?php endif; ?>

								<div class="row">
									<div class="space-6"></div>

									<div class="col-xs-12 infobox-container">
										<a href="users.php" class="infobox infobox-blue">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-users"></i>
											</div>

											<div class="infobox-data">
												<span class="infobox-data-number"><?php echo dashboard_escape(number_format((int) $userStats['total_users'])); ?></span>
												<div class="infobox-content">Total Users</div>
											</div>
										</a>

										<a href="users.php" class="infobox infobox-green">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-check-circle"></i>
											</div>

											<div class="infobox-data">
												<span class="infobox-data-number"><?php echo dashboard_escape(number_format((int) $userStats['active_users'])); ?></span>
												<div class="infobox-content">Active Users</div>
											</div>
										</a>

										<a href="users.php" class="infobox infobox-red">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-ban"></i>
											</div>

											<div class="infobox-data">
												<span class="infobox-data-number"><?php echo dashboard_escape(number_format((int) $userStats['inactive_users'])); ?></span>
												<div class="infobox-content">Inactive Users</div>
											</div>
										</a>

										<a href="users.php" class="infobox infobox-purple">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-user-secret"></i>
											</div>

											<div class="infobox-data">
												<span class="infobox-data-number"><?php echo dashboard_escape(number_format((int) $userStats['admins_count'])); ?></span>
												<div class="infobox-content">Admins</div>
											</div>
										</a>

										<a href="users.php" class="infobox infobox-orange2">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-briefcase"></i>
											</div>

											<div class="infobox-data">
												<span class="infobox-data-number"><?php echo dashboard_escape(number_format((int) $userStats['managers_count'])); ?></span>
												<div class="infobox-content">Managers</div>
											</div>
										</a>

										<a href="users.php" class="infobox infobox-grey">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-user"></i>
											</div>

											<div class="infobox-data">
												<span class="infobox-data-number"><?php echo dashboard_escape(number_format((int) $userStats['basic_users_count'])); ?></span>
												<div class="infobox-content">Basic Users</div>
											</div>
										</a>
									</div>
								</div>

								<div class="space-12"></div>

								<div class="row">
									<div class="col-sm-6">
										<div class="widget-box transparent">
											<div class="widget-header widget-header-flat">
												<h4 class="widget-title lighter">
													<i class="ace-icon fa fa-user blue"></i>
													Latest Users
												</h4>
											</div>

											<div class="widget-body">
												<div class="widget-main no-padding">
													<table class="table table-bordered table-striped">
														<thead class="thin-border-bottom">
															<tr>
																<th>Name</th>
																<th>Email</th>
																<th>Role</th>
																<th>Status</th>
																<th>Created At</th>
															</tr>
														</thead>

														<tbody>
<?php if (empty($latestUsers)): ?>
															<tr>
																<td colspan="5" class="center">No users found.</td>
															</tr>
<?php else: ?>
<?php foreach ($latestUsers as $latestUser): ?>
<?php $latestStatusClass = ($latestUser['status'] ?? '') === 'active' ? 'label-success' : 'label-default'; ?>
															<tr>
																<td><?php echo dashboard_escape($latestUser['name'] ?? ''); ?></td>
																<td><?php echo dashboard_escape($latestUser['email'] ?? ''); ?></td>
																<td><?php echo dashboard_escape($latestUser['role'] ?? ''); ?></td>
																<td>
																	<span class="label label-sm <?php echo $latestStatusClass; ?>">
																		<?php echo dashboard_escape($latestUser['status'] ?? ''); ?>
																	</span>
																</td>
																<td><?php echo dashboard_escape($latestUser['created_at'] ?? ''); ?></td>
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
										<div class="widget-box transparent">
											<div class="widget-header widget-header-flat">
												<h4 class="widget-title lighter">
													<i class="ace-icon fa fa-history orange"></i>
													Recent Account Changes
												</h4>
											</div>

											<div class="widget-body">
												<div class="widget-main no-padding">
<?php if ($recentChangesError !== ''): ?>
													<div class="alert alert-warning no-margin">
														<i class="ace-icon fa fa-info-circle"></i>
														<?php echo dashboard_escape($recentChangesError); ?>
													</div>
<?php else: ?>
													<table class="table table-bordered table-striped">
														<thead class="thin-border-bottom">
															<tr>
																<th>Name</th>
																<th>Email</th>
																<th>Role</th>
																<th>Status</th>
																<th>Updated At</th>
															</tr>
														</thead>

														<tbody>
<?php if (empty($recentAccountChanges)): ?>
															<tr>
																<td colspan="5" class="center">No account changes found.</td>
															</tr>
<?php else: ?>
<?php foreach ($recentAccountChanges as $recentUser): ?>
<?php $recentStatusClass = ($recentUser['status'] ?? '') === 'active' ? 'label-success' : 'label-default'; ?>
															<tr>
																<td><?php echo dashboard_escape($recentUser['name'] ?? ''); ?></td>
																<td><?php echo dashboard_escape($recentUser['email'] ?? ''); ?></td>
																<td><?php echo dashboard_escape($recentUser['role'] ?? ''); ?></td>
																<td>
																	<span class="label label-sm <?php echo $recentStatusClass; ?>">
																		<?php echo dashboard_escape($recentUser['status'] ?? ''); ?>
																	</span>
																</td>
																<td><?php echo dashboard_escape($recentUser['updated_at'] ?? ''); ?></td>
															</tr>
<?php endforeach; ?>
<?php endif; ?>
														</tbody>
													</table>
<?php endif; ?>
												</div>
											</div>
										</div>
									</div>
								</div>
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
