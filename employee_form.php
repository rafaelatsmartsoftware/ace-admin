<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Employee Form - Ace Admin';
$pageDescription = 'add or edit employee';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$currentUser = current_user();
$isAdminUser = ($currentUser['role'] ?? '') === 'admin';

if (!$isAdminUser) {
	header('Location: employees.php?error=permission_denied');
	exit;
}

$employeeId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $employeeId > 0;
$employee = [
	'id' => 0,
	'outlet_id' => 0,
	'employee_name' => '',
	'phone' => '',
	'email' => '',
	'job_title' => '',
	'specialties' => '',
	'joining_date' => '',
	'notes' => '',
];
$outlets = [];
$loadError = '';
$formErrors = [
	'invalid' => 'Please complete the required fields.',
	'invalid_email' => 'Please enter a valid email address.',
	'employee_not_found' => 'Employee not found.',
	'database' => 'Unable to save the employee. Please try again.',
	'permission_denied' => 'You do not have permission to manage employees.',
];
$formError = $formErrors[$_GET['error'] ?? ''] ?? '';
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$loadError = 'Unable to load the employee form right now. Please check the database connection.';
} else {
	try {
		$outletStatement = $pdo->query(
			'SELECT id, branch_name FROM branches ORDER BY branch_name ASC'
		);
		$outlets = $outletStatement->fetchAll();

		if ($isEdit) {
			$statement = $pdo->prepare(
				'SELECT id, outlet_id, employee_name, phone, email, job_title, specialties, joining_date, notes
				FROM employees
				WHERE id = :id
				LIMIT 1'
			);
			$statement->execute(['id' => $employeeId]);
			$loadedEmployee = $statement->fetch();

			if (!$loadedEmployee) {
				$loadError = 'Employee not found.';
			} else {
				$employee = array_merge($employee, $loadedEmployee);
			}
		}
	} catch (PDOException $exception) {
		error_log('Employee form load failed: ' . $exception->getMessage());
		$loadError = 'Unable to load the employee right now. Please try again later.';
	}
}

function employee_form_escape($value): string
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
								<a href="employees.php">Employees</a>
							</li>
							<li class="active"><?php echo $isEdit ? 'Edit Employee' : 'Add Employee'; ?></li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								<?php echo $isEdit ? 'Edit Employee' : 'Add Employee'; ?>
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									employees management
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($loadError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo employee_form_escape($loadError); ?>
								</div>
<?php else: ?>
<?php if ($formError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo employee_form_escape($formError); ?>
								</div>
<?php endif; ?>
<?php if (empty($outlets)): ?>
								<div class="alert alert-warning">
									<i class="ace-icon fa fa-info-circle"></i>
									Please add at least one outlet before creating employees.
								</div>
<?php endif; ?>
								<form class="form-horizontal" role="form" method="POST" action="employee_save.php">
									<input type="hidden" name="id" value="<?php echo employee_form_escape($employee['id'] ?? 0); ?>" />

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="employee_name">Employee Name</label>

										<div class="col-sm-9">
											<input type="text" id="employee_name" name="employee_name" class="col-xs-10 col-sm-5" value="<?php echo employee_form_escape($employee['employee_name'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="outlet_id">Outlet</label>

										<div class="col-sm-9">
											<select id="outlet_id" name="outlet_id" class="col-xs-10 col-sm-5">
												<option value="">Select outlet</option>
<?php foreach ($outlets as $outlet): ?>
<?php $outletId = (int) ($outlet['id'] ?? 0); ?>
												<option value="<?php echo employee_form_escape($outletId); ?>"<?php echo ((int) ($employee['outlet_id'] ?? 0) === $outletId) ? ' selected="selected"' : ''; ?>><?php echo employee_form_escape($outlet['branch_name'] ?? ''); ?></option>
<?php endforeach; ?>
											</select>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="phone">Phone</label>

										<div class="col-sm-9">
											<input type="text" id="phone" name="phone" class="col-xs-10 col-sm-5" value="<?php echo employee_form_escape($employee['phone'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="email">Email</label>

										<div class="col-sm-9">
											<input type="email" id="email" name="email" class="col-xs-10 col-sm-5" value="<?php echo employee_form_escape($employee['email'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="job_title">Job Title</label>

										<div class="col-sm-9">
											<input type="text" id="job_title" name="job_title" class="col-xs-10 col-sm-5" value="<?php echo employee_form_escape($employee['job_title'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="specialties">Specialties</label>

										<div class="col-sm-9">
											<textarea id="specialties" name="specialties" class="col-xs-10 col-sm-5" rows="4"><?php echo employee_form_escape($employee['specialties'] ?? ''); ?></textarea>
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="joining_date">Joining Date</label>

										<div class="col-sm-9">
											<input type="date" id="joining_date" name="joining_date" class="col-xs-10 col-sm-5" value="<?php echo employee_form_escape($employee['joining_date'] ?? ''); ?>" />
										</div>
									</div>

									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" for="notes">Notes</label>

										<div class="col-sm-9">
											<textarea id="notes" name="notes" class="col-xs-10 col-sm-5" rows="4"><?php echo employee_form_escape($employee['notes'] ?? ''); ?></textarea>
										</div>
									</div>

									<div class="clearfix form-actions">
										<div class="col-md-offset-3 col-md-9">
											<button class="btn btn-info" type="submit">
												<i class="ace-icon fa fa-check bigger-110"></i>
												Save Employee
											</button>

											&nbsp; &nbsp; &nbsp;
											<a class="btn" href="employees.php">
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
