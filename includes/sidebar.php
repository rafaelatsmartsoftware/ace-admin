<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$isDashboardPage = $currentPage === 'index.php';
$isCompanyProfilePage = $currentPage === 'company_profile.php';
$isBranchesPage = in_array($currentPage, ['branches.php', 'branch_form.php'], true);
$isServiceCategoriesPage = in_array($currentPage, ['service_categories.php', 'service_category_form.php'], true);
$isServicesPage = in_array($currentPage, ['services.php', 'service_form.php'], true);
$isEmployeesPage = in_array($currentPage, ['employees.php', 'employee_form.php'], true);
$isCustomersPage = in_array($currentPage, ['customers.php', 'customer_form.php'], true);
$isBookingsPage = in_array($currentPage, ['bookings.php', 'booking_form.php'], true);
$isSchedulePage = $currentPage === 'schedule.php';
$isPaymentsPage = in_array($currentPage, ['payments.php', 'payment_form.php', 'payment_view.php'], true);
$isInventoryPage = in_array($currentPage, ['inventory.php', 'inventory_form.php'], true);
$isReportsPage = $currentPage === 'reports.php';
$isSettingsPage = $currentPage === 'settings.php';
$isUsersPage = in_array($currentPage, ['users.php', 'user_form.php'], true);
$currentSidebarUser = current_user();
$isAdminUser = ($currentSidebarUser['role'] ?? '') === 'admin';
$canViewReports = in_array($currentSidebarUser['role'] ?? '', ['admin', 'manager'], true);
?>
			<div id="sidebar" class="sidebar                  responsive                    ace-save-state">
				<script type="text/javascript">
					try{ace.settings.loadState('sidebar')}catch(e){}
				</script>

				<div class="sidebar-shortcuts" id="sidebar-shortcuts">
					<div class="sidebar-shortcuts-large" id="sidebar-shortcuts-large">
						<a href="reports.php" class="btn btn-success">
							<i class="ace-icon fa fa-signal"></i>
						</a>

						<a href="inventory.php" class="btn btn-info">
							<i class="ace-icon fa fa-pencil"></i>
						</a>

						<a href="users.php" class="btn btn-warning">
							<i class="ace-icon fa fa-users"></i>
						</a>

						<a href="settings.php" class="btn btn-danger">
							<i class="ace-icon fa fa-cogs"></i>
						</a>
					</div>

					<div class="sidebar-shortcuts-mini" id="sidebar-shortcuts-mini">
						<a href="reports.php" class="btn btn-success"></a>

						<a href="inventory.php" class="btn btn-info"></a>

						<a href="users.php" class="btn btn-warning"></a>

						<a href="settings.php" class="btn btn-danger"></a>
					</div>
				</div><!-- /.sidebar-shortcuts -->

				<ul class="nav nav-list">
					<li class="<?php echo $isDashboardPage ? 'active' : ''; ?>">
						<a href="index.php">
							<i class="menu-icon fa fa-tachometer"></i>
							<span class="menu-text"> Dashboard </span>
						</a>

						<b class="arrow"></b>
					</li>

					<li class="<?php echo $isCompanyProfilePage ? 'active' : ''; ?>">
						<a href="company_profile.php">
							<i class="menu-icon fa fa-building-o"></i>
							<span class="menu-text"> Company Profile </span>
						</a>

						<b class="arrow"></b>
					</li>

					<li class="<?php echo $isBranchesPage ? 'active' : ''; ?>">
						<a href="branches.php">
							<i class="menu-icon fa fa-map-marker"></i>
							<span class="menu-text"> Branches </span>
						</a>

						<b class="arrow"></b>
					</li>

					<li class="<?php echo $isServiceCategoriesPage ? 'active' : ''; ?>">
						<a href="service_categories.php">
							<i class="menu-icon fa fa-list-alt"></i>
							<span class="menu-text"> Service Categories </span>
						</a>

						<b class="arrow"></b>
					</li>

					<li class="<?php echo $isServicesPage ? 'active' : ''; ?>">
						<a href="services.php">
							<i class="menu-icon fa fa-scissors"></i>
							<span class="menu-text"> Services </span>
						</a>

						<b class="arrow"></b>
					</li>

					<li class="<?php echo $isEmployeesPage ? 'active' : ''; ?>">
						<a href="employees.php">
							<i class="menu-icon fa fa-user-md"></i>
							<span class="menu-text"> Employees </span>
						</a>

						<b class="arrow"></b>
					</li>

					<li class="<?php echo $isCustomersPage ? 'active' : ''; ?>">
						<a href="customers.php">
							<i class="menu-icon fa fa-smile-o"></i>
							<span class="menu-text"> Customers </span>
						</a>

						<b class="arrow"></b>
					</li>

					<li class="<?php echo $isBookingsPage ? 'active' : ''; ?>">
						<a href="bookings.php">
							<i class="menu-icon fa fa-calendar-check-o"></i>
							<span class="menu-text"> Bookings </span>
						</a>

						<b class="arrow"></b>
					</li>

					<li class="<?php echo $isSchedulePage ? 'active' : ''; ?>">
						<a href="schedule.php">
							<i class="menu-icon fa fa-calendar"></i>
							<span class="menu-text"> Schedule </span>
						</a>

						<b class="arrow"></b>
					</li>

					<li class="<?php echo $isPaymentsPage ? 'active' : ''; ?>">
						<a href="payments.php">
							<i class="menu-icon fa fa-credit-card"></i>
							<span class="menu-text"> Payments / Invoices </span>
						</a>

						<b class="arrow"></b>
					</li>

					<li class="<?php echo $isInventoryPage ? 'active' : ''; ?>">
						<a href="inventory.php">
							<i class="menu-icon fa fa-cubes"></i>
							<span class="menu-text"> Inventory </span>
						</a>

						<b class="arrow"></b>
					</li>

<?php if ($canViewReports): ?>
					<li class="<?php echo $isReportsPage ? 'active' : ''; ?>">
						<a href="reports.php">
							<i class="menu-icon fa fa-bar-chart"></i>
							<span class="menu-text"> Reports </span>
						</a>

						<b class="arrow"></b>
					</li>
<?php endif; ?>

					<li class="<?php echo $isUsersPage ? 'active' : ''; ?>">
						<a href="users.php">
							<i class="menu-icon fa fa-users"></i>
							<span class="menu-text"> Users </span>
						</a>

						<b class="arrow"></b>
					</li>

<?php if ($isAdminUser): ?>
					<li class="<?php echo $isSettingsPage ? 'active' : ''; ?>">
						<a href="settings.php">
							<i class="menu-icon fa fa-cog"></i>
							<span class="menu-text"> Settings </span>
						</a>

						<b class="arrow"></b>
					</li>
<?php endif; ?>
				</ul><!-- /.nav-list -->

				<div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
					<i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
				</div>
			</div>
