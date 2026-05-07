<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'User Form - Ace Admin';
$pageDescription = 'add or edit user';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $userId > 0;
$user = [
	'id' => 0,
	'name' => '',
	'email' => '',
	'role' => available_user_roles_for(current_user())[0] ?? 'manager',
	'status' => 'active',
];
$loadError = '';
$formErrors = [
	'invalid' => 'Please complete the required fields.',
	'invalid_email' => 'Please enter a valid email address.',
	'duplicate_email' => 'That email address is already in use.',
	'user_not_found' => 'User not found.',
	'database' => 'Unable to save the user. Please try again.',
	'self_deactivate' => 'You cannot deactivate your own account.',
	'permission_denied' => 'You do not have permission to manage that user.',
];
$formError = $formErrors[$_GET['error'] ?? ''] ?? '';
$assignableRoles = available_user_roles_for(current_user());
$currentUser = current_user();
$currentRole = $currentUser['role'] ?? '';
$isManagerSelfEdit = false;
$pdo = ace_admin_db();

if (!$isEdit && !can_create_users($currentUser)) {
	header('Location: users.php?error=permission_denied');
	exit;
}

if (!$pdo instanceof PDO) {
	$loadError = 'Unable to load the user form right now. Please check the database connection.';
} elseif ($isEdit) {
	try {
		$statement = $pdo->prepare('SELECT id, name, email, role, status FROM users WHERE id = :id LIMIT 1');
		$statement->execute(['id' => $userId]);
		$loadedUser = $statement->fetch();

		if (!$loadedUser) {
			$loadError = 'User not found.';
		} elseif (!can_edit_user($currentUser, $loadedUser)) {
			header('Location: users.php?error=permission_denied');
			exit;
		} else {
			$user = $loadedUser;
			$isManagerSelfEdit = $currentRole === 'manager' && (int) ($currentUser['id'] ?? 0) === (int) ($loadedUser['id'] ?? 0);
		}
	} catch (PDOException $exception) {
		error_log('User form load failed: ' . $exception->getMessage());
		$loadError = 'Unable to load the user right now. Please try again later.';
	}
}

$roleOptions = ['admin', 'manager'];

function user_form_escape($value): string
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
								<a href="users.php">Users</a>
							</li>
							<li class="active"><?php echo $isEdit ? 'Edit User' : 'Add User'; ?></li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								<?php echo $isEdit ? 'Edit User' : 'Add User'; ?>
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									users management
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($loadError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo user_form_escape($loadError); ?>
								</div>
<?php else: ?>
<?php if ($formError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo user_form_escape($formError); ?>
								</div>
<?php endif; ?>
								<form class="form-horizontal" role="form" method="POST" action="user_save.php">
									<input type="hidden" name="id" value="<?php echo user_form_escape($user['id'] ?? 0); ?>" />

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="name">Name</label>

										<div class="col-sm-9">
											<input type="text" id="name" name="name" class="col-xs-10 col-sm-5" value="<?php echo user_form_escape($user['name'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="email">Email</label>

										<div class="col-sm-9">
											<input type="email" id="email" name="email" class="col-xs-10 col-sm-5" value="<?php echo user_form_escape($user['email'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="role">Role</label>

										<div class="col-sm-9">
<?php if ($isManagerSelfEdit): ?>
											<input type="hidden" name="role" value="<?php echo user_form_escape($user['role'] ?? 'manager'); ?>" />
											<p class="form-control-static"><?php echo user_form_escape($user['role'] ?? 'manager'); ?></p>
<?php else: ?>
											<select id="role" name="role" class="col-xs-10 col-sm-5">
<?php foreach ($roleOptions as $roleOption): ?>
												<option value="<?php echo user_form_escape($roleOption); ?>"<?php echo (($user['role'] ?? '') === $roleOption) ? ' selected="selected"' : ''; ?>><?php echo user_form_escape($roleOption); ?></option>
<?php endforeach; ?>
											</select>
<?php endif; ?>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="status">Status</label>

										<div class="col-sm-9">
<?php if ($isManagerSelfEdit): ?>
											<input type="hidden" name="status" value="<?php echo user_form_escape($user['status'] ?? 'active'); ?>" />
											<p class="form-control-static"><?php echo user_form_escape($user['status'] ?? 'active'); ?></p>
<?php else: ?>
											<select id="status" name="status" class="col-xs-10 col-sm-5">
												<option value="active"<?php echo (($user['status'] ?? '') === 'active') ? ' selected="selected"' : ''; ?>>active</option>
												<option value="inactive"<?php echo (($user['status'] ?? '') === 'inactive') ? ' selected="selected"' : ''; ?>>inactive</option>
											</select>
<?php endif; ?>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="password">Password</label>

										<div class="col-sm-9">
											<input type="password" id="password" name="password" class="col-xs-10 col-sm-5" autocomplete="new-password" />
											<span class="help-inline col-xs-12 col-sm-7">
												<span class="middle"><?php echo $isEdit ? 'Leave blank to keep the current password.' : 'Required for new users.'; ?></span>
											</span>
										</div>
									</div>

									<div class="clearfix form-actions">
										<div class="col-md-offset-3 col-md-9">
											<button class="btn btn-info" type="submit">
												<i class="ace-icon fa fa-check bigger-110"></i>
												Save
											</button>

											&nbsp; &nbsp; &nbsp;
											<a class="btn" href="users.php">
												<i class="ace-icon fa fa-undo bigger-110"></i>
												Cancel
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
