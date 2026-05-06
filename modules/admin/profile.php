<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Profile - Ace Admin';
$pageDescription = 'my account';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$currentUser = current_user();
$currentUserId = (int) ($currentUser['id'] ?? 0);
$profile = null;
$databaseError = '';
$successMessages = [
	'profile_updated' => 'Profile updated successfully.',
	'password_updated' => 'Password changed successfully.',
];
$errorMessages = [
	'invalid_request' => 'Invalid request.',
	'invalid_profile' => 'Please enter your name and a valid email address.',
	'duplicate_email' => 'That email address is already in use.',
	'invalid_password' => 'Please complete all password fields.',
	'wrong_password' => 'Current password is incorrect.',
	'password_mismatch' => 'New password and confirmation do not match.',
	'password_short' => 'New password must be at least 8 characters.',
	'database' => 'Unable to complete the request. Please try again.',
];
$successMessage = $successMessages[$_GET['success'] ?? ''] ?? '';
$errorMessage = $errorMessages[$_GET['error'] ?? ''] ?? '';
$pdo = ace_admin_db();

function profile_redirect(string $key, string $message): void
{
	header('Location: profile.php?' . $key . '=' . urlencode($message));
	exit;
}

if (!$pdo instanceof PDO) {
	$databaseError = 'Unable to load your profile right now. Please check the database connection.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = trim((string) ($_POST['action'] ?? ''));

	try {
		$statement = $pdo->prepare('SELECT id, name, email, password, role, status FROM users WHERE id = :id LIMIT 1');
		$statement->execute(['id' => $currentUserId]);
		$profileForUpdate = $statement->fetch();

		if (!$profileForUpdate) {
			profile_redirect('error', 'database');
		}

		if ($action === 'update_profile') {
			$name = trim((string) ($_POST['name'] ?? ''));
			$email = trim((string) ($_POST['email'] ?? ''));

			if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				profile_redirect('error', 'invalid_profile');
			}

			$duplicate = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
			$duplicate->execute([
				'email' => $email,
				'id' => $currentUserId,
			]);

			if ($duplicate->fetch()) {
				profile_redirect('error', 'duplicate_email');
			}

			$update = $pdo->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
			$update->execute([
				'name' => $name,
				'email' => $email,
				'id' => $currentUserId,
			]);

			$_SESSION['user_name'] = $name;
			$_SESSION['user_email'] = $email;

			profile_redirect('success', 'profile_updated');
		}

		if ($action === 'change_password') {
			$currentPassword = (string) ($_POST['current_password'] ?? '');
			$newPassword = (string) ($_POST['new_password'] ?? '');
			$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

			if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
				profile_redirect('error', 'invalid_password');
			}

			if (!password_verify($currentPassword, (string) ($profileForUpdate['password'] ?? ''))) {
				profile_redirect('error', 'wrong_password');
			}

			if (strlen($newPassword) < 8) {
				profile_redirect('error', 'password_short');
			}

			if ($newPassword !== $confirmPassword) {
				profile_redirect('error', 'password_mismatch');
			}

			$update = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
			$update->execute([
				'password' => password_hash($newPassword, PASSWORD_DEFAULT),
				'id' => $currentUserId,
			]);

			profile_redirect('success', 'password_updated');
		}

		profile_redirect('error', 'invalid_request');
	} catch (PDOException $exception) {
		error_log('Profile update failed: ' . $exception->getMessage());

		if ($exception->getCode() === '23000') {
			profile_redirect('error', 'duplicate_email');
		}

		profile_redirect('error', 'database');
	}
} elseif ($pdo instanceof PDO) {
	try {
		$statement = $pdo->prepare(
			'SELECT id, name, email, role, status, created_at, updated_at
			FROM users
			WHERE id = :id
			LIMIT 1'
		);
		$statement->execute(['id' => $currentUserId]);
		$profile = $statement->fetch() ?: null;

		if (!$profile) {
			$databaseError = 'Unable to load your profile right now.';
		}
	} catch (PDOException $exception) {
		error_log('Profile load failed: ' . $exception->getMessage());
		$databaseError = 'Unable to load your profile right now. Please try again later.';
	}
}

function profile_escape($value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function profile_display($value): string
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
							<li class="active">Profile</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Profile
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									my account
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($successMessage !== ''): ?>
								<div class="alert alert-success">
									<i class="ace-icon fa fa-check"></i>
									<?php echo profile_escape($successMessage); ?>
								</div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo profile_escape($errorMessage); ?>
								</div>
<?php endif; ?>
<?php if ($databaseError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo profile_escape($databaseError); ?>
								</div>
<?php else: ?>
								<div class="row">
									<div class="col-sm-5">
										<div class="widget-box">
											<div class="widget-header">
												<h4 class="widget-title">
													<i class="ace-icon fa fa-user"></i>
													Profile Details
												</h4>
											</div>

											<div class="widget-body">
												<div class="widget-main">
													<table class="table table-bordered table-striped">
														<tbody>
															<tr>
																<th class="col-sm-4">Name</th>
																<td><?php echo profile_escape(profile_display($profile['name'] ?? '')); ?></td>
															</tr>
															<tr>
																<th>Email</th>
																<td><?php echo profile_escape(profile_display($profile['email'] ?? '')); ?></td>
															</tr>
															<tr>
																<th>Role</th>
																<td><?php echo profile_escape(profile_display($profile['role'] ?? '')); ?></td>
															</tr>
															<tr>
																<th>Status</th>
																<td><?php echo profile_escape(profile_display($profile['status'] ?? '')); ?></td>
															</tr>
															<tr>
																<th>Created At</th>
																<td><?php echo profile_escape(profile_display($profile['created_at'] ?? '')); ?></td>
															</tr>
															<tr>
																<th>Updated At</th>
																<td><?php echo profile_escape(profile_display($profile['updated_at'] ?? '')); ?></td>
															</tr>
														</tbody>
													</table>
												</div>
											</div>
										</div>
									</div>

									<div class="col-sm-7">
										<div class="widget-box">
											<div class="widget-header">
												<h4 class="widget-title">
													<i class="ace-icon fa fa-pencil"></i>
													Update Profile
												</h4>
											</div>

											<div class="widget-body">
												<div class="widget-main">
													<form class="form-horizontal" method="POST" action="profile.php">
														<input type="hidden" name="action" value="update_profile" />

														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="name">Name</label>
															<div class="col-sm-9">
																<input type="text" id="name" name="name" class="col-xs-10 col-sm-8" value="<?php echo profile_escape($profile['name'] ?? ''); ?>" />
															</div>
														</div>

														<div class="space-4"></div>

														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="email">Email</label>
															<div class="col-sm-9">
																<input type="email" id="email" name="email" class="col-xs-10 col-sm-8" value="<?php echo profile_escape($profile['email'] ?? ''); ?>" />
															</div>
														</div>

														<div class="clearfix form-actions">
															<div class="col-md-offset-3 col-md-9">
																<button class="btn btn-info" type="submit">
																	<i class="ace-icon fa fa-check bigger-110"></i>
																	Save Profile
																</button>
															</div>
														</div>
													</form>
												</div>
											</div>
										</div>

										<div class="space-12"></div>

										<div class="widget-box">
											<div class="widget-header">
												<h4 class="widget-title">
													<i class="ace-icon fa fa-lock"></i>
													Change Password
												</h4>
											</div>

											<div class="widget-body">
												<div class="widget-main">
													<form class="form-horizontal" method="POST" action="profile.php">
														<input type="hidden" name="action" value="change_password" />

														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="current_password">Current Password</label>
															<div class="col-sm-9">
																<input type="password" id="current_password" name="current_password" class="col-xs-10 col-sm-8" autocomplete="current-password" />
															</div>
														</div>

														<div class="space-4"></div>

														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="new_password">New Password</label>
															<div class="col-sm-9">
																<input type="password" id="new_password" name="new_password" class="col-xs-10 col-sm-8" autocomplete="new-password" />
															</div>
														</div>

														<div class="space-4"></div>

														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="confirm_password">Confirm Password</label>
															<div class="col-sm-9">
																<input type="password" id="confirm_password" name="confirm_password" class="col-xs-10 col-sm-8" autocomplete="new-password" />
															</div>
														</div>

														<div class="clearfix form-actions">
															<div class="col-md-offset-3 col-md-9">
																<button class="btn btn-info" type="submit">
																	<i class="ace-icon fa fa-key bigger-110"></i>
																	Change Password
																</button>
															</div>
														</div>
													</form>
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
