<div class="contener">
	<div class="login_main_box">
    <div class="login_box">
        <div class="login_logo">
            <img src="img/logo.svg">
        </div>
		<div class="login_lang" onclick="toggleLangMenu()">
			<img src="img/icons/lang.png" />
			<span id="current-lang">Es</span>
			<ul id="login_lang_menu" class="login_lang_menu hidden">
				<li onclick="setLang('regnew', 'es')">Español</li>
				<li onclick="setLang('regnew', 'en')">English</li>
			</ul>
		</div>
        <div class="login_tit" data-i18n="register_title">REGISTRO DE DATOS</div>
        <div class="login_data_box">
            <div class="logins">
                <div class="login_input_box">
                    <div class="login_input_inp">
                        <input type="Text" id="company_name" data-i18n-placeholder="register_company_name_placeholder">
                    </div>
                </div>
                <div class="login_input_box">
                    <div class="login_input_inp">
                        <input type="text" id="tax_id" name="tax_id" data-i18n-placeholder="register_tax_id_placeholder" placeholder="XX-XXXXXXXX-X" inputmode="numeric" maxlength="13">
                    </div>
                </div>
                <div class="login_input_box">
                    <div class="login_input_inp">
                        <input type="Text" id="mail" data-i18n-placeholder="register_mail_placeholder">
                    </div>
                </div>
                <div class="login_input_box">
                    <div class="login_input_inp">
                        <input type="Text" id="phone" data-i18n-placeholder="register_phone_placeholder">
                    </div>
                </div>
                <div class="login_input_box">
                    <div class="login_input_inp">
                        <input type="Password" id="pass" data-i18n-placeholder="register_pass_placeholder">
                    </div>
                </div>
                <div class="login_input_box">
                    <div class="login_input_inp">
                        <input type="Password" id="repass" data-i18n-placeholder="register_repass_placeholder">
                    </div>
                </div>
            </div>
            <div class="general_bt login_input_bt" data-i18n="register_button" onclick="regUser()">Guardar Datos</div>
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
  initLang('regnew');
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

function regUser() {
	const msgEl = document.getElementById('login_info_msg');
	msgEl.innerHTML = '';
	msgEl.classList.remove('err');

	let company_name = document.getElementById('company_name').value.trim();
	let tax_idRaw = document.getElementById('tax_id').value.trim();
	let tax_idDigits = (tax_idRaw || '').replace(/\D/g, '');
	let mail = document.getElementById('mail').value.trim();
	let phone = document.getElementById('phone').value.trim();
	let pass = document.getElementById('pass').value.trim();
	let repass = document.getElementById('repass').value.trim();

	// CUIT: obligatorio exactamente 11 dígitos (bloquear registro si no)
	if (tax_idDigits.length !== 11 || !/^\d{11}$/.test(tax_idDigits)) {
		msgEl.innerHTML = 'CUIT / Identificación Fiscal debe tener exactamente 11 dígitos';
		msgEl.classList.add('err');
		document.getElementById('tax_id').focus();
		return;
	}

	let requiredFields = { company_name, tax_id: tax_idDigits, mail, phone, pass, repass };
	for (let field in requiredFields) {
		const val = String(requiredFields[field]).trim();
		if (!val) {
			msgEl.innerHTML = `Todos los campos deben estar completos. (Campo: ${field})`;
			msgEl.classList.add('err');
			return;
		}
	}
	
	if (pass != repass) {
		msgEl.innerHTML = "Contraseña y confirmacion de contraseña no son iguales";
		msgEl.classList.add('err');
		return;
	}

	let senddata = {
		company_name: company_name,
		tax_id: tax_idDigits,
		mail: mail,
		phone: phone,
		pass: pass
	};
	// Solo enviar si CUIT tiene 11 dígitos (ya validado arriba)
	fetch('includes/regnew_js.php', { method: 'POST', headers: { 'Content-Type': 'application/json', }, body: JSON.stringify( senddata )})
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
	document.getElementById('company_name').value='';
	document.getElementById('tax_id').value='';
	document.getElementById('mail').value='';
	document.getElementById('phone').value='';
	document.getElementById('pass').value='';
	document.getElementById('repass').value='';
	// Clear message
	document.getElementById('login_info_msg').innerHTML = '';
}
</script>
