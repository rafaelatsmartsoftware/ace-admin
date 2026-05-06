<?php
require_once __DIR__ . '/session.php';

$pageTitle = $pageTitle ?? 'Dashboard - Smart ERP';
$pageDescription = $pageDescription ?? 'overview & stats';
$bodyClass = $bodyClass ?? 'no-skin';
$assetPath = $assetPath ?? '../../assets';
$aceStylesheetAttributes = $aceStylesheetAttributes ?? 'class="ace-main-stylesheet" id="main-ace-style"';
$includeAceSkins = $includeAceSkins ?? true;
$includeAceExtra = $includeAceExtra ?? true;
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>

		<meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="<?php echo $assetPath; ?>/css/bootstrap.min.css" />
		<link rel="stylesheet" href="<?php echo $assetPath; ?>/font-awesome/4.5.0/css/font-awesome.min.css" />

		<?php if (!empty($pageStylesheets)): ?>
			<?php foreach ($pageStylesheets as $stylesheet): ?>
		<link rel="stylesheet" href="<?php echo htmlspecialchars($stylesheet, ENT_QUOTES, 'UTF-8'); ?>" />
			<?php endforeach; ?>
		<?php else: ?>
		<!-- page specific plugin styles -->
		<?php endif; ?>

		<!-- text fonts -->
		<link rel="stylesheet" href="<?php echo $assetPath; ?>/css/fonts.googleapis.com.css" />

		<!-- ace styles -->
		<link rel="stylesheet" href="<?php echo $assetPath; ?>/css/ace.min.css" <?php echo $aceStylesheetAttributes; ?> />

		<!--[if lte IE 9]>
			<link rel="stylesheet" href="<?php echo $assetPath; ?>/css/ace-part2.min.css" <?php echo $aceStylesheetAttributes; ?> />
		<![endif]-->
<?php if ($includeAceSkins): ?>
		<link rel="stylesheet" href="<?php echo $assetPath; ?>/css/ace-skins.min.css" />
<?php endif; ?>
		<link rel="stylesheet" href="<?php echo $assetPath; ?>/css/ace-rtl.min.css" />

		<!--[if lte IE 9]>
		  <link rel="stylesheet" href="<?php echo $assetPath; ?>/css/ace-ie.min.css" />
		<![endif]-->

		<?php if (empty($pageInlineStyles)): ?>
		<!-- inline styles related to this page -->
		<?php else: ?>
		<?php echo $pageInlineStyles . "\n"; ?>
		<?php endif; ?>

<?php if ($includeAceExtra): ?>
		<!-- ace settings handler -->
		<script src="<?php echo $assetPath; ?>/js/ace-extra.min.js"></script>

<?php endif; ?>
		<!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->

		<!--[if lte IE 8]>
		<script src="<?php echo $assetPath; ?>/js/html5shiv.min.js"></script>
		<script src="<?php echo $assetPath; ?>/js/respond.min.js"></script>
		<![endif]-->
	</head>

	<body class="<?php echo htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8'); ?>">
