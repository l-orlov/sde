<?
// Определяем базовый путь, если он еще не определен
if (!isset($basePath)) {
    include __DIR__ . '/path_helper.php';
    $basePath = getAdminBasePath();
}

$query = "SELECT COUNT(id) as cprod FROM users";

$result = mysqli_query($link, $query) or die("SQL query error: " . basename(__FILE__) . " <b>$query</b><br>at line: " . __LINE__);

$row = mysqli_fetch_array($result, MYSQLI_ASSOC);

$count = $row['cprod'];

$cc = ceil($count / 250);

$busc = '';
?>

<div id="debug"></div>

<!-- Begin Page Content -->

<div class="container-fluid">

	<h1 class="h3 mb-2 text-gray-800">Usuarios</h1>

	<div class="card shadow mb-4">

		<div class="card-body">

			<h6 class="m-0 font-weight-bold text-primary py-3">Lista de usuarios:

				<?= $count ?>

			</h6>

			<div class="pager">

				<? for ($i = 0; $i < $cc; $i++) { ?>

					<div class="pgr_box" id="pager_<?= $i ?>" onclick="user_list(<?= $i ?>, '<?=$busc?>')">

						<?= $i + 1 ?>

					</div>

				<? } ?>

			</div>

			<div class="uploadload" id="uploadload"></div>

            <div class="addnew_ico" onclick="user_add_open()">
                <img id="plusIcon" src="<?= $basePath ?>img/plus.png" class="icon-size">
            </div>

			<div class="addnew">

            <div class="adm_add_tit">Nombre de la Empresa:</div>
				<div class="adm_add_txt"><input class="add_input" type="text" id="company_name"></div>

				<div class="adm_add_tit">CUIL/CUIT:</div>
				<div class="adm_add_txt"><input class="add_input" type="text" id="tax_id"></div>

				<div class="adm_add_tit">Correo electrónico:</div>
				<div class="adm_add_txt"><input class="add_input" type="text" id="email"></div>

				<div class="adm_add_tit">Teléfono:</div>
				<div class="adm_add_txt"><input class="add_input" type="text" id="phone"></div>

				<div class="adm_add_tit">Contraseña:</div>
				<div class="adm_add_txt"><input class="add_input" type="text" id="password"></div>

				<div class="adm_add_tit">Es Administrador:</div>
				<div class="adm_add_txt">
					<select class="add_input" id="is_admin">
						<option value="0">No</option>
						<option value="1">Sí</option>
					</select>
				</div>

                <div style="grid-column: 1/-1; text-align:right; font-size:30px;" onclick="user_create()">
                    <img id="saveIcon" src="<?= $basePath ?>img/save.png" class="icon-size">
                </div>

			</div>

			<div class="table-responsive users" id="user_list"></div>

			<div class="row">

				<div class="col-sm-12 col-md-5">

					<div class="dataTables_info" id="dataTable_info" role="status" aria-live="polite"></div>

				</div>

			</div>

		</div>

	</div>

<script>
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

function user_add_open() {
    let st = document.querySelector('.addnew');
    let plusIcon = document.getElementById('plusIcon');

    if (st.style.display === 'grid') {
        st.style.display = 'none';
        plusIcon.src = basePath + "img/plus.png";
    } else {
        st.style.display = 'grid';
        plusIcon.src = basePath + "img/close.png";
    }
}
function user_list(pg, busc) {
	document.getElementById('user_list').innerHTML = '<img class="loading" src="' + basePath + 'img/loading_modern.gif">';

	fetch(basePath + 'includes/users_list_js.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({ pg, busc })
	})
	.then(response => response.json()) 
	.then(data => {
		document.getElementById('debug').innerHTML = data.debug || '';
		document.getElementById('user_list').innerHTML = data.res;
		document.getElementById('dataTable_info').innerHTML = 'Mostrando: ' + data.cant + ' usuarios';

		for (let i = 0; i < <?= $cc ?>; i++) {
			let pageElem = document.getElementById('pager_' + i);
			if (pageElem) pageElem.style.backgroundColor = "#CCC";
		}

		let activePageElem = document.getElementById('pager_' + pg);
		if (activePageElem) activePageElem.style.backgroundColor = "#999";
	})
	.catch(error => {
		console.error('Failed to get users list:', error);
		document.getElementById('user_list').innerHTML = '<p style="color:red;">Error al obtener la lista de usuarios</p>';
	});
}
function user_get_edit_form(id) {
    const editBox = document.getElementById('adm_list_edit_box' + id);
    const editIcon = document.getElementById('edit_icon_' + id);

    if (editBox.style.display === "grid") {
        editBox.style.display = "none";
        if (editIcon) editIcon.src = basePath + "img/edit.png";
        return;
    }

    fetch(basePath + 'includes/users_edit_form_js.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok === 1) {
            document.getElementById('debug').innerHTML = data.debug || '';
            editBox.innerHTML = data.res;
            editBox.style.display = "grid";

            if (editIcon) editIcon.src = basePath + "img/close.png";
        } else {
            console.error("Failed to get user edit form:", data.err);
            document.getElementById('debug').innerHTML = `<p style="color:red;">Error: ${data.err}</p>`;
        }
    })
    .catch(error => {
        console.error("Connection error:", error);
        document.getElementById('debug').innerHTML = `<p style="color:red;">Error al obtener el formulario de edición</p>`;
    });
}
function user_edit_save(id) {
    let data = {
        id:		                id,
        company_name:		    document.getElementById('company_name' + id)?.value    || '',
        tax_id:		            document.getElementById('tax_id' + id)?.value        || '',
        email:		            document.getElementById('email' + id)?.value            || '',
        phone:		            document.getElementById('phone' + id)?.value            || '',
        is_admin:		        document.getElementById('is_admin' + id)?.value        || '0'
    };

    fetch(basePath + 'includes/users_edit_js.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(responseData => {
        console.log(responseData);

        if (responseData.ok === 1) {
            const editIcon = document.getElementById('edit_icon_' + id);
            if (editIcon) editIcon.src = basePath + "img/edit.png";

            document.getElementById('adm_list_edit_box' + id).style.display = "none";

            document.getElementById('c0_' + id).innerHTML = responseData.user.id;
            document.getElementById('c1_' + id).innerHTML = responseData.user.company_name;
            document.getElementById('c2_' + id).innerHTML = responseData.user.tax_id;
            document.getElementById('c3_' + id).innerHTML = responseData.user.email;
            document.getElementById('c4_' + id).innerHTML = responseData.user.phone;
            document.getElementById('c5_' + id).innerHTML = responseData.user.is_admin == 1 ? 'Sí' : 'No';
            document.getElementById('c6_' + id).innerHTML = responseData.user.created_at;
            document.getElementById('c7_' + id).innerHTML = responseData.user.updated_at;
        } else {
            console.error("Failed to save:", responseData.err);
            document.getElementById('debug').innerHTML = `<p style="color:red;">Error: ${responseData.err}</p>`;
        }
    })
    .catch(error => {
        console.error("Connection error:", error);
        document.getElementById('debug').innerHTML = `<p style="color:red;">Error de conexión</p>`;
    });
}
function user_del(id) {
	if (!confirm("¿Está seguro de eliminar estos datos?\nNo hay forma de recuperar los datos eliminados")) {
        return;
    }

	fetch(basePath + 'includes/users_del_js.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({ id: id })
	})
	.then(response => response.json()) 
	.then(data => {
		if (data.ok === 1) {
			document.querySelectorAll(`[id^=c][id$=_${id}]`).forEach(el => el.style.display = "none");

            let countElem = document.getElementById('dataTable_info');
            if (countElem) {
                let countText = countElem.innerText;
                let currentCount = parseInt(countText.split(':')[1].trim());
                if (currentCount > 0) {
                    countElem.innerHTML = 'Mostrando: ' + (currentCount - 1) + ' usuarios';
                }
            }
		} else {
			console.error("Failed to delete:", data.err);
			document.getElementById('debug').innerHTML = `<p style="color:red;">Error: ${data.err}</p>`;
		}
	})
	.catch(error => {
		console.error("Connection error:", error);
		document.getElementById('debug').innerHTML = `<p style="color:red;">Error de conexión</p>`;
	});
}
function user_create() {
    let data = {
        company_name:	document.getElementById('company_name')?.value	|| '',
        tax_id:		    document.getElementById('tax_id')?.value		    || '',
        email:		    document.getElementById('email')?.value		    || '',
        phone:		    document.getElementById('phone')?.value		    || '',
        password:		document.getElementById('password')?.value		|| '',
        is_admin:		document.getElementById('is_admin')?.value		|| '0'
    };

    fetch(basePath + 'includes/users_create_js.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(responseData => {
        console.log(responseData);
        if (responseData.ok === 1) {
            document.querySelector('.addnew').style.display = 'none';
            document.getElementById('plusIcon').src = basePath + "img/plus.png";

            document.getElementById('company_name').value = '';
            document.getElementById('tax_id').value = '';
            document.getElementById('email').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('password').value = '';
            document.getElementById('is_admin').value = '0';

            user_list(0, '');
        } else {
            console.error("Failed to save:", responseData.err);
            document.getElementById('debug').innerHTML = `<p style="color:red;">Error: ${responseData.err}</p>`;
        }
    })
    .catch(error => {
        console.error("Connection error:", error);
        document.getElementById('debug').innerHTML = `<p style="color:red;">Error de conexión</p>`;
    });
}
function user_list_by_filter() {
	let busc = document.getElementById('busc_texto').value;
	user_list(0,busc);
}

user_list(0,'');
</script>

