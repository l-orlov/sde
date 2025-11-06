<div class="contener">
	<div class="login_main_box">
    <div class="login_box">
        <div class="login_logo">
            <img src="img/logo.png">
        </div>
		<div class="login_lang" onclick="toggleLangMenu()">
			<img src="img/icons/lang.png" />
			<span id="current-lang">Es</span>
			<ul id="login_lang_menu" class="login_lang_menu hidden">
				<li onclick="setLang('reg', 'es')">Español</li>
				<li onclick="setLang('reg', 'en')">English</li>
				<li onclick="setLang('reg', 'ru')">Русский</li>
			</ul>
		</div>
        <div class="login_tit" data-i18n="register_title">REGISTRO DE DATOS</div>
        <div class="login_data_box">
            <div class="logins">
                <div class="login_input_box">
                    <div class="login_input_inp">
                        <input type="Text" id="lastname" placeholder="Ingrese su Apellido">
                        <!-- data-i18n-placeholder="register_lastname_placeholder" -->
                    </div>
                </div>
                <div class="login_input_box">
                    <div class="login_input_inp">
                        <input type="Text" id="firstname" placeholder="Ingrese su Nombre">
                        <!-- data-i18n-placeholder="register_firstname_placeholder" -->
                    </div>
                </div>
                <div class="login_input_box">
                    <div class="login_input_inp">
                        <input type="Text" id="mail" placeholder="Ingrese su Correo electrónico">
                        <!-- data-i18n-placeholder="register_phone_placeholder -->
                    </div>
                </div>
                <div class="login_input_box">
                    <div class="login_input_inp">
                        <input type="Text" id="phone" placeholder="Ingrese su Número de WhatsApp">
                        <!-- data-i18n-placeholder="register_pass_placeholder" -->
                    </div>
                </div>
                <div class="login_input_box">
                    <div class="login_input_inp">
                        <input type="Password" id="pass" placeholder="Ingrese su Contraseña">
                        <!-- data-i18n-placeholder="register_repass_placeholder" -->
                    </div>
                </div>
                <div class="login_input_box">
                    <div class="login_input_inp">
                        <input type="Password" id="repass" placeholder="Confirmar Contraseña">
                        <!-- data-i18n-placeholder="register_repass_placeholder" -->
                    </div>
                </div>
            </div>
            <div class="general_bt login_input_bt" data-i18n="register_button" onclick="regUser()">Guardar Datos</div>
			<div class="login_info_msg" id="login_info_msg"></div>
        </div>
    </div>
	</div>
</div>
<script src="/js/i18n.js?v=1.0.2"></script>
<script>
function toggleLangMenu() {
  const menu = document.getElementById('login_lang_menu');
  menu.classList.toggle('hidden');
}
document.addEventListener('DOMContentLoaded', () => {
  // Установка текущего языка при загрузке
  initLang('reg');
});
// (опционально) закрытие меню по клику вне
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

	let lastname = document.getElementById('lastname').value.trim();
	let firstname = document.getElementById('firstname').value.trim();
	let mail = document.getElementById('mail').value.trim();
	let phone = document.getElementById('phone').value.trim();
	let pass = document.getElementById('pass').value.trim();
	let repass = document.getElementById('repass').value.trim();
	
	let requiredFields = { lastname, firstname, mail, phone, pass, repass };
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
		lastname: lastname,
		firstname: firstname,
		mail: mail,
		phone: phone,
		pass: pass
	};
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
	document.getElementById('lastname').value='';
	document.getElementById('firstname').value='';
	document.getElementById('mail').value='';
	document.getElementById('phone').value='';
	document.getElementById('pass').value='';
	document.getElementById('repass').value='';
	// Clear message
	document.getElementById('login_info_msg').innerHTML = '';
}
</script>
