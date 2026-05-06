<?php
$assetPath = $assetPath ?? '../../assets';
$includeBootstrapScript = $includeBootstrapScript ?? true;
$includeExcanvas = $includeExcanvas ?? false;
$includeAceScripts = $includeAceScripts ?? true;
$pagePluginScripts = $pagePluginScripts ?? [];
?>
		<!-- basic scripts -->

		<!--[if !IE]> -->
		<script src="<?php echo $assetPath; ?>/js/jquery-2.1.4.min.js"></script>

		<!-- <![endif]-->

		<!--[if IE]>
<script src="<?php echo $assetPath; ?>/js/jquery-1.11.3.min.js"></script>
<![endif]-->
		<script type="text/javascript">
			if('ontouchstart' in document.documentElement) document.write("<script src='<?php echo $assetPath; ?>/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
		</script>
<?php if ($includeBootstrapScript): ?>
		<script src="<?php echo $assetPath; ?>/js/bootstrap.min.js"></script>
<?php endif; ?>

<?php if ($includeExcanvas || !empty($pagePluginScripts)): ?>
		<!-- page specific plugin scripts -->

<?php endif; ?>
<?php if ($includeExcanvas): ?>
		<!--[if lte IE 8]>
		  <script src="<?php echo $assetPath; ?>/js/excanvas.min.js"></script>
		<![endif]-->
<?php endif; ?>
<?php foreach ($pagePluginScripts as $script): ?>
		<script src="<?php echo htmlspecialchars($script, ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endforeach; ?>

<?php if ($includeAceScripts): ?>
		<!-- ace scripts -->
		<script src="<?php echo $assetPath; ?>/js/ace-elements.min.js"></script>
		<script src="<?php echo $assetPath; ?>/js/ace.min.js"></script>

<?php endif; ?>
