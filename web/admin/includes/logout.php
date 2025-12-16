<?
session_destroy();
?>
<script type="text/javascript">
	// Определяем базовый путь автоматически
	var basePath = window.location.pathname;
	var adminPos = basePath.lastIndexOf('/admin');
	if (adminPos !== -1) {
		basePath = basePath.substring(0, adminPos + 6); // +6 для '/admin'
	} else {
		basePath = basePath.substring(0, basePath.lastIndexOf('/') + 1);
	}
	if (basePath[basePath.length - 1] !== '/') {
		basePath += '/';
	}
	window.location = basePath + "login.php";
</script>