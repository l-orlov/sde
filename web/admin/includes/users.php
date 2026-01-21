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

<div class="container-fluid users-admin-container">

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
							<button type="button" onclick="user_create()" style="background: #003399; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">
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
	
	// Инициализация обработчиков для новых полей
	setupFormEventHandlers();
}

function setupFormEventHandlers() {
	// Обработчик для чекбокса "Otros" в Factores de Diferenciación
	const otrosDiffCheckbox = document.querySelector('.diff-factor[value="Otros"]');
	const otrosDiffInput = document.getElementById('form_other_differentiation');
	if (otrosDiffCheckbox && otrosDiffInput) {
		otrosDiffCheckbox.addEventListener('change', function() {
			otrosDiffInput.disabled = !this.checked;
			if (!this.checked) {
				otrosDiffInput.value = '';
			}
		});
	}
	
	// Обработчик для чекбокса "Otros" в Necesidades
	const otrosNeedsCheckbox = document.querySelector('.need-option[value="Otros"]');
	const otrosNeedsInput = document.getElementById('form_other_needs');
	if (otrosNeedsCheckbox && otrosNeedsInput) {
		otrosNeedsCheckbox.addEventListener('change', function() {
			otrosNeedsInput.disabled = !this.checked;
			if (!this.checked) {
				otrosNeedsInput.value = '';
			}
		});
	}
	
	// Обработчик для кнопки добавления продукта
	const addProductBtn = document.getElementById('add_product_btn');
	if (addProductBtn) {
		addProductBtn.addEventListener('click', function() {
			const container = document.getElementById('products_list_container');
			if (!container) return;
			
			const productItems = container.querySelectorAll('.product-item-admin');
			const newIndex = productItems.length;
			
			const newProductHtml = '<div class="product-item-admin" data-product-id="" data-product-index="' + newIndex + '" data-product-type="product">' +
				'<h5 style="margin-top: 20px; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">Producto ' + (newIndex + 1) + '</h5>' +
				'<div class="form-group"><label>Producto <span class="req">*</span></label>' +
				'<input type="text" class="form-control product-name" data-index="' + newIndex + '" value="" required></div>' +
				'<div class="form-group"><label>Descripción <span class="req">*</span></label>' +
				'<input type="text" class="form-control product-description" data-index="' + newIndex + '" value="" required></div>' +
				'<div class="form-group"><label>Exportación Anual (USD)</label>' +
				'<input type="text" class="form-control product-export" data-index="' + newIndex + '" value=""></div>' +
				'</div>';
			
			container.insertAdjacentHTML('beforeend', newProductHtml);
		});
	}
	
	// Обработчик для кнопки добавления услуги
	const addServiceBtn = document.getElementById('add_service_btn');
	if (addServiceBtn) {
		addServiceBtn.addEventListener('click', function() {
			const container = document.getElementById('services_list_container');
			if (!container) return;
			
			const serviceItems = container.querySelectorAll('.service-item-admin');
			const newIndex = serviceItems.length;
			
			const activityOptions = [
				'Staff augmentation / provisión de perfiles especializados',
				'Implementadores de soluciones',
				'Ciencia de datos',
				'Análisis de datos y scraping',
				'Blockchain',
				'Biotecnología (servicios, prótesis)',
				'Turismo (servicios tecnológicos asociados)',
				'Marketing Digital',
				'Servicios de mantenimiento aeronáutico',
				'IA – servicios de desarrollo (bots de lenguaje natural, soluciones a medida)',
				'e-Government (soluciones para Estado provincial y municipios)',
				'Consultoría de procesos y transformación digital',
				'Diseño mecánico',
				'Diseño 3D',
				'Diseño multimedia',
				'Diseño de hardware',
				'Fintech',
				'Growth Marketing',
				'Economía del Conocimiento – Productos orientados a Salud',
				'Sistemas de facturación'
			];
			
			let activityOptionsHtml = '<option value="">...</option>';
			activityOptions.forEach(option => {
				activityOptionsHtml += '<option value="' + option.replace(/'/g, "&#39;") + '">' + option + '</option>';
			});
			
			const newServiceHtml = '<div class="service-item-admin" data-service-id="" data-service-index="' + newIndex + '" data-service-type="service">' +
				'<h5 style="margin-top: 20px; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">Servicio ' + (newIndex + 1) + '</h5>' +
				'<div class="form-group"><label>Actividad <span class="req">*</span></label>' +
				'<select class="form-control service-activity" data-index="' + newIndex + '" required>' + activityOptionsHtml + '</select></div>' +
				'<div class="form-group"><label>Servicio <span class="req">*</span></label>' +
				'<input type="text" class="form-control service-name" data-index="' + newIndex + '" value="" required></div>' +
				'<div class="form-group"><label>Descripción <span class="req">*</span></label>' +
				'<input type="text" class="form-control service-description" data-index="' + newIndex + '" value="" required></div>' +
				'<div class="form-group"><label>Exportación Anual (USD)</label>' +
				'<input type="text" class="form-control service-export" data-index="' + newIndex + '" value=""></div>' +
				'</div>';
			
			container.insertAdjacentHTML('beforeend', newServiceHtml);
		});
	}
	
	// Обработчики для удаления файлов
	document.querySelectorAll('.delete-file-btn').forEach(btn => {
		btn.addEventListener('click', function() {
			const fileId = this.getAttribute('data-file-id');
			if (!fileId) {
				console.error('No file ID found');
				return;
			}
			
			if (!confirm('¿Está seguro de eliminar este archivo?')) {
				return;
			}
			
			const url = basePath + 'includes/admin_delete_file_js.php';
			console.log('Deleting file:', fileId, 'URL:', url);
			
			fetch(url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ file_id: fileId })
			})
			.then(response => {
				console.log('Response status:', response.status);
				console.log('Response headers:', response.headers);
				
				// Проверяем, что ответ действительно JSON
				const contentType = response.headers.get('content-type');
				if (!contentType || !contentType.includes('application/json')) {
					return response.text().then(text => {
						console.error('Respuesta no es JSON:', text.substring(0, 500));
						throw new Error('El servidor devolvió una respuesta no válida: ' + text.substring(0, 100));
					});
				}
				return response.json();
			})
			.then(data => {
				console.log('Response data:', data);
				if (data.ok === 1) {
					// Удаляем элемент из DOM
					const fileItem = this.closest('.file-item-preview');
					if (fileItem) {
						fileItem.remove();
					}
					// Если это был последний файл, скрываем весь блок
					const filesPreview = this.closest('.files-preview');
					if (filesPreview && filesPreview.querySelectorAll('.file-item-preview').length === 0) {
						const formGroup = filesPreview.closest('.form-group');
						if (formGroup) {
							formGroup.remove();
						}
					}
				} else {
					alert('Error al eliminar archivo: ' + (data.err || 'Error desconocido'));
				}
			})
			.catch(error => {
				console.error('Error deleting file:', error);
				alert('Error de conexión al eliminar archivo: ' + error.message);
			});
		});
	});
	
	// Обработчики для удаления продуктов
	document.querySelectorAll('.delete-product-btn').forEach(btn => {
		btn.addEventListener('click', function() {
			const productId = this.getAttribute('data-product-id');
			if (!productId) return;
			
			if (!confirm('¿Está seguro de eliminar este producto? Se eliminarán también todas sus imágenes.')) {
				return;
			}
			
			fetch(basePath + 'includes/admin_delete_product_js.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ product_id: productId })
			})
			.then(response => response.json())
			.then(data => {
				if (data.ok === 1) {
					// Удаляем элемент из DOM
					const productItem = this.closest('.product-item-admin');
					if (productItem) {
						productItem.remove();
					}
					// Перезагружаем данные пользователя для обновления формы
					const userId = currentSelectedUserId;
					if (userId) {
						loadUserFullData(userId);
					}
				} else {
					alert('Error al eliminar producto: ' + (data.err || 'Error desconocido'));
				}
			})
			.catch(error => {
				console.error('Error deleting product:', error);
				alert('Error de conexión al eliminar producto');
			});
		});
	});
	
	// Обработчики для удаления услуг
	document.querySelectorAll('.delete-service-btn').forEach(btn => {
		btn.addEventListener('click', function() {
			const serviceId = this.getAttribute('data-service-id');
			if (!serviceId) return;
			
			if (!confirm('¿Está seguro de eliminar este servicio? Se eliminarán también todas sus imágenes.')) {
				return;
			}
			
			fetch(basePath + 'includes/admin_delete_product_js.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ product_id: serviceId })
			})
			.then(response => response.json())
			.then(data => {
				if (data.ok === 1) {
					// Удаляем элемент из DOM
					const serviceItem = this.closest('.service-item-admin');
					if (serviceItem) {
						serviceItem.remove();
					}
					// Перезагружаем данные пользователя для обновления формы
					const userId = currentSelectedUserId;
					if (userId) {
						loadUserFullData(userId);
					}
				} else {
					alert('Error al eliminar servicio: ' + (data.err || 'Error desconocido'));
				}
			})
			.catch(error => {
				console.error('Error deleting service:', error);
				alert('Error de conexión al eliminar servicio');
			});
		});
	});
}

user_list(0, '');
</script>

<script src="<?= $basePath ?>js/user_detail_form.js"></script>

