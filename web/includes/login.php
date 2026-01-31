<?
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    session_destroy();
    header('Location: ?page=login');
    exit();
}
?>
<div class="contener">
	<div class="login_main_box">
		<div class="login_box">
			<div class="login_logo">
                <img src="img/logo.svg">
			</div>
			<div class="login_lang" onclick="toggleLangMenu()">
				<img src="img/icons/lang.png">
				<span id="current-lang">Es</span>
				<ul id="login_lang_menu" class="login_lang_menu hidden">
					<li onclick="setLang('login', 'es')">Español</li>
					<li onclick="setLang('login', 'en')">English</li>
				</ul>
			</div>
			<div class="login_tit" data-i18n="login_title">LOG-IN</div>
			<div class="login_data_box">
				<div class="logins">
					<div class="login_input_box">
						<div class="login_input_inp">
							<input style="width: 100%;" type="text" id="tax_id" name="tax_id" data-i18n-placeholder="login_tax_id_placeholder" placeholder="XX-XXXXXXXX-X" inputmode="numeric" maxlength="13">
						</div>
						<div class="login_input_ico">
							<img src="img/icons/telephone.png">
						</div>
					</div>
					<div class="login_input_box">
						<div class="login_input_inp">
							<input style="width: 100%;" type="Password" id="pass" data-i18n-placeholder="login_pass_placeholder">
						</div>
						<div class="login_input_ico">
							<img src="img/icons/key.png">
						</div>
					</div>
				</div>
				<div class="login_text">
					<div class="login_registr">
						<div class="login_input_forgot">
							<a class="salir ahref_color text_btn" data-i18n="login_register" onclick="location.href='?page=regnew';">REGISTRAR NUEVO</a>
						</div>
						<div class="login_input_forgot text_btn" data-i18n="login_forgot" onclick="forgot_pass()">ME OLVIDÉ LA CONTRASEÑA</div>
					</div>
				</div>
				<div class="general_bt login_input_bt" onclick="login()" data-i18n="login_button">INGRESAR</div>
				<div class="login_info_msg" id="login_info_msg"></div>
			</div>
		</div>
	</div>
</div>
<script src="js/i18n.js?v=<?= asset_version('js/i18n.js') ?>"></script>
<script>
function toggleLangMenu() {
  const menu = document.getElementById('login_lang_menu');
  menu.classList.toggle('hidden');
}
document.addEventListener('DOMContentLoaded', () => {
  initLang('login');
  // Маска CUIT: 11 dígitos, formato 20-18858351-3 (guión como / en fecha)
  const taxIdInput = document.getElementById('tax_id');
  if (taxIdInput) {
    const formatCuit = (v) => {
      v = v.replace(/\D/g, '').slice(0, 11);
      if (v.length <= 2) return v;
      if (v.length <= 9) return v.slice(0, 2) + '-' + v.slice(2);
      if (v.length === 10) return v.slice(0, 2) + '-' + v.slice(2, 10) + '-';
      return v.slice(0, 2) + '-' + v.slice(2, 10) + '-' + v.slice(10, 11);
    };
    taxIdInput.addEventListener('input', function() {
      this.value = formatCuit(this.value);
    });
  }
});
document.addEventListener('click', function (e) {
  const langBox = document.querySelector('.login_lang');
  const menu = document.getElementById('login_lang_menu');
  if (!langBox.contains(e.target)) {
    menu.classList.add('hidden');
  }
});

function login() {
	const msgEl = document.getElementById('login_info_msg');
	msgEl.innerHTML = '';
	msgEl.classList.remove('err');

	let tax_idRaw = document.getElementById('tax_id').value.trim();
	let tax_idDigits = tax_idRaw.replace(/\D/g, '');
	let tax_id = tax_idDigits;
	let pass = document.getElementById('pass').value.trim();
	
	let requiredFields = { tax_id, pass };
	for (let field in requiredFields) {
		const val = String(requiredFields[field]).trim();
		if (!val) {
			msgEl.innerHTML = `Todos los campos deben estar completos. (Campo: ${field})`;
			msgEl.classList.add('err');
			return;
		}
	}
	if (tax_idDigits.length !== 11) {
		msgEl.innerHTML = 'CUIT / Identificación Fiscal debe tener exactamente 11 dígitos';
		msgEl.classList.add('err');
		document.getElementById('tax_id').focus();
		return;
	}

	let senddata = {
		tax_id: tax_idDigits,
		pass: pass
	};
	fetch('includes/login_js.php', { method: 'POST', headers: { 'Content-Type': 'application/json', }, body: JSON.stringify( senddata )})
	.then(response => response.json())
	.then(data => {
		console.log(data);
		let res_json = JSON.stringify(data);
		let res_arr = JSON.parse(res_json);
		let ok = res_arr['ok'];
		let err = res_arr['err'];
		if ( ok == 1 ) {
			clearForm();

			// Redirect to home page
			window.location.href = '?page=home';
		} else {
			msgEl.innerHTML = err;
			msgEl.classList.add('err');
		}
	})
	.catch(error => {
		console.error('Error:', error);
		msgEl.innerHTML = 'Error de conexión. Intente de nuevo.';
		msgEl.classList.add('err');
	});
}
function clearForm() {
	// Clear fields
	document.getElementById('tax_id').value='';
	document.getElementById('pass').value='';
	// Clear message
	document.getElementById('login_info_msg').innerHTML = '';
}
</script>
