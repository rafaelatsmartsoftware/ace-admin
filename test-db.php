<?php
require_once __DIR__ . '/config/database.php';

$pdo = ace_admin_db();
$connected = $pdo instanceof PDO;
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>Database Test - Ace Admin</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
		<link rel="stylesheet" href="assets/css/ace.min.css" />
	</head>
	<body class="no-skin">
		<div class="main-container">
			<div class="main-content">
				<div class="page-content">
					<div class="page-header">
						<h1>Database Connection Test</h1>
					</div>

					<?php if ($connected): ?>
						<div class="alert alert-success">
							Database connection successful.
						</div>
					<?php else: ?>
						<div class="alert alert-danger">
							Database connection failed. Check config/database.php and make sure the database exists.
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</body>
</html>
