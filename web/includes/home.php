<?
// Загружаем данные пользователя из базы данных
// session_start() и DBconnect() уже вызваны в index.php
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

// Если нет данных пользователя, редирект на логин
if (!$userData) {
    header('Location: ?page=login');
    exit();
}

$lastname = htmlspecialchars($userData['last_name'] ?? '');
$firstname = htmlspecialchars($userData['first_name'] ?? '');
$email = htmlspecialchars($userData['email'] ?? '');
$phone = htmlspecialchars($userData['phone'] ?? '');
// Проверяем наличие колонки company_name (может отсутствовать в старых версиях таблицы)
$companyName = isset($userData['company_name']) ? htmlspecialchars($userData['company_name']) : '';
?>
<div class="home-container">
  <!-- Header -->
  <header class="home-header">
    <div class="home-header-wrapper">
      <div class="home-logo">
        <img src="img/logo.png" alt="Santiago del Estero" class="home-logo-image">
      </div>
      <div class="home-nav-container">
        <nav class="home-nav">
          <a href="#" class="home-nav-link">Nosotros</a>
          <a href="#" class="home-nav-link">Oferta exportable</a>
          <a href="#" class="home-nav-link">Noticias</a>
          <a href="#" class="home-nav-link">Contactos</a>
          <a href="https://wa.me/" class="home-nav-whatsapp" target="_blank">
            <img src="img/icono_whatsapp.png" alt="WhatsApp" class="whatsapp-icon">
          </a>
        </nav>
      </div>
      <div class="home-header-actions">
        <button class="btn btn-export-tariffs">Ver aranceles de exportación</button>
        <div class="home-header-icons">
          <div class="home-icon-btn">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </div>
          <div class="home-icon-btn home-notification-btn">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <span class="notification-badge">3</span>
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
          <div class="home-avatar-placeholder">
            <span class="home-avatar-text">Agregar<br>logotipo</span>
            <div class="home-avatar-camera">
              <img src="img/icons/edit_icon.png" alt="Edit">
            </div>
          </div>
        </div>
        
        <div class="home-form-fields">
          <div class="home-form-field">
            <label class="home-form-label">Apellido:</label>
            <input type="text" class="home-form-input" id="profile-lastname" value="<?= $lastname ?>">
          </div>
          
          <div class="home-form-field">
            <label class="home-form-label">Nombre:</label>
            <input type="text" class="home-form-input" id="profile-firstname" value="<?= $firstname ?>">
          </div>
          
          <div class="home-form-field">
            <label class="home-form-label">Nombre de la empresa:</label>
            <input type="text" class="home-form-input" id="profile-company" value="<?= $companyName ?>">
          </div>
          
          <div class="home-form-field">
            <label class="home-form-label">Correo electrónico:</label>
            <input type="email" class="home-form-input" id="profile-email" value="<?= $email ?>">
          </div>
          
          <div class="home-form-field">
            <label class="home-form-label">Número de WhatsApp:</label>
            <input type="tel" class="home-form-input" id="profile-phone" value="<?= $phone ?>">
          </div>
          
          <div class="home-form-field">
            <label class="home-form-label">Contraseña:</label>
            <div class="home-form-password">
              <input type="password" class="home-form-input" id="profile-password" placeholder="Nueva contraseña">
              <button class="home-form-change-btn">Cambiar</button>
            </div>
          </div>
        </div>
        
        <div class="home-profile-buttons">
          <button class="btn btn-save-profile">Guardar cambios</button>
          <button class="btn btn-logout">Cerrar sesión</button>
        </div>
      </div>
      
      <div class="home-profile-action">
        <button class="btn btn-edit-form">Editar formulario: agregar nuevos productos y servicios</button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="home-content">
      <!-- Products Section -->
      <section class="home-section home-products-section">
        <div class="home-section-header">
          <h2 class="home-section-title">Información sobre Productos y Servicios <span class="home-section-count" data-total="25" data-visible="4">4/25</span></h2>
          <div class="home-search-box">
            <input type="search" class="home-search-input" placeholder="Buscar...">
            <svg class="home-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <path d="m21 21-4.35-4.35"></path>
            </svg>
          </div>
        </div>
        
        <div class="home-products-grid">
          <div class="home-product-card home-product-visible">
            <div class="home-product-image">
              <img src="img/productos/foto1.jpg" alt="Queso de cabra madurado">
            </div>
            <div class="home-product-info">
              <div class="home-product-name">Queso de cabra madurado</div>
              <div class="home-product-code">0406.90.20</div>
            </div>
          </div>
          
          <div class="home-product-card home-product-visible">
            <div class="home-product-image">
              <img src="img/productos/foto2.jpg" alt="Miel natural">
            </div>
            <div class="home-product-info">
              <div class="home-product-name">Miel natural</div>
              <div class="home-product-code">0409.00.00</div>
            </div>
          </div>
          
          <div class="home-product-card home-product-visible">
            <div class="home-product-image">
              <img src="img/productos/foto3.jpg" alt="Aceite de oliva">
            </div>
            <div class="home-product-info">
              <div class="home-product-name">Aceite de oliva</div>
              <div class="home-product-code">1509.10.00</div>
            </div>
          </div>
          
          <div class="home-product-card home-product-visible">
            <div class="home-product-image">
              <img src="img/productos/foto4.png" alt="Yerba mate">
            </div>
            <div class="home-product-info">
              <div class="home-product-name">Yerba mate</div>
              <div class="home-product-code">0903.00.10</div>
            </div>
          </div>
          
          <div class="home-product-card home-product-hidden">
            <div class="home-product-image">
              <img src="img/productos/foto5.jpg" alt="Mermelada de durazno natural">
            </div>
            <div class="home-product-info">
              <div class="home-product-name">Mermelada de durazno natural</div>
              <div class="home-product-code">2007.99.10</div>
            </div>
          </div>
          
          <div class="home-product-card home-product-hidden">
            <div class="home-product-image">
              <img src="img/productos/foto6.jpg" alt="Dulce de leche artesanal">
            </div>
            <div class="home-product-info">
              <div class="home-product-name">Dulce de leche artesanal</div>
              <div class="home-product-code">1901.90.90</div>
            </div>
          </div>
          
          <!-- Hidden products -->
          <?php
          $productImages = ['foto1.jpg', 'foto2.jpg', 'foto3.jpg', 'foto4.png', 'foto5.jpg', 'foto6.jpg'];
          for ($i = 7; $i <= 25; $i++) {
            $imageIndex = ($i - 7) % 6;
            $imagePath = 'img/productos/' . $productImages[$imageIndex];
          ?>
          <div class="home-product-card home-product-hidden">
            <div class="home-product-image">
              <img src="<?php echo $imagePath; ?>" alt="Producto <?php echo $i; ?>">
            </div>
            <div class="home-product-info">
              <div class="home-product-name">Producto <?php echo $i; ?></div>
              <div class="home-product-code">0000.00.00</div>
            </div>
          </div>
          <?php } ?>
        </div>
        
        <button class="btn btn-show-more" id="showMoreProducts">Mostrar más</button>
      </section>

      <!-- Presentations Section -->
      <section class="home-section">
        <div class="home-section-header">
          <h2 class="home-section-title">Presentaciones generadas de productos y servicios <span class="home-section-count">4/4</span></h2>
          <div class="home-search-box">
            <input type="search" class="home-search-input" placeholder="Buscar...">
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
              <div class="home-presentation-name">Queso de cabra madurado</div>
              <button class="btn btn-download-pdf">Descargar PDF</button>
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
              <div class="home-presentation-name">Miel natural</div>
              <button class="btn btn-download-pdf">Descargar PDF</button>
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
              <div class="home-presentation-name">Mermelada de durazno natural</div>
              <button class="btn btn-download-pdf">Descargar PDF</button>
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
              <div class="home-presentation-name">Dulce de leche artesanal</div>
              <button class="btn btn-download-pdf">Descargar PDF</button>
            </div>
          </div>
        </div>
        
        <div class="home-presentations-actions">
          <button class="btn btn-show-more-outline">Mostrar más</button>
          <button class="btn btn-generate-ai">Generar IA-presentación</button>
        </div>
      </section>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const showMoreBtn = document.getElementById('showMoreProducts');
  const productsGrid = document.querySelector('.home-products-grid');
  const countElement = document.querySelector('.home-products-section .home-section-count');
  
  if (!showMoreBtn || !productsGrid || !countElement) return;
  
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
  
  // Функция для инициализации и обновления видимых карточек
  function updateVisibleCards() {
    const productsPerRow = getProductsPerRow();
    hideExtraCards(productsPerRow);
    visibleCount = productsPerRow;
    updateCounter();
    
    // Скрываем кнопку, если все продукты показаны
    if (visibleCount >= totalProducts) {
      showMoreBtn.style.display = 'none';
    } else {
      showMoreBtn.style.display = 'block';
    }
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
    
    if (visibleCount >= totalProducts) {
      showMoreBtn.style.display = 'none';
    }
  }
  
  // Обработчик клика на кнопку
  showMoreBtn.addEventListener('click', showNextRow);
  
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
      
      // Обновляем видимость кнопки
      if (visibleCount >= totalProducts) {
        showMoreBtn.style.display = 'none';
      } else {
        showMoreBtn.style.display = 'block';
      }
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
      if (confirm('¿Está seguro de que desea cerrar sesión?')) {
        window.location.href = '?page=logout';
      }
    });
  }
});
</script>

