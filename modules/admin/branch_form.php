<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Branch Form - Ace Admin';
$pageDescription = 'add or edit branch';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$currentUser = current_user();
$isAdminUser = ($currentUser['role'] ?? '') === 'admin';

if (!$isAdminUser) {
	header('Location: branches.php?error=permission_denied');
	exit;
}

$branchId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $branchId > 0;
$branch = [
	'id' => 0,
	'branch_name' => '',
	'branch_code' => '',
	'full_address' => '',
	'area_city' => '',
	'phone' => '',
	'email' => '',
	'google_maps_link' => '',
	'opening_time' => '',
	'closing_time' => '',
	'weekly_off_day' => '',
	'branch_manager' => '',
	'status' => 'active',
	'notes' => '',
];
$loadError = '';
$formErrors = [
	'invalid' => 'Please complete the required fields.',
	'invalid_email' => 'Please enter a valid email address.',
	'invalid_status' => 'Please choose a valid status.',
	'branch_not_found' => 'Branch not found.',
	'database' => 'Unable to save the branch. Please try again.',
	'permission_denied' => 'You do not have permission to manage branches.',
];
$formError = $formErrors[$_GET['error'] ?? ''] ?? '';
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$loadError = 'Unable to load the branch form right now. Please check the database connection.';
} elseif ($isEdit) {
	try {
		$statement = $pdo->prepare(
			'SELECT id, branch_name, branch_code, full_address, area_city, phone, email, google_maps_link,
				opening_time, closing_time, weekly_off_day, branch_manager, status, notes
			FROM branches
			WHERE id = :id
			LIMIT 1'
		);
		$statement->execute(['id' => $branchId]);
		$loadedBranch = $statement->fetch();

		if (!$loadedBranch) {
			$loadError = 'Branch not found.';
		} else {
			$branch = array_merge($branch, $loadedBranch);
		}
	} catch (PDOException $exception) {
		error_log('Branch form load failed: ' . $exception->getMessage());
		$loadError = 'Unable to load the branch right now. Please try again later.';
	}
}

function branch_form_escape($value): string
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
								<a href="branches.php">Branches</a>
							</li>
							<li class="active"><?php echo $isEdit ? 'Edit Branch' : 'Add Branch'; ?></li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								<?php echo $isEdit ? 'Edit Branch' : 'Add Branch'; ?>
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									branches
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($loadError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo branch_form_escape($loadError); ?>
								</div>
<?php else: ?>
<?php if ($formError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo branch_form_escape($formError); ?>
								</div>
<?php endif; ?>
								<form class="form-horizontal" role="form" method="POST" action="branch_save.php">
									<input type="hidden" name="id" value="<?php echo branch_form_escape($branch['id'] ?? 0); ?>" />

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="branch_name">Branch Name</label>

										<div class="col-sm-9">
											<input type="text" id="branch_name" name="branch_name" class="col-xs-10 col-sm-5" value="<?php echo branch_form_escape($branch['branch_name'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="branch_code">Branch Code</label>

										<div class="col-sm-9">
											<input type="text" id="branch_code" name="branch_code" class="col-xs-10 col-sm-5" value="<?php echo branch_form_escape($branch['branch_code'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="full_address">Full Address</label>

										<div class="col-sm-9">
											<textarea id="full_address" name="full_address" class="col-xs-10 col-sm-5" rows="4"><?php echo branch_form_escape($branch['full_address'] ?? ''); ?></textarea>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="area_city">Area / City</label>

										<div class="col-sm-9">
											<input type="text" id="area_city" name="area_city" class="col-xs-10 col-sm-5" value="<?php echo branch_form_escape($branch['area_city'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="phone">Phone</label>

										<div class="col-sm-9">
											<input type="text" id="phone" name="phone" class="col-xs-10 col-sm-5" value="<?php echo branch_form_escape($branch['phone'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="email">Email</label>

										<div class="col-sm-9">
											<input type="email" id="email" name="email" class="col-xs-10 col-sm-5" value="<?php echo branch_form_escape($branch['email'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="google_maps_link">Google Maps Link</label>

										<div class="col-sm-9">
											<input type="text" id="google_maps_link" name="google_maps_link" class="col-xs-10 col-sm-5" value="<?php echo branch_form_escape($branch['google_maps_link'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="opening_time">Opening Time</label>

										<div class="col-sm-9">
											<input type="time" id="opening_time" name="opening_time" class="col-xs-10 col-sm-5" value="<?php echo branch_form_escape($branch['opening_time'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="closing_time">Closing Time</label>

										<div class="col-sm-9">
											<input type="time" id="closing_time" name="closing_time" class="col-xs-10 col-sm-5" value="<?php echo branch_form_escape($branch['closing_time'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="weekly_off_day">Weekly Off Day</label>

										<div class="col-sm-9">
											<input type="text" id="weekly_off_day" name="weekly_off_day" class="col-xs-10 col-sm-5" value="<?php echo branch_form_escape($branch['weekly_off_day'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="branch_manager">Branch Manager</label>

										<div class="col-sm-9">
											<input type="text" id="branch_manager" name="branch_manager" class="col-xs-10 col-sm-5" value="<?php echo branch_form_escape($branch['branch_manager'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="status">Status</label>

										<div class="col-sm-9">
											<select id="status" name="status" class="col-xs-10 col-sm-5">
												<option value="active"<?php echo (($branch['status'] ?? '') === 'active') ? ' selected="selected"' : ''; ?>>active</option>
												<option value="inactive"<?php echo (($branch['status'] ?? '') === 'inactive') ? ' selected="selected"' : ''; ?>>inactive</option>
											</select>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="notes">Notes</label>

										<div class="col-sm-9">
											<textarea id="notes" name="notes" class="col-xs-10 col-sm-5" rows="4"><?php echo branch_form_escape($branch['notes'] ?? ''); ?></textarea>
										</div>
									</div>

									<div class="clearfix form-actions">
										<div class="col-md-offset-3 col-md-9">
											<button class="btn btn-info" type="submit">
												<i class="ace-icon fa fa-check bigger-110"></i>
												Save Branch
											</button>

											&nbsp; &nbsp; &nbsp;
											<a class="btn" href="branches.php">
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
