<?
$userData = null;
if (isset($_SESSION['uid'])) {
    $userId = intval($_SESSION['uid']);
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && mysqli_num_rows($result) > 0) {
        $userData = mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
}
if (!$userData) {
    header('Location: ?page=login');
    exit();
}

$lastname = htmlspecialchars($userData['last_name'] ?? '');
$firstname = htmlspecialchars($userData['first_name'] ?? '');
$email = htmlspecialchars($userData['email'] ?? '');
$phone = htmlspecialchars($userData['phone'] ?? '');
$companyName = isset($userData['company_name']) ? htmlspecialchars($userData['company_name']) : '';

// Загрузка товаров пользователя
require_once __DIR__ . '/FileManager.php';
require_once __DIR__ . '/storage/StorageFactory.php';

$products = [];
$productPhotos = [];

try {
    $query = "SELECT id, is_main, name, tariff_code, description 
              FROM products 
              WHERE user_id = ? 
              ORDER BY is_main DESC, id ASC";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = [
            'id' => intval($row['id']),
            'is_main' => (bool)$row['is_main'],
            'name' => htmlspecialchars($row['name'] ?? ''),
            'tariff_code' => htmlspecialchars($row['tariff_code'] ?? ''),
            'description' => htmlspecialchars($row['description'] ?? '')
        ];
    }
    mysqli_stmt_close($stmt);
    
    // Загрузка изображений товаров
    if (!empty($products)) {
        $productIds = array_column($products, 'id');
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $query = "SELECT f.id, f.product_id, f.file_path, f.storage_type, p.is_main
                  FROM files f
                  LEFT JOIN products p ON f.product_id = p.id
                  WHERE f.user_id = ? AND f.file_type = 'product_photo' 
                  AND f.is_temporary = 0 AND f.product_id IN ($placeholders)
                  ORDER BY p.is_main DESC, f.product_id, f.created_at";
        $stmt = mysqli_prepare($link, $query);
        $types = 'i' . str_repeat('i', count($productIds));
        $params = array_merge([$userId], $productIds);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $pid = intval($row['product_id']);
            if (!isset($productPhotos[$pid])) {
                try {
                    $storageType = $row['storage_type'] ?? 'local';
                    if (empty($storageType)) {
                        $storageType = 'local';
                    }
                    $storage = StorageFactory::createByType($storageType);
                    $productPhotos[$pid] = $storage->getUrl($row['file_path']);
                } catch (Exception $e) {
                    $productPhotos[$pid] = null;
                }
            }
        }
        mysqli_stmt_close($stmt);
    }
} catch (Exception $e) {
    error_log("Error loading products in home.php: " . $e->getMessage());
}

$totalProducts = count($products);
$visibleProducts = min(4, $totalProducts);
?>
<div class="home-container">
  <!-- Header -->
  <header class="home-header">
    <div class="home-header-wrapper">
      <div class="home-logo">
        <img src="img/logo.png" alt="Santiago del Estero" class="home-logo-image">
      </div>
      <div class="home-header-actions">
        <button data-i18n="btn_export_tariffs" class="btn btn-export-tariffs">Ver aranceles de exportación</button>
        <div class="home-header-icons">
          <div class="home-lang" onclick="toggleHomeLangMenu()">
            <img src="img/icons/lang.png" alt="Language">
            <span id="home-current-lang">Es</span>
            <ul id="home_lang_menu" class="home_lang_menu hidden">
              <li onclick="setLang('home', 'es')">Español</li>
              <li onclick="setLang('home', 'en')">English</li>
              <!-- <li onclick="setLang('home', 'ru')">Русский</li> -->
            </ul>
          </div>
          <div onclick="location.href='?page=regfull';" class="home-icon-btn home-notification-btn">
            <img src="img/icons/massage_icon.png" alt="Notifications" class="home-icon-image">
          </div>
          <div class="home-icon-btn home-profile-btn">
            <img src="img/icons/profile_icon.png" alt="Profile" class="home-icon-image">
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="home-main">
    <!-- Left Sidebar - Profile Form -->
    <aside class="home-sidebar">
      <div class="home-profile-form">
        <div class="home-avatar-upload">
          <div class="home-avatar-placeholder" id="home-avatar-placeholder" style="cursor: pointer;">
            <img id="home-avatar-image" src="" alt="Logo" style="display: none; width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
            <span data-i18n-html="home_avatar_text" class="home-avatar-text" id="home-avatar-text">Agregar<br>logotipo</span>
            <div class="home-avatar-camera">
              <img src="img/icons/edit_icon.png" alt="Edit">
            </div>
          </div>
          <input type="file" id="home-logo-input" accept="image/jpeg,image/png,image/jpg" style="display: none;">
        </div>
        
        <div class="home-form-fields">
          <div class="home-form-field">
            <label data-i18n="home_form_lastname" class="home-form-label">Apellido:</label>
            <input type="text" class="home-form-input" id="profile-lastname" value="<?= $lastname ?>">
          </div>
          
          <div class="home-form-field">
            <label data-i18n="home_form_firstname" class="home-form-label">Nombre:</label>
            <input type="text" class="home-form-input" id="profile-firstname" value="<?= $firstname ?>">
          </div>
          
          <div class="home-form-field">
            <label data-i18n="home_form_company" class="home-form-label">Nombre de la empresa:</label>
            <input type="text" class="home-form-input" id="profile-company" value="<?= $companyName ?>">
          </div>
          
          <div class="home-form-field">
            <label data-i18n="home_form_email" class="home-form-label">Correo electrónico:</label>
            <input type="email" class="home-form-input" id="profile-email" value="<?= $email ?>">
          </div>
          
          <div class="home-form-field">
            <label data-i18n="home_form_phone" class="home-form-label">Número de WhatsApp:</label>
            <input type="tel" class="home-form-input" id="profile-phone" value="<?= $phone ?>">
          </div>
          
          <div class="home-form-field">
            <label data-i18n="home_form_password" class="home-form-label">Contraseña:</label>
            <div class="home-form-password">
              <input type="password" class="home-form-input" id="profile-password" data-i18n-placeholder="home_form_password_placeholder">
              <button data-i18n="btn_change_password" class="home-form-change-btn">Cambiar</button>
            </div>
          </div>
        </div>
        
        <div class="home-profile-buttons">
          <button data-i18n="btn_save_profile" class="btn btn-save-profile">Guardar cambios</button>
          <button data-i18n="btn_logout" class="btn btn-logout">Cerrar sesión</button>
          <span data-i18n="logout_confirm" style="display: none;">¿Está seguro de que desea cerrar sesión?</span>
        </div>
      </div>
      
      <div class="home-profile-action">
        <button data-i18n="btn_edit_form" onclick="location.href='?page=regfull';" class="btn btn-edit-form">Editar formulario: agregar nuevos productos y servicios</button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="home-content">
      <!-- Products Section -->
      <section class="home-section home-products-section">
        <div class="home-section-header">
          <h2 class="home-section-title"><span data-i18n="home_section_title">Información sobre Productos y Servicios</span> <span class="home-section-count" data-total="<?php echo $totalProducts; ?>" data-visible="<?php echo $visibleProducts; ?>"><?php echo $visibleProducts; ?>/<?php echo $totalProducts; ?></span></h2>
          <div class="home-search-box">
            <input type="search" class="home-search-input" data-i18n-placeholder="home_search_placeholder">
            <svg class="home-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <path d="m21 21-4.35-4.35"></path>
            </svg>
          </div>
        </div>
        
        <?php if (empty($products)): ?>
          <div style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 18px; color: #666; margin-bottom: 30px;">
              <p data-i18n="home_no_products_message">Aún no has agregado productos. ¡Comienza agregando tu primer producto!</p>
            </div>
            <a href="?page=regfull" class="btn btn-show-more" style="text-decoration: none; display: inline-block;">
              <span data-i18n="home_add_products_button">Agregar Productos</span>
            </a>
          </div>
        <?php else: ?>
          <div class="home-products-grid">
            <?php foreach ($products as $index => $product): 
              $isVisible = $index < 4;
              $productImage = isset($productPhotos[$product['id']]) ? $productPhotos[$product['id']] : null;
              $imageSrc = $productImage ? $productImage : 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect width="200" height="200" fill="#f0f0f0"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" font-family="Arial" font-size="14" fill="#999">No img</text></svg>');
              $imageAlt = htmlspecialchars($product['name']);
              $productName = htmlspecialchars($product['name']);
              $tariffCode = htmlspecialchars($product['tariff_code'] ?: '0000.00.00');
            ?>
            <div class="home-product-card <?php echo $isVisible ? 'home-product-visible' : 'home-product-hidden'; ?>">
              <div class="home-product-image">
                <img src="<?php echo $imageSrc; ?>" alt="<?php echo $imageAlt; ?>">
              </div>
              <div class="home-product-info">
                <div class="home-product-name"><?php echo $productName; ?></div>
                <div class="home-product-code"><?php echo $tariffCode; ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          
          <?php if ($totalProducts > 4): ?>
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
              <button data-i18n="btn_show_more" class="btn btn-show-more" id="showMoreProducts">Mostrar más</button>
              <button data-i18n="btn_hide" class="btn btn-show-less" id="showLessProducts" style="display: none;">Ocultar</button>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </section>

      <!-- Presentations Section -->
      <section class="home-section">
        <div class="home-section-header">
          <h2 data-i18n="home_presentations_title" class="home-section-title">Presentaciones generadas de productos y servicios <span class="home-section-count">4/4</span></h2>
          <div class="home-search-box">
            <input type="search" class="home-search-input" data-i18n-placeholder="home_search_placeholder_presentations">
            <svg class="home-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <path d="m21 21-4.35-4.35"></path>
            </svg>
          </div>
        </div>
        
        <div class="home-presentations-grid">
          <div class="home-presentation-card">
            <div class="home-presentation-image">
              <img src="img/productos/foto1.jpg" alt="Queso de cabra madurado">
              <div class="home-presentation-icon">
                <img src="img/icons/ai_icon.png" alt="AI">
              </div>
            </div>
            <div class="home-presentation-content">
              <div data-i18n="product_goat_cheese" class="home-presentation-name">Queso de cabra madurado</div>
              <button data-i18n="btn_download_pdf" class="btn btn-download-pdf">Descargar PDF</button>
            </div>
          </div>
          
          <div class="home-presentation-card">
            <div class="home-presentation-image">
              <img src="img/productos/foto2.jpg" alt="Miel natural">
              <div class="home-presentation-icon">
                <img src="img/icons/ai_icon.png" alt="AI">
              </div>
            </div>
            <div class="home-presentation-content">
              <div data-i18n="product_natural_honey" class="home-presentation-name">Miel natural</div>
              <button data-i18n="btn_download_pdf" class="btn btn-download-pdf">Descargar PDF</button>
            </div>
          </div>
          
          <div class="home-presentation-card">
            <div class="home-presentation-image">
              <img src="img/productos/foto5.jpg" alt="Mermelada de durazno natural">
              <div class="home-presentation-icon">
                <img src="img/icons/ai_icon.png" alt="AI">
              </div>
            </div>
            <div class="home-presentation-content">
              <div data-i18n="product_peach_jam" class="home-presentation-name">Mermelada de durazno natural</div>
              <button data-i18n="btn_download_pdf" class="btn btn-download-pdf">Descargar PDF</button>
            </div>
          </div>
          
          <div class="home-presentation-card">
            <div class="home-presentation-image">
              <img src="img/productos/foto6.jpg" alt="Dulce de leche artesanal">
              <div class="home-presentation-icon">
                <img src="img/icons/ai_icon.png" alt="AI">
              </div>
            </div>
            <div class="home-presentation-content">
              <div data-i18n="product_dulce_leche" class="home-presentation-name">Dulce de leche artesanal</div>
              <button data-i18n="btn_download_pdf" class="btn btn-download-pdf">Descargar PDF</button>
            </div>
          </div>
        </div>
        
        <div class="home-presentations-actions">
          <button data-i18n="btn_show_more" class="btn btn-show-more-outline">Mostrar más</button>
          <button data-i18n="btn_generate_ai" class="btn btn-generate-ai">Generar IA-presentación</button>
        </div>
      </section>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const productsGrid = document.querySelector('.home-products-grid');
  const countElement = document.querySelector('.home-products-section .home-section-count');
  
  if (!productsGrid || !countElement) return;
  
  const showMoreBtn = document.getElementById('showMoreProducts');
  const showLessBtn = document.getElementById('showLessProducts');
  
  if (!showMoreBtn || !showLessBtn) {
    return;
  }
  
  const totalProducts = parseInt(countElement.getAttribute('data-total')) || 25;
  let visibleCount = 0;
  
  // Функция для определения количества карточек в ряду
  function getProductsPerRow() {
    const width = window.innerWidth;
    
    // Определяем количество колонок на основе ширины экрана
    // Соответствует CSS медиа-запросам
    if (width >= 1200) {
      return 4; // 4 карточки на больших экранах (≥1200px)
    } else if (width >= 900) {
      return 3; // 3 карточки на средних экранах (900px-1199px)
    } else if (width >= 480) {
      return 2; // 2 карточки на маленьких экранах (480px-899px)
    } else {
      return 1; // 1 карточка на очень маленьких экранах (<480px)
    }
  }
  
  // Функция для обновления счетчика
  function updateCounter() {
    countElement.textContent = visibleCount + '/' + totalProducts;
    countElement.setAttribute('data-visible', visibleCount);
  }
  
  // Функция для скрытия всех карточек кроме первых N
  function hideExtraCards(maxVisible) {
    const allCards = productsGrid.querySelectorAll('.home-product-card');
    allCards.forEach((card, index) => {
      if (index < maxVisible) {
        card.classList.remove('home-product-hidden');
      } else {
        card.classList.add('home-product-hidden');
      }
    });
  }
  
  // Функция для обновления видимости кнопок
  function updateButtonsVisibility() {
    const productsPerRow = getProductsPerRow();
    
    // Кнопка "Свернуть" показывается только если открыто больше одного ряда
    if (visibleCount > productsPerRow) {
      showLessBtn.style.display = 'block';
    } else {
      showLessBtn.style.display = 'none';
    }
    
    // Кнопка "Показать больше" скрывается если все продукты показаны
    if (visibleCount >= totalProducts) {
      showMoreBtn.style.display = 'none';
    } else {
      showMoreBtn.style.display = 'block';
    }
  }
  
  // Функция для инициализации и обновления видимых карточек
  function updateVisibleCards() {
    const productsPerRow = getProductsPerRow();
    hideExtraCards(productsPerRow);
    visibleCount = productsPerRow;
    updateCounter();
    updateButtonsVisibility();
  }
  
  // Инициализация - ждем немного, чтобы сетка успела отрендериться
  setTimeout(function() {
    updateVisibleCards();
  }, 100);
  
  // Функция для показа следующего ряда продуктов
  function showNextRow() {
    const hiddenProducts = productsGrid.querySelectorAll('.home-product-hidden');
    
    if (hiddenProducts.length === 0) {
      showMoreBtn.style.display = 'none';
      return;
    }
    
    const productsPerRow = getProductsPerRow();
    const productsToShow = Math.min(productsPerRow, hiddenProducts.length);
    
    for (let i = 0; i < productsToShow; i++) {
      hiddenProducts[i].classList.remove('home-product-hidden');
    }
    
    visibleCount += productsToShow;
    updateCounter();
    updateButtonsVisibility();
  }
  
  // Функция для сворачивания продуктов до первого ряда
  function collapseToFirstRow() {
    const productsPerRow = getProductsPerRow();
    hideExtraCards(productsPerRow);
    visibleCount = productsPerRow;
    updateCounter();
    updateButtonsVisibility();
  }
  
  // Обработчики кликов на кнопки
  showMoreBtn.addEventListener('click', showNextRow);
  showLessBtn.addEventListener('click', collapseToFirstRow);
  
  // Обновление при изменении размера окна
  let resizeTimeout;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(function() {
      const productsPerRow = getProductsPerRow();
      const currentVisible = Array.from(productsGrid.querySelectorAll('.home-product-card:not(.home-product-hidden)')).length;
      
      // Если количество видимых карточек не соответствует текущему размеру ряда
      // Пересчитываем, чтобы показывать только полные ряды
      if (currentVisible > productsPerRow && currentVisible % productsPerRow !== 0) {
        // Округляем вниз до полного ряда
        visibleCount = Math.floor(currentVisible / productsPerRow) * productsPerRow;
        if (visibleCount < productsPerRow) visibleCount = productsPerRow;
        hideExtraCards(visibleCount);
        updateCounter();
      } else if (currentVisible < productsPerRow) {
        // Если видимых меньше, чем нужно для ряда, показываем первый ряд
        visibleCount = productsPerRow;
        hideExtraCards(visibleCount);
        updateCounter();
      }
      
      // Обновляем видимость кнопок
      updateButtonsVisibility();
    }, 250);
  });
});

// Установка изображения как background для карточек презентаций
document.addEventListener('DOMContentLoaded', function() {
  const presentationCards = document.querySelectorAll('.home-presentation-card');
  
  presentationCards.forEach(function(card) {
    const image = card.querySelector('.home-presentation-image img');
    
    if (image) {
      const imageSrc = image.getAttribute('src');
      if (imageSrc) {
        card.style.backgroundImage = 'url(' + imageSrc + ')';
      }
    }
  });
  
  // Logout button handler
  const logoutBtn = document.querySelector('.btn-logout');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', function() {
      const confirmEl = document.querySelector('[data-i18n="logout_confirm"]');
      const confirmText = confirmEl ? confirmEl.textContent : '¿Está seguro de que desea cerrar sesión?';
      if (confirm(confirmText)) {
        window.location.href = '?page=logout';
      }
    });
  }
  
  // Logo upload handler
  const avatarPlaceholder = document.getElementById('home-avatar-placeholder');
  const logoInput = document.getElementById('home-logo-input');
  const avatarImage = document.getElementById('home-avatar-image');
  const avatarText = document.getElementById('home-avatar-text');
  
  if (avatarPlaceholder && logoInput) {
    // Клик на аватар открывает диалог выбора файла
    avatarPlaceholder.addEventListener('click', function() {
      logoInput.click();
    });
    
    // Обработка выбора файла
    logoInput.addEventListener('change', async function(e) {
      const file = e.target.files[0];
      if (!file) return;
      
      // Проверяем тип файла
      if (!file.type.startsWith('image/')) {
        alert('Por favor, seleccione un archivo de imagen (JPG, PNG)');
        return;
      }
      
      // Проверяем размер (максимум 10MB до сжатия)
      const maxSize = 10 * 1024 * 1024;
      if (file.size > maxSize) {
        alert(`El archivo es demasiado grande (${(file.size / 1024 / 1024).toFixed(2)} MB). Máximo permitido: 10 MB`);
        return;
      }
      
      // Функция для сжатия изображения
      const compressImage = (file, maxWidth = 800, maxHeight = 800, quality = 0.85) => {
        return new Promise((resolve, reject) => {
          const reader = new FileReader();
          reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
              const canvas = document.createElement('canvas');
              let width = img.width;
              let height = img.height;
              
              // Вычисляем новые размеры с сохранением пропорций
              if (width > maxWidth || height > maxHeight) {
                const ratio = Math.min(maxWidth / width, maxHeight / height);
                width = width * ratio;
                height = height * ratio;
              }
              
              canvas.width = width;
              canvas.height = height;
              
              const ctx = canvas.getContext('2d');
              ctx.drawImage(img, 0, 0, width, height);
              
              // Конвертируем в Blob
              canvas.toBlob((blob) => {
                if (blob) {
                  const compressedFile = new File([blob], file.name, {
                    type: file.type,
                    lastModified: Date.now()
                  });
                  console.log(`📸 Logo comprimido: ${(file.size / 1024 / 1024).toFixed(2)} MB → ${(compressedFile.size / 1024 / 1024).toFixed(2)} MB`);
                  resolve(compressedFile);
                } else {
                  resolve(file);
                }
              }, file.type, quality);
            };
            img.onerror = () => reject(new Error('Error al cargar la imagen'));
            img.src = e.target.result;
          };
          reader.onerror = () => reject(new Error('Error al leer el archivo'));
          reader.readAsDataURL(file);
        });
      };
      
      try {
        // Показываем превью сразу
        const reader = new FileReader();
        reader.onload = (e) => {
          avatarImage.src = e.target.result;
          avatarImage.style.display = 'block';
          avatarText.style.display = 'none';
        };
        reader.readAsDataURL(file);
        
        // Сжимаем изображение
        const compressedFile = await compressImage(file);
        
        // Отправляем на сервер
        const formData = new FormData();
        formData.append('logo', compressedFile);
        
        console.log('📤 Enviando logo al servidor...');
        
        const response = await fetch('includes/home_upload_logo_js.php', {
          method: 'POST',
          body: formData
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('📥 Respuesta del servidor:', result);
        
        if (result.ok === 1) {
          // Обновляем изображение с URL с сервера (если есть)
          if (result.url) {
            avatarImage.src = result.url;
          }
          console.log('✅ Logo guardado correctamente');
        } else {
          throw new Error(result.err || 'Error al guardar el logo');
        }
      } catch (error) {
        console.error('❌ Error al subir el logo:', error);
        alert('Error al subir el logo. Por favor, intente de nuevo.');
        // Восстанавливаем состояние
        avatarImage.style.display = 'none';
        avatarText.style.display = 'block';
        logoInput.value = '';
      }
    });
  }
  
  // Загружаем существующий логотип при загрузке страницы
  fetch('includes/home_get_logo_js.php')
    .then(response => response.json())
    .then(data => {
      if (data.ok === 1 && data.url) {
        avatarImage.src = data.url;
        avatarImage.style.display = 'block';
        avatarText.style.display = 'none';
      }
    })
    .catch(error => {
      console.log('ℹ️ No hay logo guardado o error al cargar:', error);
    });
});
</script>
<script src="js/i18n.js?v=1.0.2"></script>
<script>
function toggleHomeLangMenu() {
  const menu = document.getElementById('home_lang_menu');
  menu.classList.toggle('hidden');
}
document.addEventListener('DOMContentLoaded', () => {
  initLang('home');
  // Обновляем ID для current-lang на главной странице
  const currentLangEl = document.getElementById('home-current-lang');
  if (currentLangEl) {
    const storedLang = localStorage.getItem('lang') || 'es';
    currentLangEl.textContent = storedLang.toUpperCase();
  }
});
// Переопределяем setLang для обновления home-current-lang
const originalSetLang = window.setLang;
if (originalSetLang) {
  window.setLang = async function(page, lang) {
    await originalSetLang(page, lang);
    const homeCurrentLang = document.getElementById('home-current-lang');
    if (homeCurrentLang) {
      homeCurrentLang.textContent = lang.toUpperCase();
    }
  };
}
document.addEventListener('click', function (e) {
  const langBox = document.querySelector('.home-lang');
  const menu = document.getElementById('home_lang_menu');
  if (!langBox.contains(e.target)) {
    menu.classList.add('hidden');
  }
});
</script>