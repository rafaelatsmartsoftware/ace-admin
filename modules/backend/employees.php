<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Employees - Ace Admin';
$pageDescription = 'salon and spa staff';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$employees = [];
$databaseError = '';
$successMessages = [
	'employee_created' => 'Employee created successfully.',
	'employee_updated' => 'Employee updated successfully.',
];
$errorMessages = [
	'invalid_request' => 'Invalid request.',
	'employee_not_found' => 'Employee not found.',
	'database' => 'Unable to complete the request. Please try again.',
	'permission_denied' => 'You do not have permission to manage employees.',
];
$successMessage = $successMessages[$_GET['success'] ?? ''] ?? '';
$errorMessage = $errorMessages[$_GET['error'] ?? ''] ?? '';
$searchQuery = trim((string) ($_GET['search'] ?? ''));
$currentUser = current_user();
$isAdminUser = ($currentUser['role'] ?? '') === 'admin';
$pdo = ace_admin_db();

if (!$pdo instanceof PDO) {
	$databaseError = 'Unable to load employees right now. Please check the database connection.';
} else {
	try {
		if ($searchQuery !== '') {
			$statement = $pdo->prepare(
				'SELECT employees.id, employees.employee_name, employees.phone, employees.email,
					employees.job_title, employees.specialties, employees.joining_date, employees.notes,
					employees.created_at, employees.updated_at, branches.branch_name
				FROM employees
				INNER JOIN branches ON branches.id = employees.outlet_id
				WHERE employees.employee_name LIKE :search_employee_name
					OR employees.phone LIKE :search_phone
					OR employees.email LIKE :search_email
					OR employees.job_title LIKE :search_job_title
					OR employees.specialties LIKE :search_specialties
					OR branches.branch_name LIKE :search_branch_name
				ORDER BY employees.created_at DESC, employees.id DESC'
			);
			$searchTerm = '%' . $searchQuery . '%';
			$statement->bindValue(':search_employee_name', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_phone', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_email', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_job_title', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_specialties', $searchTerm, PDO::PARAM_STR);
			$statement->bindValue(':search_branch_name', $searchTerm, PDO::PARAM_STR);
			$statement->execute();
		} else {
			$statement = $pdo->query(
				'SELECT employees.id, employees.employee_name, employees.phone, employees.email,
					employees.job_title, employees.specialties, employees.joining_date, employees.notes,
					employees.created_at, employees.updated_at, branches.branch_name
				FROM employees
				INNER JOIN branches ON branches.id = employees.outlet_id
				ORDER BY employees.created_at DESC, employees.id DESC'
			);
		}

		$employees = $statement->fetchAll();
	} catch (PDOException $exception) {
		error_log('Employees query failed: ' . $exception->getMessage());
		$databaseError = 'Unable to load employees right now. Please try again later.';
	}
}

function employees_escape($value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function employees_display($value): string
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
							<li class="active">Employees</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Employees
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									salon and spa staff
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($successMessage !== ''): ?>
								<div class="alert alert-success">
									<i class="ace-icon fa fa-check"></i>
									<?php echo employees_escape($successMessage); ?>
								</div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo employees_escape($errorMessage); ?>
								</div>
<?php endif; ?>
<?php if ($searchQuery !== ''): ?>
								<div class="alert alert-info">
									<i class="ace-icon fa fa-search"></i>
									Search results for: "<?php echo employees_escape($searchQuery); ?>"
								</div>
<?php endif; ?>

								<div class="clearfix">
									<form class="form-search pull-left" method="GET" action="employees.php">
										<span class="input-icon">
											<input type="text" name="search" class="nav-search-input" placeholder="Search employees ..." value="<?php echo employees_escape($searchQuery); ?>" autocomplete="off" />
											<i class="ace-icon fa fa-search nav-search-icon"></i>
										</span>
									</form>

<?php if ($isAdminUser): ?>
									<a href="employee_form.php" class="btn btn-sm btn-primary pull-right">
										<i class="ace-icon fa fa-plus"></i>
										Add Employee
									</a>
<?php endif; ?>
								</div>

								<div class="space-6"></div>

<?php if ($databaseError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo employees_escape($databaseError); ?>
								</div>
<?php else: ?>
								<div class="table-responsive">
									<table id="employees-table" class="table table-bordered table-hover">
										<thead>
											<tr>
												<th>ID</th>
												<th>Employee Name</th>
												<th>Outlet</th>
												<th>Phone</th>
												<th>Email</th>
												<th>Job Title</th>
												<th>Specialties</th>
												<th>Joining Date</th>
												<th>Created At</th>
												<th>Updated At</th>
												<th>Actions</th>
											</tr>
										</thead>

										<tbody>
<?php if (empty($employees)): ?>
											<tr>
												<td colspan="11" class="center">No employees found.</td>
											</tr>
<?php else: ?>
<?php foreach ($employees as $employee): ?>
<?php $employeeId = (int) ($employee['id'] ?? 0); ?>
											<tr>
												<td><?php echo employees_escape($employeeId); ?></td>
												<td>
													<strong><?php echo employees_escape(employees_display($employee['employee_name'] ?? '')); ?></strong>
<?php if (trim((string) ($employee['notes'] ?? '')) !== ''): ?>
													<div class="space-2"></div>
													<?php echo nl2br(employees_escape($employee['notes'])); ?>
<?php endif; ?>
												</td>
												<td><?php echo employees_escape(employees_display($employee['branch_name'] ?? '')); ?></td>
												<td><?php echo employees_escape(employees_display($employee['phone'] ?? '')); ?></td>
												<td><?php echo employees_escape(employees_display($employee['email'] ?? '')); ?></td>
												<td><?php echo employees_escape(employees_display($employee['job_title'] ?? '')); ?></td>
												<td><?php echo nl2br(employees_escape(employees_display($employee['specialties'] ?? ''))); ?></td>
												<td><?php echo employees_escape(employees_display($employee['joining_date'] ?? '')); ?></td>
												<td><?php echo employees_escape(employees_display($employee['created_at'] ?? '')); ?></td>
												<td><?php echo employees_escape(employees_display($employee['updated_at'] ?? '')); ?></td>
												<td>
<?php if ($isAdminUser): ?>
													<a href="employee_form.php?id=<?php echo employees_escape($employeeId); ?>" class="btn btn-xs btn-info">
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
