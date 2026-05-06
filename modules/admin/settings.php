<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Settings - Ace Admin';
$pageDescription = 'company profile settings';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$currentUser = current_user();
$isAdminUser = ($currentUser['role'] ?? '') === 'admin';
$settings = [
	'business_name' => '',
	'logo' => '',
	'phone' => '',
	'email' => '',
	'website' => '',
	'main_address' => '',
	'description' => '',
	'facebook_url' => '',
	'instagram_url' => '',
	'opening_note' => '',
	'status' => 'active',
];
$databaseError = '';
$successMessages = [
	'saved' => 'Company profile settings saved successfully.',
];
$errorMessages = [
	'invalid' => 'Business name is required.',
	'invalid_email' => 'Please enter a valid email address.',
	'invalid_status' => 'Please choose a valid status.',
	'database' => 'Unable to save company profile settings. Please try again.',
	'permission_denied' => 'You do not have permission to edit settings.',
];
$successMessage = $successMessages[$_GET['success'] ?? ''] ?? '';
$errorMessage = $errorMessages[$_GET['error'] ?? ''] ?? '';
$pdo = ace_admin_db();

if (!$isAdminUser && $_SERVER['REQUEST_METHOD'] === 'POST') {
	header('Location: settings.php?error=permission_denied');
	exit;
}

if ($isAdminUser && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$formData = [
		'business_name' => trim((string) ($_POST['business_name'] ?? '')),
		'logo' => trim((string) ($_POST['logo'] ?? '')),
		'phone' => trim((string) ($_POST['phone'] ?? '')),
		'email' => trim((string) ($_POST['email'] ?? '')),
		'website' => trim((string) ($_POST['website'] ?? '')),
		'main_address' => trim((string) ($_POST['main_address'] ?? '')),
		'description' => trim((string) ($_POST['description'] ?? '')),
		'facebook_url' => trim((string) ($_POST['facebook_url'] ?? '')),
		'instagram_url' => trim((string) ($_POST['instagram_url'] ?? '')),
		'opening_note' => trim((string) ($_POST['opening_note'] ?? '')),
		'status' => trim((string) ($_POST['status'] ?? 'active')),
	];

	if ($formData['business_name'] === '') {
		header('Location: settings.php?error=invalid');
		exit;
	}

	if ($formData['email'] !== '' && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
		header('Location: settings.php?error=invalid_email');
		exit;
	}

	if (!in_array($formData['status'], ['active', 'inactive'], true)) {
		header('Location: settings.php?error=invalid_status');
		exit;
	}

	if (!$pdo instanceof PDO) {
		header('Location: settings.php?error=database');
		exit;
	}

	try {
		$statement = $pdo->prepare(
			'INSERT INTO company_settings
				(id, business_name, logo, phone, email, website, main_address, description, facebook_url, instagram_url, opening_note, status)
			VALUES
				(1, :business_name, :logo, :phone, :email, :website, :main_address, :description, :facebook_url, :instagram_url, :opening_note, :status)
			ON DUPLICATE KEY UPDATE
				business_name = VALUES(business_name),
				logo = VALUES(logo),
				phone = VALUES(phone),
				email = VALUES(email),
				website = VALUES(website),
				main_address = VALUES(main_address),
				description = VALUES(description),
				facebook_url = VALUES(facebook_url),
				instagram_url = VALUES(instagram_url),
				opening_note = VALUES(opening_note),
				status = VALUES(status)'
		);
		$statement->execute($formData);

		header('Location: settings.php?success=saved');
		exit;
	} catch (PDOException $exception) {
		error_log('Company settings save failed: ' . $exception->getMessage());
		header('Location: settings.php?error=database');
		exit;
	}
}

if (!$pdo instanceof PDO) {
	$databaseError = 'Unable to load settings right now. Please check the database connection.';
} elseif ($isAdminUser) {
	try {
		$statement = $pdo->prepare('SELECT * FROM company_settings WHERE id = :id LIMIT 1');
		$statement->execute(['id' => 1]);
		$loadedSettings = $statement->fetch();

		if ($loadedSettings) {
			$settings = array_merge($settings, $loadedSettings);
		}
	} catch (PDOException $exception) {
		error_log('Company settings load failed: ' . $exception->getMessage());
		$databaseError = 'Unable to load settings right now. Please try again later.';
	}
}

function settings_escape($value): string
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
							<li class="active">Settings</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Settings
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									company profile settings
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if (!$isAdminUser): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo settings_escape($errorMessages['permission_denied']); ?>
								</div>
<?php else: ?>
<?php if ($successMessage !== ''): ?>
								<div class="alert alert-success">
									<i class="ace-icon fa fa-check"></i>
									<?php echo settings_escape($successMessage); ?>
								</div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo settings_escape($errorMessage); ?>
								</div>
<?php endif; ?>
<?php if ($databaseError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo settings_escape($databaseError); ?>
								</div>
<?php else: ?>
								<form class="form-horizontal" role="form" method="POST" action="settings.php">
									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="business_name">Business Name</label>

										<div class="col-sm-9">
											<input type="text" id="business_name" name="business_name" class="col-xs-10 col-sm-5" value="<?php echo settings_escape($settings['business_name'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="logo">Logo Path or URL</label>

										<div class="col-sm-9">
											<input type="text" id="logo" name="logo" class="col-xs-10 col-sm-5" value="<?php echo settings_escape($settings['logo'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="phone">Phone</label>

										<div class="col-sm-9">
											<input type="text" id="phone" name="phone" class="col-xs-10 col-sm-5" value="<?php echo settings_escape($settings['phone'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="email">Email</label>

										<div class="col-sm-9">
											<input type="email" id="email" name="email" class="col-xs-10 col-sm-5" value="<?php echo settings_escape($settings['email'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="website">Website</label>

										<div class="col-sm-9">
											<input type="text" id="website" name="website" class="col-xs-10 col-sm-5" value="<?php echo settings_escape($settings['website'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="main_address">Main Address</label>

										<div class="col-sm-9">
											<textarea id="main_address" name="main_address" class="col-xs-10 col-sm-5" rows="4"><?php echo settings_escape($settings['main_address'] ?? ''); ?></textarea>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="description">Description</label>

										<div class="col-sm-9">
											<textarea id="description" name="description" class="col-xs-10 col-sm-5" rows="4"><?php echo settings_escape($settings['description'] ?? ''); ?></textarea>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="facebook_url">Facebook URL</label>

										<div class="col-sm-9">
											<input type="text" id="facebook_url" name="facebook_url" class="col-xs-10 col-sm-5" value="<?php echo settings_escape($settings['facebook_url'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="instagram_url">Instagram URL</label>

										<div class="col-sm-9">
											<input type="text" id="instagram_url" name="instagram_url" class="col-xs-10 col-sm-5" value="<?php echo settings_escape($settings['instagram_url'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="opening_note">Opening Note</label>

										<div class="col-sm-9">
											<textarea id="opening_note" name="opening_note" class="col-xs-10 col-sm-5" rows="3"><?php echo settings_escape($settings['opening_note'] ?? ''); ?></textarea>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="status">Status</label>

										<div class="col-sm-9">
											<select id="status" name="status" class="col-xs-10 col-sm-5">
												<option value="active"<?php echo (($settings['status'] ?? '') === 'active') ? ' selected="selected"' : ''; ?>>active</option>
												<option value="inactive"<?php echo (($settings['status'] ?? '') === 'inactive') ? ' selected="selected"' : ''; ?>>inactive</option>
											</select>
										</div>
									</div>

									<div class="clearfix form-actions">
										<div class="col-md-offset-3 col-md-9">
											<button class="btn btn-info" type="submit">
												<i class="ace-icon fa fa-check bigger-110"></i>
												Save Settings
											</button>

											&nbsp; &nbsp; &nbsp;
											<a class="btn" href="company_profile.php">
												<i class="ace-icon fa fa-eye bigger-110"></i>
												View Profile
											</a>
										</div>
									</div>
								</form>
<?php endif; ?>
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
