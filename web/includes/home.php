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
    // Очищаем сессию, если пользователь не найден в БД
    session_destroy();
    session_start();
    header('Location: ?page=login');
    exit();
}

$email = htmlspecialchars($userData['email'] ?? '');
$phone = htmlspecialchars($userData['phone'] ?? '');

// Приоритет: сначала companies, потом users (COALESCE)
$query = "SELECT COALESCE(c.name, u.company_name) as company_name,
                 COALESCE(c.tax_id, u.tax_id) as tax_id
          FROM users u
          LEFT JOIN companies c ON c.user_id = u.id
          WHERE u.id = ? LIMIT 1";
$stmt = mysqli_prepare($link, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        $companyName = htmlspecialchars($data['company_name'] ?? '');
        $taxId = $data['tax_id'] ?? '';
        $taxIdDigits = preg_replace('/\D/', '', (string) $taxId);
        // Для полей ввода используем только цифры без маски
        $taxId = htmlspecialchars($taxIdDigits);
    } else {
        $companyName = '';
        $taxId = '';
    }
    mysqli_stmt_close($stmt);
} else {
    $companyName = '';
    $taxId = '';
}

// Проверка статуса модерации и наличия данных компании
$hasCompanyData = false;
$moderationStatus = null;
$query = "SELECT moderation_status FROM companies WHERE user_id = ? LIMIT 1";
$stmt = mysqli_prepare($link, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && mysqli_num_rows($result) > 0) {
        $moderationData = mysqli_fetch_assoc($result);
        $hasCompanyData = true;
        $moderationStatus = $moderationData['moderation_status'] ?? 'pending';
    }
    mysqli_stmt_close($stmt);
}

// Загрузка товаров и услуг пользователя
require_once __DIR__ . '/FileManager.php';
require_once __DIR__ . '/storage/StorageFactory.php';

$products = [];
$productPhotos = [];

try {
    // Загружаем и продукты, и услуги
    $query = "SELECT id, is_main, type, name, description 
              FROM products 
              WHERE user_id = ? 
              ORDER BY is_main DESC, id ASC";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Определяем тип продукта/услуги
        $itemType = $row['type'] ?? null;
        // Если type пустой, NULL или не равен 'service', считаем продуктом
        if (empty($itemType) || $itemType === '' || $itemType !== 'service') {
            $itemType = 'product';
        }
        
        $products[] = [
            'id' => intval($row['id']),
            'is_main' => (bool)$row['is_main'],
            'type' => $itemType,
            'name' => htmlspecialchars($row['name'] ?? ''),
            'description' => htmlspecialchars($row['description'] ?? '')
        ];
    }
    mysqli_stmt_close($stmt);
    
    // Загрузка изображений товаров и услуг
    if (!empty($products)) {
        $productIds = array_column($products, 'id');
        $mainProductId = null;
        foreach ($products as $product) {
            if ($product['is_main']) {
                $mainProductId = $product['id'];
                break;
            }
        }
        
        // Загрузка изображений для продуктов и услуг
        if (count($productIds) > 0) {
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            // Загружаем и product_photo, и service_photo
            $query = "SELECT f.id, f.product_id, f.file_path, f.file_type, f.storage_type, p.is_main, p.type, p.id as product_id_from_table
                      FROM files f
                      LEFT JOIN products p ON f.product_id = p.id AND p.user_id = ?
                      WHERE f.user_id = ? AND (f.file_type = 'product_photo' OR f.file_type = 'service_photo')
                      AND f.is_temporary = 0 
                      AND (f.product_id IN ($placeholders) OR (f.product_id IS NULL OR f.product_id = 0))
                      ORDER BY p.is_main DESC, f.product_id, f.created_at";
            $stmt = mysqli_prepare($link, $query);
            $types = 'ii' . str_repeat('i', count($productIds));
            $params = array_merge([$userId, $userId], $productIds);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $pid = null;
                $isMain = $row['is_main'] ?? false;
                $productIdFromTable = $row['product_id_from_table'];
                $fileType = $row['file_type'] ?? '';
                $productType = $row['type'] ?? 'product';
                
                // Определяем правильный тип файла на основе типа продукта/услуги
                // Если это услуга, ищем service_photo, если продукт - product_photo
                if ($productType === 'service' && $fileType !== 'service_photo') {
                    continue; // Пропускаем, если это не service_photo для услуги
                }
                if ($productType === 'product' && $fileType !== 'product_photo') {
                    continue; // Пропускаем, если это не product_photo для продукта
                }
                
                // Если product_id NULL или 0, и это основной товар/услуга
                if (($row['product_id'] === null || intval($row['product_id']) == 0) && $isMain && $mainProductId) {
                    $pid = $mainProductId;
                } else if ($row['product_id'] !== null && intval($row['product_id']) > 0) {
                    $pid = intval($row['product_id']);
                }
                
                if ($pid !== null && !isset($productPhotos[$pid])) {
                    try {
                        $storageType = $row['storage_type'] ?? 'local';
                        if (empty($storageType)) {
                            $storageType = 'local';
                        }
                        if ($storageType === 'local') {
                            $productPhotos[$pid] = get_serve_file_url($row['id']);
                        } else {
                            $storage = StorageFactory::createByType($storageType);
                            $productPhotos[$pid] = $storage->getUrl($row['file_path']);
                        }
                    } catch (Exception $e) {
                        $productPhotos[$pid] = null;
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
} catch (Exception $e) {
    error_log("Error loading products and services in home.php: " . $e->getMessage());
}

$totalProducts = count($products);
$visibleProducts = min(4, $totalProducts);
?>
<div class="home-container">
  <!-- Header -->
  <header class="home-header">
    <div class="home-header-wrapper">
      <div class="home-logo">
        <img src="img/logo.svg" alt="Santiago del Estero" class="home-logo-image">
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
        <?php if (!empty($companyName)): ?>
          <div class="home-profile-company-name" style="text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px; color: #333; padding: 10px; background: #f5f5f5; border-radius: 4px;">
            <?= $companyName ?>
          </div>
        <?php endif; ?>
        <div class="home-avatar-upload">
          <div class="home-avatar-placeholder" id="home-avatar-placeholder" style="cursor: pointer;">
            <img id="home-avatar-image" src="" alt="Logo" style="display: none; width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
            <span data-i18n-html="home_avatar_text" class="home-avatar-text" id="home-avatar-text">Agregar<br>logotipo</span>
            <div class="home-avatar-camera">
              <img src="img/icons/edit_icon.png" alt="Edit">
            </div>
          </div>
          <input type="file" id="home-logo-input" accept="image/jpeg,image/png,image/jpg" style="display: none;">
          <button type="button" id="home-logo-delete" class="home-logo-delete" style="display: none;" data-i18n="home_delete_logo">Eliminar logotipo</button>
        </div>
        
        <div class="home-form-fields">
          <div class="home-form-field">
            <label data-i18n="home_form_company" class="home-form-label">Nombre de la empresa:</label>
            <input type="text" class="home-form-input" id="profile-company" value="<?= $companyName ?>">
          </div>
          
          <div class="home-form-field">
            <label data-i18n="home_form_tax_id" class="home-form-label">CUIL/CUIT:</label>
            <input
              type="text"
              class="home-form-input"
              id="profile-tax-id"
              name="tax_id"
              value="<?= $taxId ?>"
              placeholder=""
              inputmode="numeric"
              maxlength="11"
            >
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
          <button data-i18n="btn_save_profile" class="btn btn-save-profile" id="btnSaveProfile">Guardar cambios</button>
          <button data-i18n="btn_logout" class="btn btn-logout">Cerrar sesión</button>
          <span data-i18n="logout_confirm" style="display: none;">¿Está seguro de que desea cerrar sesión?</span>
        </div>
        <div id="home-profile-message" style="margin-top: 15px; display: none; padding: 10px; border-radius: 4px; text-align: center;"></div>
      </div>
      
      <div class="home-profile-action">
        <button data-i18n="btn_edit_form" onclick="location.href='?page=regfull';" class="btn btn-edit-form">Editar formulario: agregar nuevos productos y servicios</button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="home-content">
      <?php if (isset($_GET['pdf_blocked']) && $_GET['pdf_blocked'] === 'moderation'): ?>
      <div class="home-pdf-blocked-notice" role="alert" style="margin-bottom: 20px; padding: 16px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; color: #856404;">
        <p data-i18n="home_pdf_blocked_moderation" style="margin: 0; font-size: 16px;">No se puede descargar el PDF. Sus datos están en moderación. Los PDF estarán disponibles cuando el administrador apruebe su empresa.</p>
      </div>
      <?php endif; ?>
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
        
        <?php if (!$hasCompanyData): ?>
          <!-- Состояние 1: Пользователь не заполнял форму -->
          <div style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 18px; color: #666; margin-bottom: 30px;">
              <p data-i18n="home_no_products_message">Aún no has agregado productos. ¡Comienza agregando tu primer producto!</p>
            </div>
            <button onclick="location.href='?page=regfull'" class="btn btn-show-more" style="cursor: pointer;">
              <span data-i18n="home_add_products_button">Agregar Productos</span>
            </button>
          </div>
        <?php elseif ($moderationStatus === 'pending'): ?>
          <!-- Состояние 2: Данные на модерации -->
          <div style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 18px; color: #666; margin-bottom: 30px;">
              <p data-i18n="home_moderation_message">Sus datos están en moderación. Por favor, espere la confirmación del administrador.</p>
            </div>
          </div>
        <?php elseif (empty($products)): ?>
          <!-- Состояние 3: Данные подтверждены, но товаров нет -->
          <div style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 18px; color: #666; margin-bottom: 30px;">
              <p data-i18n="home_no_products_message">Aún no has agregado productos. ¡Comienza agregando tu primer producto!</p>
            </div>
            <button onclick="location.href='?page=regfull'" class="btn btn-show-more" style="cursor: pointer;">
              <span data-i18n="home_add_products_button">Agregar Productos</span>
            </button>
          </div>
        <?php else: ?>
          <div class="home-products-grid">
            <?php foreach ($products as $index => $product): 
              $isVisible = $index < 4;
              $productImage = isset($productPhotos[$product['id']]) ? $productPhotos[$product['id']] : null;
              $imageSrc = $productImage ? $productImage : 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect width="200" height="200" fill="#f0f0f0"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" font-family="Arial" font-size="14" fill="#999">No img</text></svg>');
              $imageAlt = htmlspecialchars($product['name']);
              $productName = htmlspecialchars($product['name']);
            ?>
            <div class="home-product-card <?php echo $isVisible ? 'home-product-visible' : 'home-product-hidden'; ?>">
              <div class="home-product-badge <?php echo ($product['type'] === 'service') ? 'home-service-badge' : 'home-product-badge-type'; ?>">
                <?php echo ($product['type'] === 'service') ? 'Servicio' : 'Producto'; ?>
              </div>
              <div class="home-product-image">
                <img src="<?php echo $imageSrc; ?>" alt="<?php echo $imageAlt; ?>">
              </div>
              <div class="home-product-info">
                <div class="home-product-name"><?php echo $productName; ?></div>
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

      <!-- Recomendaciones de mercados con IA -->
      <section class="home-section home-gemini-section">
        <div class="home-section-header">
          <h2 data-i18n="home_gemini_section_title" class="home-section-title">Recomendaciones de mercados con IA</h2>
        </div>
        <?php if ($hasCompanyData && !empty($products)): ?>
        <p class="home-gemini-intro" data-i18n="home_gemini_intro">Obtenga sugerencias sobre países y estrategias para exportar o vender sus productos y servicios.</p>
        <div class="home-gemini-actions">
          <button type="button" class="btn btn-generate-ai" id="btnGeminiMarkets" data-i18n="home_gemini_btn">Obtener recomendaciones con IA</button>
        </div>
        <div id="home-gemini-loading" class="home-gemini-loading" style="display: none;" data-i18n="home_gemini_loading">Cargando...</div>
        <div id="home-gemini-error" class="home-gemini-error" style="display: none;"></div>
        <div id="home-gemini-result" class="home-gemini-result" style="display: none;"></div>
        <?php else: ?>
        <div class="home-gemini-placeholder" style="text-align: center; padding: 40px 20px; font-size: 18px; color: #666; background: #f9f9f9; border-radius: 8px;">
          <p data-i18n="home_gemini_need_data" style="margin-bottom: 20px;">Agregue empresa y productos para obtener recomendaciones de mercados.</p>
          <a href="index.php?page=regfull" class="btn btn-show-more" style="display: inline-block; text-decoration: none;" data-i18n="home_add_products_button">Agregar Productos</a>
        </div>
        <?php endif; ?>
      </section>

      <!-- Presentations Section: siempre visible; si no aprobado, se muestra mensaje en lugar de la grilla -->
      <section class="home-section">
        <div class="home-section-header">
          <h2 data-i18n="home_presentations_title" class="home-section-title">Presentaciones generadas de productos y servicios <span class="home-section-count">3/3</span></h2>
        </div>

        <?php if ($moderationStatus === 'approved'): ?>
        <div class="home-presentations-grid">
          <div class="home-presentation-card">
            <div class="home-presentation-image">
              <img src="img/ClasicoCuadrado.png" alt="Clásico">
            </div>
            <div class="home-presentation-content">
              <a href="index.php?page=clasico_company_es" class="btn btn-download-pdf js-pdf-link" target="_blank" rel="noopener" data-pdf-url-es="index.php?page=clasico_company_es" data-pdf-url-en="index.php?page=clasico_company_en" data-i18n="btn_download_pdf">Descargar PDF</a>
            </div>
          </div>
          
          <div class="home-presentation-card">
            <div class="home-presentation-image">
              <img src="img/CorporativoCuadrado.png" alt="Corporativo">
            </div>
            <div class="home-presentation-content">
              <a href="index.php?page=corporativo_company_es" class="btn btn-download-pdf js-pdf-link" target="_blank" rel="noopener" data-pdf-url-es="index.php?page=corporativo_company_es" data-pdf-url-en="index.php?page=corporativo_company_en" data-i18n="btn_download_pdf">Descargar PDF</a>
            </div>
          </div>
          
          <div class="home-presentation-card">
            <div class="home-presentation-image">
              <img src="img/ModernoCuadrado.png" alt="Moderno">
            </div>
            <div class="home-presentation-content">
              <a href="index.php?page=moderno_company_es" class="btn btn-download-pdf js-pdf-link" target="_blank" rel="noopener" data-pdf-url-es="index.php?page=moderno_company_es" data-pdf-url-en="index.php?page=moderno_company_en" data-i18n="btn_download_pdf">Descargar PDF</a>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="home-presentations-placeholder" style="text-align: center; padding: 40px 20px; font-size: 18px; color: #666; background: #f9f9f9; border-radius: 8px;">
          <?php if (!$hasCompanyData): ?>
          <p data-i18n="home_no_products_message" style="margin-bottom: 20px;">Aún no has agregado productos. ¡Comienza agregando tu primer producto!</p>
          <a href="index.php?page=regfull" class="btn btn-show-more" style="display: inline-block; text-decoration: none;" data-i18n="home_add_products_button">Agregar Productos</a>
          <?php else: ?>
          <p data-i18n="home_moderation_message" style="margin: 0;">Sus datos están en moderación. Por favor, espere la confirmación del administrador.</p>
          <?php endif; ?>
        </div>
        <?php endif; ?>
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
  
  // Поле CUIT в профиле: только цифры, без формата с guiones
  const profileTaxIdInput = document.getElementById('profile-tax-id');
  if (profileTaxIdInput) {
    profileTaxIdInput.addEventListener('input', function() {
      this.value = (this.value || '').replace(/\D/g, '').slice(0, 11);
    });
  }

  // Save profile button handler
  const saveProfileBtn = document.getElementById('btnSaveProfile');
  const profileMessage = document.getElementById('home-profile-message');
  
  if (saveProfileBtn) {
    saveProfileBtn.addEventListener('click', async function() {
      const companyName = document.getElementById('profile-company').value.trim();
      const taxIdRaw = document.getElementById('profile-tax-id').value.trim();
      const taxIdDigits = (taxIdRaw || '').replace(/\D/g, '');
      const email = document.getElementById('profile-email').value.trim();
      const phone = document.getElementById('profile-phone').value.trim();
      const password = document.getElementById('profile-password').value.trim();
      
      if (taxIdDigits.length !== 11 || !/^\d{11}$/.test(taxIdDigits)) {
        showProfileMessage('CUIT / Identificación Fiscal debe tener exactamente 11 dígitos', 'error');
        if (profileTaxIdInput) profileTaxIdInput.focus();
        return;
      }
      
      if (!email) {
        showProfileMessage('El correo electrónico es obligatorio', 'error');
        return;
      }
      
      if (!phone) {
        showProfileMessage('El número de teléfono es obligatorio', 'error');
        return;
      }
      
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showProfileMessage('El formato del correo electrónico no es válido', 'error');
        return;
      }
      
      const saveBtnInitialText = saveProfileBtn.getAttribute('data-i18n') ? (saveProfileBtn.textContent || 'Guardar cambios') : 'Guardar cambios';
      saveProfileBtn.disabled = true;
      saveProfileBtn.textContent = 'Guardando...';
      
      try {
        const data = {
          company_name: companyName,
          tax_id: taxIdDigits,
          email: email,
          phone: phone
        };
        
        if (password) {
          data.password = password;
        }
        
        const response = await fetch('includes/home_update_profile_js.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(data)
        });
        
        const text = await response.text();
        let result;
        try {
          result = JSON.parse(text);
        } catch (parseErr) {
          console.error('Profile update: invalid JSON', text);
          showProfileMessage('Error del servidor. Respuesta no válida.', 'error');
          saveProfileBtn.disabled = false;
          saveProfileBtn.textContent = saveBtnInitialText;
          return;
        }
        
        if (result.ok === 1) {
          showProfileMessage(result.res || 'Perfil actualizado correctamente', 'success');
          if (password) {
            document.getElementById('profile-password').value = '';
          }
          setTimeout(() => {
            window.location.reload();
          }, 1500);
        } else {
          showProfileMessage(result.err || 'Error al actualizar el perfil', 'error');
          saveProfileBtn.disabled = false;
          saveProfileBtn.textContent = saveBtnInitialText;
        }
      } catch (error) {
        console.error('Profile update error:', error);
        showProfileMessage('Error de conexión. Intente de nuevo.', 'error');
        saveProfileBtn.disabled = false;
        saveProfileBtn.textContent = saveBtnInitialText;
      }
    });
  }
  
  function showProfileMessage(message, type) {
    if (!profileMessage) return;
    
    profileMessage.textContent = message;
    profileMessage.style.display = 'block';
    profileMessage.className = type === 'success' ? 'success' : 'error';
    profileMessage.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
    profileMessage.style.color = type === 'success' ? '#155724' : '#721c24';
    profileMessage.style.border = `1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'}`;
    
    if (type === 'success') {
      setTimeout(() => {
        profileMessage.style.display = 'none';
      }, 3000);
    }
  }
  
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
          const logoDeleteBtn = document.getElementById('home-logo-delete');
          if (logoDeleteBtn) logoDeleteBtn.style.display = 'block';
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
        const logoDeleteBtn = document.getElementById('home-logo-delete');
        if (logoDeleteBtn) logoDeleteBtn.style.display = 'block';
      }
    })
    .catch(error => {
      console.log('ℹ️ No hay logo guardado o error al cargar:', error);
    });

  // Eliminar logotipo
  const logoDeleteBtn = document.getElementById('home-logo-delete');
  if (logoDeleteBtn) {
    logoDeleteBtn.addEventListener('click', async function(e) {
      e.preventDefault();
      e.stopPropagation();
      const msg = document.documentElement.lang === 'es' || !document.documentElement.lang
        ? '¿Eliminar el logotipo?'
        : 'Delete the logo?';
      if (!confirm(msg)) return;
      try {
        const response = await fetch('includes/home_delete_logo_js.php', { method: 'POST' });
        const result = await response.json();
        if (result.ok === 1) {
          avatarImage.src = '';
          avatarImage.style.display = 'none';
          avatarText.style.display = 'block';
          logoDeleteBtn.style.display = 'none';
          if (logoInput) logoInput.value = '';
        } else {
          alert(result.err || 'Error al eliminar el logotipo');
        }
      } catch (err) {
        console.error('Error deleting logo:', err);
        alert('Error al eliminar el logotipo. Por favor, intente de nuevo.');
      }
    });
  }

  // Recomendaciones de mercados con IA
  const btnGemini = document.getElementById('btnGeminiMarkets');
  const geminiLoading = document.getElementById('home-gemini-loading');
  const geminiError = document.getElementById('home-gemini-error');
  const geminiResult = document.getElementById('home-gemini-result');
  if (btnGemini) {
    btnGemini.addEventListener('click', async function() {
      geminiLoading.style.display = 'block';
      geminiError.style.display = 'none';
      geminiError.textContent = '';
      geminiResult.style.display = 'none';
      geminiResult.textContent = '';
      btnGemini.disabled = true;
      try {
        const res = await fetch('includes/gemini_markets_js.php', { method: 'POST' });
        const data = await res.json();
        geminiLoading.style.display = 'none';
        btnGemini.disabled = false;
        if (data.ok && data.text) {
          geminiResult.style.display = 'block';
          geminiResult.textContent = data.text;
          geminiResult.style.whiteSpace = 'pre-wrap';
        } else {
          geminiError.style.display = 'block';
          geminiError.textContent = data.error || 'Error al obtener recomendaciones.';
        }
      } catch (err) {
        geminiLoading.style.display = 'none';
        btnGemini.disabled = false;
        geminiError.style.display = 'block';
        geminiError.textContent = 'Error de conexión. Intente de nuevo.';
      }
    });
  }
});
</script>
<script src="js/i18n.js?v=<?= asset_version('js/i18n.js') ?>"></script>
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