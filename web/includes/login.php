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
							<img src="img/icons/regfull_datos.svg">
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
	<!-- Overlay "Olvidé la contraseña": email + enviar -->
	<div id="login_forgot_overlay" class="login_forgot_overlay" style="display: none;">
		<div class="login_forgot_box">
			<div class="login_tit" style="margin-bottom: 16px;" data-i18n="login_forgot_title">Restablecer contraseña</div>
			<p class="login_forgot_text" data-i18n="login_forgot_hint">Ingrese su correo electrónico. Si está registrado, recibirá un enlace para crear una nueva contraseña.</p>
			<div class="login_input_box" style="margin: 16px 0;">
				<input type="email" id="login_forgot_email" data-i18n-placeholder="login_forgot_email_placeholder" placeholder="Correo electrónico" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px;">
			</div>
			<div class="login_forgot_msg" id="login_forgot_msg"></div>
			<div class="login_forgot_buttons">
				<button type="button" class="general_bt login_input_bt" id="login_forgot_send" data-i18n="login_forgot_send">Enviar enlace</button>
				<button type="button" class="general_bt login_input_bt" id="login_forgot_cancel" data-i18n="login_forgot_cancel" style="background: #666;">Cancelar</button>
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
  // Поле CUIT: только цифры, без формата с guiones
  const taxIdInput = document.getElementById('tax_id');
  if (taxIdInput) {
    taxIdInput.addEventListener('input', function() {
      this.value = this.value.replace(/\D/g, '').slice(0, 11);
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
	fetch('index.php?page=login_submit', { method: 'POST', headers: { 'Content-Type': 'application/json', }, body: JSON.stringify( senddata )})
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

function forgot_pass() {
	var overlay = document.getElementById('login_forgot_overlay');
	var emailInp = document.getElementById('login_forgot_email');
	var msgEl = document.getElementById('login_forgot_msg');
	if (overlay) {
		overlay.style.display = 'flex';
		if (emailInp) { emailInp.value = ''; emailInp.focus(); }
		if (msgEl) msgEl.textContent = '';
	}
}
document.addEventListener('DOMContentLoaded', function() {
	var overlay = document.getElementById('login_forgot_overlay');
	var cancelBtn = document.getElementById('login_forgot_cancel');
	var sendBtn = document.getElementById('login_forgot_send');
	var emailInp = document.getElementById('login_forgot_email');
	var msgEl = document.getElementById('login_forgot_msg');
	if (cancelBtn && overlay) {
		cancelBtn.addEventListener('click', function() { overlay.style.display = 'none'; });
	}
	if (sendBtn && overlay && emailInp && msgEl) {
		sendBtn.addEventListener('click', function() {
			var email = (emailInp.value || '').trim();
			if (!email) {
				msgEl.textContent = 'Ingrese su correo electrónico';
				msgEl.className = 'login_forgot_msg err';
				return;
			}
			sendBtn.disabled = true;
			msgEl.textContent = 'Enviando...';
			msgEl.className = 'login_forgot_msg';
			fetch('includes/login_forgot_js.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ email: email })
			})
			.then(function(r) { return r.json(); })
			.then(function(data) {
				if (data.ok === 1) {
					msgEl.textContent = data.res || 'Si el correo está registrado, recibirá un enlace.';
					msgEl.className = 'login_forgot_msg';
					msgEl.style.color = '#0a0';
				} else {
					msgEl.textContent = data.err || 'Error. Intente de nuevo.';
					msgEl.className = 'login_forgot_msg err';
				}
				sendBtn.disabled = false;
			})
			.catch(function() {
				msgEl.textContent = 'Error de conexión. Intente de nuevo.';
				msgEl.className = 'login_forgot_msg err';
				sendBtn.disabled = false;
			});
		});
	}
});
</script>
