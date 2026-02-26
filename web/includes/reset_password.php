<?
$token = isset($_GET['token']) ? trim((string) $_GET['token']) : '';
if ($token === '') {
    header('Location: ?page=login');
    exit;
}
?>
<div class="contener">
	<div class="login_main_box">
		<div class="login_box">
			<div class="login_logo">
                <img src="img/logo.svg">
			</div>
			<div class="login_tit" data-i18n="login_reset_title">Restablecer contraseña</div>
			<div class="login_data_box">
				<p class="login_reset_hint" style="margin-bottom: 16px; color: #666; font-size: 14px;" data-i18n="login_reset_hint">Ingrese su nueva contraseña (mínimo 6 caracteres).</p>
				<div class="login_input_box" style="margin-bottom: 12px;">
					<div class="login_input_inp">
						<input type="password" id="reset_password" data-i18n-placeholder="login_reset_password_placeholder" placeholder="Nueva contraseña" minlength="6" style="width: 100%;">
					</div>
				</div>
				<div class="login_input_box" style="margin-bottom: 16px;">
					<div class="login_input_inp">
						<input type="password" id="reset_password_confirm" data-i18n-placeholder="login_reset_confirm_placeholder" placeholder="Repetir contraseña" minlength="6" style="width: 100%;">
					</div>
				</div>
				<div class="general_bt login_input_bt" id="btn_reset_submit" data-i18n="login_reset_submit">Guardar nueva contraseña</div>
				<div class="login_info_msg" id="reset_info_msg"></div>
				<p style="margin-top: 16px;">
					<a href="?page=login" class="ahref_color text_btn" data-i18n="login_reset_back">Volver al inicio de sesión</a>
				</p>
			</div>
		</div>
	</div>
</div>
<script src="js/i18n.js?v=<?= asset_version('js/i18n.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', async function() {
	await initLang('login');
	const token = <?= json_encode($token) ?>;
	const btn = document.getElementById('btn_reset_submit');
	const msgEl = document.getElementById('reset_info_msg');
	const passEl = document.getElementById('reset_password');
	const confirmEl = document.getElementById('reset_password_confirm');
	const dict = window.__i18nDict || {};

	function t(key) { return dict[key] || key; }

	function showMsg(text, isError) {
		msgEl.textContent = text;
		msgEl.className = isError ? 'login_info_msg err' : 'login_info_msg';
		msgEl.style.display = 'block';
	}

	btn.addEventListener('click', async function() {
		msgEl.textContent = '';
		msgEl.classList.remove('err');
		const password = (passEl && passEl.value) ? passEl.value : '';
		const passwordConfirm = (confirmEl && confirmEl.value) ? confirmEl.value : '';
		if (password.length < 6) {
			showMsg(t('login_reset_err_length'), true);
			return;
		}
		if (password !== passwordConfirm) {
			showMsg(t('login_reset_err_mismatch'), true);
			return;
		}
		btn.disabled = true;
		btn.textContent = t('login_reset_saving');
		try {
			const res = await fetch('includes/login_reset_js.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ token: token, password: password, password_confirm: passwordConfirm })
			});
			const data = await res.json();
			if (data.ok === 1) {
				showMsg(data.res || t('login_reset_success'), false);
				setTimeout(function() { window.location.href = '?page=login'; }, 2000);
			} else {
				showMsg(data.err || t('login_reset_err_fail'), true);
				btn.disabled = false;
				btn.textContent = t('login_reset_submit');
			}
		} catch (e) {
			showMsg(t('login_reset_err_connection'), true);
			btn.disabled = false;
			btn.textContent = t('login_reset_submit');
		}
	});
});
</script>
