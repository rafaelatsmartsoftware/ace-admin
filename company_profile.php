<?php
require_once __DIR__ . '/includes/session.php';
require_login('login.php');
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Company Profile - Ace Admin';
$pageDescription = 'salon and spa details';
$bodyClass = 'no-skin';
$includeAceSkins = true;
$includeAceExtra = true;

$company = null;
$databaseError = '';
$pdo = ace_admin_db();
$currentUser = current_user();
$isAdminUser = ($currentUser['role'] ?? '') === 'admin';

if (!$pdo instanceof PDO) {
	$databaseError = 'Unable to load company profile right now. Please check the database connection.';
} else {
	try {
		$statement = $pdo->prepare('SELECT * FROM company_settings WHERE id = :id LIMIT 1');
		$statement->execute(['id' => 1]);
		$company = $statement->fetch() ?: null;
	} catch (PDOException $exception) {
		error_log('Company profile load failed: ' . $exception->getMessage());
		$databaseError = 'Unable to load company profile right now. Please try again later.';
	}
}

function company_profile_escape($value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function company_profile_value(?array $company, string $key): string
{
	$value = trim((string) ($company[$key] ?? ''));

	return $value !== '' ? $value : 'Not configured';
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
							<li class="active">Company Profile</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Company Profile
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									salon and spa details
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
<?php if ($databaseError !== ''): ?>
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo company_profile_escape($databaseError); ?>
								</div>
<?php elseif (!$company): ?>
								<div class="alert alert-info">
									<i class="ace-icon fa fa-info-circle"></i>
									Company profile has not been configured yet.
								</div>
<?php if ($isAdminUser): ?>
								<a href="settings.php" class="btn btn-sm btn-primary">
									<i class="ace-icon fa fa-cog"></i>
									Configure Company Profile
								</a>
<?php endif; ?>
<?php else: ?>
								<div class="clearfix">
<?php if ($isAdminUser): ?>
									<a href="settings.php" class="btn btn-sm btn-primary pull-right">
										<i class="ace-icon fa fa-pencil"></i>
										Edit Settings
									</a>
<?php endif; ?>
								</div>
								<div class="space-6"></div>

								<div class="widget-box">
									<div class="widget-header">
										<h4 class="widget-title">
											<i class="ace-icon fa fa-building-o"></i>
											<?php echo company_profile_escape(company_profile_value($company, 'business_name')); ?>
										</h4>
									</div>

									<div class="widget-body">
										<div class="widget-main">
											<div class="row">
												<div class="col-sm-3 center">
<?php if (trim((string) ($company['logo'] ?? '')) !== ''): ?>
													<img src="<?php echo company_profile_escape($company['logo']); ?>" alt="<?php echo company_profile_escape(company_profile_value($company, 'business_name')); ?> Logo" class="img-responsive inline" style="max-height:160px" />
<?php else: ?>
													<div class="well well-sm">
														<i class="ace-icon fa fa-image bigger-300 grey"></i>
														<div class="space-6"></div>
														Logo not configured
													</div>
<?php endif; ?>
												</div>

												<div class="col-sm-9">
													<table class="table table-bordered table-striped">
														<tbody>
															<tr>
																<th class="col-sm-3">Phone</th>
																<td><?php echo company_profile_escape(company_profile_value($company, 'phone')); ?></td>
															</tr>
															<tr>
																<th>Email</th>
																<td><?php echo company_profile_escape(company_profile_value($company, 'email')); ?></td>
															</tr>
															<tr>
																<th>Website</th>
																<td>
<?php if (trim((string) ($company['website'] ?? '')) !== ''): ?>
																	<a href="<?php echo company_profile_escape($company['website']); ?>" target="_blank" rel="noopener">
																		<?php echo company_profile_escape($company['website']); ?>
																	</a>
<?php else: ?>
																	Not configured
<?php endif; ?>
																</td>
															</tr>
															<tr>
																<th>Main Address</th>
																<td><?php echo nl2br(company_profile_escape(company_profile_value($company, 'main_address'))); ?></td>
															</tr>
															<tr>
																<th>Description</th>
																<td><?php echo nl2br(company_profile_escape(company_profile_value($company, 'description'))); ?></td>
															</tr>
															<tr>
																<th>Facebook</th>
																<td>
<?php if (trim((string) ($company['facebook_url'] ?? '')) !== ''): ?>
																	<a href="<?php echo company_profile_escape($company['facebook_url']); ?>" target="_blank" rel="noopener">
																		<?php echo company_profile_escape($company['facebook_url']); ?>
																	</a>
<?php else: ?>
																	Not configured
<?php endif; ?>
																</td>
															</tr>
															<tr>
																<th>Instagram</th>
																<td>
<?php if (trim((string) ($company['instagram_url'] ?? '')) !== ''): ?>
																	<a href="<?php echo company_profile_escape($company['instagram_url']); ?>" target="_blank" rel="noopener">
																		<?php echo company_profile_escape($company['instagram_url']); ?>
																	</a>
<?php else: ?>
																	Not configured
<?php endif; ?>
																</td>
															</tr>
															<tr>
																<th>Opening Note</th>
																<td><?php echo nl2br(company_profile_escape(company_profile_value($company, 'opening_note'))); ?></td>
															</tr>
															<tr>
																<th>Status</th>
																<td>
<?php $statusClass = ($company['status'] ?? '') === 'active' ? 'label-success' : 'label-default'; ?>
																	<span class="label label-sm <?php echo $statusClass; ?>">
																		<?php echo company_profile_escape(company_profile_value($company, 'status')); ?>
																	</span>
																</td>
															</tr>
														</tbody>
													</table>
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
