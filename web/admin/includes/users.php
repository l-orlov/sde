<?
// Определяем базовый путь, если он еще не определен
if (!isset($basePath)) {
    include __DIR__ . '/path_helper.php';
    $basePath = getAdminBasePath();
}

// Подключаемся к БД если еще не подключены
if (!isset($link)) {
    include getIncludesFilePath('functions.php');
    DBconnect();
}

$query = "SELECT COUNT(id) as cprod FROM users";

$result = mysqli_query($link, $query) or die("SQL query error: " . basename(__FILE__) . " <b>$query</b><br>at line: " . __LINE__);

$row = mysqli_fetch_array($result, MYSQLI_ASSOC);

$count = $row['cprod'];

$busc = '';
?>

<div id="debug"></div>

<!-- Begin Page Content -->

<div class="container-fluid">

	<h1 class="h3 mb-2 text-gray-800">Usuarios</h1>

	<div class="row">
		<!-- Левая колонка: Список пользователей -->
		<div class="col-md-4">
			<div class="card shadow mb-4">
				<div class="card-body">
					<h6 class="m-0 font-weight-bold text-primary py-3">Lista de usuarios: <?= $count ?></h6>
					
					<!-- Поиск -->
					<div class="adm_busc">
						<input class="adm_busc_input" type="text" id="busc_texto" placeholder="Buscar...">
						<div class="adm_busc_bt" onclick="user_list_by_filter()">Buscar</div>
					</div>

					<!-- Кнопка добавления -->
					<div class="addnew_ico" onclick="user_add_open()">
						<img id="plusIcon" src="<?= $basePath ?>img/plus.png" class="icon-size">
					</div>

					<!-- Форма добавления -->
					<div class="addnew">
						<div class="form-field-group">
							<div class="adm_add_tit">Nombre de la Empresa:</div>
							<div class="adm_add_txt"><input class="add_input" type="text" id="company_name"></div>
						</div>

						<div class="form-field-group">
							<div class="adm_add_tit">CUIL/CUIT:</div>
							<div class="adm_add_txt"><input class="add_input" type="text" id="tax_id"></div>
						</div>

						<div class="form-field-group">
							<div class="adm_add_tit">Correo electrónico:</div>
							<div class="adm_add_txt"><input class="add_input" type="text" id="email"></div>
						</div>

						<div class="form-field-group">
							<div class="adm_add_tit">Teléfono:</div>
							<div class="adm_add_txt"><input class="add_input" type="text" id="phone"></div>
						</div>

						<div class="form-field-group">
							<div class="adm_add_tit">Contraseña:</div>
							<div class="adm_add_txt"><input class="add_input" type="text" id="password"></div>
						</div>

						<div class="form-field-group">
							<div class="adm_add_tit">Es Administrador:</div>
							<div class="adm_add_txt">
								<select class="add_input" id="is_admin">
									<option value="0">No</option>
									<option value="1">Sí</option>
								</select>
							</div>
						</div>

						<div class="form-field-group" style="text-align:right; margin-top: 10px;">
							<button type="button" onclick="user_create()" style="background: #0082C6; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">
								Guardar
							</button>
						</div>
					</div>

					<!-- Список пользователей -->
					<div class="table-responsive users-simple" id="user_list"></div>

					<div class="dataTables_info" id="dataTable_info" role="status" aria-live="polite"></div>
				</div>
			</div>
		</div>

		<!-- Правая колонка: Детальная форма -->
		<div class="col-md-8">
			<div class="card shadow mb-4">
				<div class="card-body">
					<h6 class="m-0 font-weight-bold text-primary py-3">Datos del Usuario</h6>
					<div id="user_detail_form" class="user-detail-form">
						<div class="user-detail-empty">
							<p>Seleccione un usuario de la lista para ver sus datos.</p>
						</div>
					</div>
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
// Устанавливаем глобально для использования в других скриптах
window.basePath = basePath;

var currentSelectedUserId = null;

function user_add_open() {
    let st = document.querySelector('.addnew');
    let plusIcon = document.getElementById('plusIcon');

    if (st.style.display === 'flex' || st.style.display === '') {
        st.style.display = 'none';
        plusIcon.src = basePath + "img/plus.png";
    } else {
        st.style.display = 'flex';
        plusIcon.src = basePath + "img/close.png";
    }
}

function user_list(pg, busc) {
	document.getElementById('user_list').innerHTML = '<img class="loading" src="' + basePath + 'img/loading_modern.gif">';

	fetch(basePath + 'includes/users_list_simple_js.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({ pg: 0, busc })
	})
	.then(response => response.json()) 
	.then(data => {
		document.getElementById('debug').innerHTML = data.debug || '';
		document.getElementById('user_list').innerHTML = data.res;
		document.getElementById('dataTable_info').innerHTML = 'Mostrando: ' + data.cant + ' usuarios';
		
		// Восстанавливаем выделение если был выбран пользователь
		if (currentSelectedUserId) {
			highlightUser(currentSelectedUserId);
		}
	})
	.catch(error => {
		console.error('Failed to get users list:', error);
		document.getElementById('user_list').innerHTML = '<p style="color:red;">Error al obtener la lista de usuarios</p>';
	});
}

function highlightUser(userId) {
	// Убираем выделение со всех строк
	document.querySelectorAll('.user-row').forEach(row => {
		row.style.backgroundColor = '';
	});
	
	// Выделяем выбранного пользователя
	const rows = document.querySelectorAll(`[id^="user_row"][id*="_${userId}"]`);
	rows.forEach(row => {
		row.style.backgroundColor = '#e3f2fd';
	});
}

function selectUser(userId) {
	currentSelectedUserId = userId;
	highlightUser(userId);
	loadUserFullData(userId);
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
			// Удаляем строки из списка
			document.querySelectorAll(`[id*="_${id}"]`).forEach(el => el.style.display = "none");
			
			// Очищаем форму справа если удалили выбранного пользователя
			if (currentSelectedUserId == id) {
				currentSelectedUserId = null;
				document.getElementById('user_detail_form').innerHTML = '<div class="user-detail-empty"><p>Seleccione un usuario de la lista para ver sus datos.</p></div>';
			}

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
	user_list(0, busc);
}

// Загрузка полных данных пользователя
function loadUserFullData(userId) {
	const formContainer = document.getElementById('user_detail_form');
	formContainer.innerHTML = '<div class="text-center p-4"><img class="loading" src="' + basePath + 'img/loading_modern.gif"></div>';
	
	fetch(basePath + 'includes/users_get_full_data_js.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({ user_id: userId })
	})
	.then(response => {
		// Проверяем, что ответ действительно JSON
		const contentType = response.headers.get('content-type');
		if (!contentType || !contentType.includes('application/json')) {
			return response.text().then(text => {
				console.error('Respuesta no es JSON:', text);
				throw new Error('El servidor devolvió una respuesta no válida: ' + text.substring(0, 200));
			});
		}
		return response.json();
	})
	.then(data => {
		if (data.ok === 1 && data.data) {
			displayUserForm(data.data, userId);
			// Инициализируем отслеживание изменений после отображения формы
			setTimeout(() => {
				if (typeof initChangeTracking === 'function') {
					initChangeTracking(data.data);
				}
			}, 300);
		} else {
			formContainer.innerHTML = '<div class="alert alert-danger">Error: ' + (data.err || 'Error desconocido') + '</div>';
		}
	})
	.catch(error => {
		console.error('Error loading user data:', error);
		formContainer.innerHTML = '<div class="alert alert-danger">Error de conexión al cargar los datos: ' + error.message + '</div>';
	});
}

// Отображение формы с данными пользователя
function displayUserForm(data, userId) {
	// Сбрасываем отслеживание изменений при загрузке новой формы
	if (typeof changedFields !== 'undefined') {
		changedFields = {};
	}
	if (typeof originalFormData !== 'undefined') {
		originalFormData = {};
	}
	
	const form = generateUserFormHTML(data, userId);
	document.getElementById('user_detail_form').innerHTML = form;
}

user_list(0, '');
</script>

<script src="<?= $basePath ?>js/user_detail_form.js"></script>

