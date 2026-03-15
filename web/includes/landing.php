<?php
$__landing_config = file_exists(__DIR__ . '/config/config.php') ? (require __DIR__ . '/config/config.php') : [];
$__web_base = rtrim($__landing_config['web_base'] ?? '', '/');
$__scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$__host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$__base_url = $__scheme . '://' . $__host . ($__web_base ?: '');
$__pdf_es = $__base_url . '/index.php?page=download_oferta_pdf&lang=es';
$__pdf_en = $__base_url . '/index.php?page=download_oferta_pdf&lang=en';
$__pdf_oferta_urls = [
    'clasico'      => $__web_base . '/index.php?page=clasico_pdf_es',
    'clasico_es'   => $__web_base . '/index.php?page=clasico_pdf_es',
    'clasico_en'   => $__web_base . '/index.php?page=clasico_pdf_en',
    'corporativo'  => $__web_base . '/index.php?page=corporativo_pdf_es',
    'corporativo_es' => $__web_base . '/index.php?page=corporativo_pdf_es',
    'corporativo_en' => $__web_base . '/index.php?page=corporativo_pdf_en',
    'moderno'      => $__web_base . '/index.php?page=moderno_pdf_es',
    'moderno_es'   => $__web_base . '/index.php?page=moderno_pdf_es',
    'moderno_en'   => $__web_base . '/index.php?page=moderno_pdf_en',
    'pdf_es'       => $__pdf_es,
    'pdf_en'       => $__pdf_en,
];

// Товары/услуги только из компаний с пройденной модерацией (approved); без удалённых (deleted_at IS NULL); данные при каждой загрузке страницы
$__carousel_products = [];
global $link;
if ($link) {
    // Проверяем наличие колонки deleted_at (если миграция не применена — фильтр не используем)
    $hasDeletedAt = false;
    $checkCol = @mysqli_query($link, "SHOW COLUMNS FROM products LIKE 'deleted_at'");
    if ($checkCol && mysqli_fetch_assoc($checkCol)) {
        $hasDeletedAt = true;
    }
    $deletedCondition = $hasDeletedAt ? " AND (p.deleted_at IS NULL)" : "";
    // Сначала выбираем только одобренные компании, затем только товары этих компаний (жёсткая связь по id и user_id)
    $q = "SELECT p.id, p.name, p.description, p.type, p.company_id, c.name AS company_name
          FROM products p
          INNER JOIN (
            SELECT id, user_id, name FROM companies WHERE BINARY moderation_status = 'approved'
          ) c ON c.id = p.company_id AND c.user_id = p.user_id
          WHERE 1=1" . $deletedCondition . "
          ORDER BY COALESCE(p.updated_at, p.id) DESC
          LIMIT 60";
    $res = @mysqli_query($link, $q);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $desc = trim($row['description'] ?? '');
            $__carousel_products[] = [
                'id' => (int) $row['id'],
                'name' => htmlspecialchars($row['name'] ?? ''),
                'description' => htmlspecialchars($desc),
                'type' => ($row['type'] ?? 'product') === 'service' ? 'service' : 'product',
                'company_name' => htmlspecialchars($row['company_name'] ?? ''),
            ];
        }
    }
    // Изображения для товаров (только для уже отфильтрованного списка товаров одобренных компаний)
    $__carousel_photos = [];
    if (!empty($__carousel_products)) {
        $productIds = array_column($__carousel_products, 'id');
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $q = "SELECT f.id, f.product_id FROM files f
              INNER JOIN products p ON p.id = f.product_id
              INNER JOIN (SELECT id, user_id FROM companies WHERE BINARY moderation_status = 'approved') c ON c.id = p.company_id AND c.user_id = p.user_id
              WHERE f.product_id IN ($placeholders)
                AND f.file_type IN ('product_photo', 'product_photo_sec', 'service_photo')
                AND (f.is_temporary = 0 OR f.is_temporary IS NULL)" . $deletedCondition . "
              ORDER BY f.product_id, f.id ASC";
        $stmt = mysqli_prepare($link, $q);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, str_repeat('i', count($productIds)), ...$productIds);
            mysqli_stmt_execute($stmt);
            $r = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($r)) {
                $pid = (int) $row['product_id'];
                if (!isset($__carousel_photos[$pid])) {
                    $__carousel_photos[$pid] = get_serve_file_public_url($row['id']);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!-- HEADER -->
<div class="hero-section">
    <div class="hero-bg-video" aria-hidden="true">
        <video class="hero-bg-video-file" autoplay muted playsinline id="hero-bg-video">
            <source src="video/video11.mp4" type="video/mp4">
        </video>
    </div>
    <div class="hero-header">
        <div class="hero-header-container">
            <div class="hero-header-logos">
                <div class="logo logo-sde">
                    <img src="img/logo.svg" alt="Santiago del Estero" class="logo-image">
                </div>
                <div class="logo logo-cfi">
                    <img src="img/logo_cfi.svg" alt="CFI" class="logo-image">
                </div>
            </div>
            <div class="landing_header_lang" onclick="toggleLangMenu()">
                <img src="img/icons/lang.png" />
                <span id="current-lang">Es</span>
                <ul id="landing_header_lang_menu" class="landing_header_lang_menu hidden">
                    <li onclick="setLang('landing', 'es')">Español</li>
                    <li onclick="setLang('landing', 'en')">English</li>
                </ul>
            </div>
            <button type="button" class="hero-burger" aria-label="Menú" aria-expanded="false" id="hero-burger-btn">
                <span class="hero-burger-line"></span>
                <span class="hero-burger-line"></span>
                <span class="hero-burger-line"></span>
            </button>
            <div class="nav-container">
                <nav class="hero-nav">
                    <div class="oferta-dropdown" id="oferta-dropdown">
                        <button type="button" class="nav-link oferta-dropdown-trigger" data-i18n="nav_exportable" aria-expanded="false" aria-haspopup="true" id="oferta-dropdown-btn">Oferta exportable</button>
                        <ul class="oferta-dropdown-menu" id="oferta-dropdown-menu" role="menu" aria-label="Formatos de oferta exportable">
                            <li role="none">
                                <a role="menuitem" class="oferta-dropdown-item js-pdf-link" href="<?= htmlspecialchars($__pdf_es) ?>" target="_blank" rel="noopener" data-pdf-url-es="<?= htmlspecialchars($__pdf_es) ?>" data-pdf-url-en="<?= htmlspecialchars($__pdf_en) ?>"><span class="oferta-dropdown-name" data-i18n="pdf_name_clasico">Clásico</span><img src="img/icons/clasico_icon.png" alt="" class="oferta-dropdown-icon"></a>
                            </li>
                            <li role="none">
                                <a role="menuitem" class="oferta-dropdown-item js-pdf-link" href="<?= htmlspecialchars($__pdf_es) ?>" target="_blank" rel="noopener" data-pdf-url-es="<?= htmlspecialchars($__pdf_es) ?>" data-pdf-url-en="<?= htmlspecialchars($__pdf_en) ?>"><span class="oferta-dropdown-name" data-i18n="pdf_name_corporativo">Corporativo</span><img src="img/icons/corporativo_icon.png" alt="" class="oferta-dropdown-icon"></a>
                            </li>
                            <li role="none">
                                <a role="menuitem" class="oferta-dropdown-item js-pdf-link" href="<?= htmlspecialchars($__pdf_es) ?>" target="_blank" rel="noopener" data-pdf-url-es="<?= htmlspecialchars($__pdf_es) ?>" data-pdf-url-en="<?= htmlspecialchars($__pdf_en) ?>"><span class="oferta-dropdown-name" data-i18n="pdf_name_moderno">Moderno</span><img src="img/icons/moderno_icon.png" alt="" class="oferta-dropdown-icon"></a>
                            </li>
                        </ul>
                    </div>
                    <span class="hero-nav-sep" aria-hidden="true">|</span>
                    <a data-i18n="nav_search" onclick="location.href='?page=search';" class="nav-link">Buscar</a>
                    <span class="hero-nav-sep" aria-hidden="true">|</span>
                    <a data-i18n="nav_turismo" href="https://turismosantiago.gob.ar/" target="_blank" rel="noopener" class="nav-link">Turismo</a>
                    <span class="hero-nav-sep" aria-hidden="true">|</span>
                    <span class="hero-nav-contact-wrap">
                        <a data-i18n="nav_contact" href="#contactos" class="nav-link">Contactos</a>
                        <a href="https://wa.me/" class="nav-whatsapp" target="_blank" aria-label="WhatsApp">
                            <img src="img/icono_whatsapp.png" alt="" class="whatsapp-icon">
                        </a>
                    </span>
                </nav>
            </div>
            <div class="hero-buttons">
                <a data-i18n="btn_register" onclick="location.href='?page=regnew';" class="btn btn-register">Registrarse</a>
                <a data-i18n="btn_login" onclick="location.href='?page=login';" class="btn btn-login">Entrar</a>
            </div>
        </div>
    </div>
    <div class="hero-content">
        <h1 data-i18n-html="hero_title" class="hero-title">Conectamos el trabajo local con el<br>mercado global</h1>
    </div>
    <div class="hero-products" aria-hidden="true">
        <img src="img/landing/hero_products.png" alt="" class="hero-products-img" onerror="this.parentElement.style.display='none'">
    </div>
    <div class="hero-footer">
        <div class="hero-tagline">
            <span data-i18n="hero_tagline_normal" class="tagline-normal">CON </span>
            <span data-i18n="hero_tagline_bold" class="tagline-bold">SU GENTE</span>
            <span data-i18n="hero_tagline_normal_2" class="tagline-normal">, EL NORTE CAMINA</span><br>
            <span data-i18n="hero_tagline_normal_3" class="tagline-normal">HACIA UN </span>
            <span data-i18n="hero_tagline_bold_2" class="tagline-bold">FUTURO SOSTENIBLE.</span>
        </div>
    </div>
</div>
<!-- HEADER -->

<?php if (!empty($__carousel_products)): ?>
<!-- PRODUCTS CAROUSEL -->
<section class="products-carousel-section" id="productos">
    <div class="products-carousel-container">
        <div class="products-carousel-header">
            <h2 class="products-carousel-title" data-i18n="carousel_title">PRODUCTOS Y SERVICIOS</h2>
            <a onclick="location.href='?page=search';" class="products-carousel-buscar-btn" data-i18n="buscar">Buscar</a>
        </div>
        <div class="products-carousel-wrapper">
            <button type="button" class="products-carousel-btn products-carousel-btn-prev" aria-label="Anterior">&lsaquo;</button>
            <div class="products-carousel-track-container">
                <div class="products-carousel-track">
                    <?php for ($dup = 0; $dup < 2; $dup++): foreach ($__carousel_products as $p): 
                        $imgUrl = $__carousel_photos[$p['id']] ?? null;
                        $typeLabel = $p['type'] === 'service' ? 'Servicio' : 'Producto';
                    ?>
                    <div class="products-carousel-slide">
                        <div class="products-carousel-card">
                            <?php if ($imgUrl): ?>
                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= $p['name'] ?>" class="products-carousel-image" loading="lazy">
                            <?php else: ?>
                            <div class="products-carousel-image products-carousel-image-placeholder" aria-hidden="true"></div>
                            <?php endif; ?>
                            <?php if (!empty($p['name'])): ?>
                            <span class="products-carousel-type"><?= $p['name'] ?></span>
                            <?php endif; ?>
                            <?php if (!empty($p['company_name'])): ?>
                            <span class="products-carousel-company"><?= $p['company_name'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; endfor; ?>
                </div>
            </div>
            <button type="button" class="products-carousel-btn products-carousel-btn-next" aria-label="Siguiente">&rsaquo;</button>
        </div>
    </div>
</section>
<!-- PRODUCTS CAROUSEL -->
<?php endif; ?>

<!-- ESTADIO SECTION -->
<div class="estadio-section" id="turismo">
    <div class="estadio-container">
        <div class="estadio-text-column">
            <h2 class="estadio-title" data-i18n-html="estadio_title">ESTADIO UNICO<br>MADRE DE CIUDADES</h2>
            <p class="estadio-text" data-i18n="estadio_text_1">El Estadio Único Madre de Ciudades es un recinto deportivo ubicado en la ciudad de Santiago del Estero, Argentina. Se encuentra ubicado en la zona norte de la ciudad de Santiago del Estero y se conecta con la Terminal de Ómnibus y la Estación Ferroviaria La Banda mediante el Tren al Desarrollo. Además, cuenta con un Museo interactivo y un restaurante de primer nivel, con hall de acceso preferencial hacia el emblemático Puente Carretero y un amplio estacionamiento para más de 400 vehículos debajo de sus tribunas de cabecera.</p>
            <p class="estadio-text" data-i18n="estadio_text_2">Fue inaugurado el 4 de marzo de 2021, siendo sede del partido de la Supercopa Argentina 2019 entre Racing Club y River Plate, el cual terminó en victoria de este último por 5 a 0.</p>
        </div>
        <div class="estadio-image-column">
            <img src="img/landing/landing_estadio_1.png" 
                 alt="Estadio Único Madre de Ciudades" 
                 class="estadio-image" 
                 id="estadio-image"
                 onmouseover="this.src='img/landing/landing_estadio_2.png'"
                 onmouseout="this.src='img/landing/landing_estadio_1.png'">
        </div>
    </div>
</div>
<!-- ESTADIO SECTION -->

<!-- TERMAL SECTION -->
<div class="termal-section">
    <div class="termal-container">
        <div class="termal-image-column">
            <img src="img/landing/landing_termal_1.png" 
                 alt="Centro Termal Spa" 
                 class="termal-image" 
                 id="termal-image"
                 onmouseover="this.src='img/landing/landing_termal_2.png'"
                 onmouseout="this.src='img/landing/landing_termal_1.png'">
        </div>
        <div class="termal-text-column">
            <h2 class="termal-title" data-i18n-html="termal_title">CENTRO TERMAL</h2>
            <p class="termal-text" data-i18n="termal_text_main">El Centro Termal-Spa de Termas de Río Hondo es un importante complejo recreativo público ubicado en el Parque Güemes, que ofrece aguas termales curativas (30°C a 85°C), relajación, balneoterapia y tratamientos para afecciones de la piel y del sistema musculoesquelético. También incluye servicios integrados como saunas, masajes y un entorno natural para el descanso y la recreación en una de las ciudades termales más importantes de Argentina.</p>
            <h3 class="termal-subtitle" data-i18n="termal_subtitle">Características Principales:</h3>
            <ul class="termal-list">
                <li class="termal-list-item">
                    <strong data-i18n="termal_feature_1_label">Ubicación:</strong> 
                    <span data-i18n="termal_feature_1_text">Parque Martín Miguel de Güemes, un predio tradicional con flora, sanitarios y vestuarios.</span>
                </li>
                <li class="termal-list-item">
                    <strong data-i18n="termal_feature_2_label">Aguas Termales:</strong> 
                    <span data-i18n="termal_feature_2_text">Aguas ricas en sales y minerales, provenientes de 14 napas subterráneas, con temperaturas que varían entre los 30°C y 85°C, ideales para baños terapéuticos y recreativos.</span>
                </li>
                <li class="termal-list-item">
                    <strong data-i18n="termal_feature_3_label">Beneficios:</strong> 
                    <span data-i18n="termal_feature_3_text">Ayudan en tratamientos de reumatismo, artrosis, estrés, problemas de piel (psoriasis) y revitalizan el cuerpo, mejorando la circulación y la relajación.</span>
                </li>
                <li class="termal-list-item">
                    <strong data-i18n="termal_feature_4_label">Servicios:</strong> 
                    <span data-i18n="termal_feature_4_text">Incluyen piscinas lúdicas (al aire libre y cubiertas), circuitos de hidroterapia (saunas, vapor, duchas), salas de relajación, masajes y actividades recreativas.</span>
                </li>
                <li class="termal-list-item">
                    <strong data-i18n="termal_feature_5_label">Experiencia:</strong> 
                    <span data-i18n="termal_feature_5_text">Combina salud y esparcimiento, con un enfoque en la balneoterapia (tratamientos con agua) para el bienestar físico y mental, en un ambiente de ciudad spa.</span>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- TERMAL SECTION -->

<!-- GALLERY SECTION -->
<div class="gallery-section">
    <div class="gallery-container">
        <div class="gallery-row gallery-row-1">
            <div class="gallery-item" data-modal-id="1" data-map-url="https://maps.app.goo.gl/iS7U5LmQjgv7DkJBA">
                <img src="img/landing/parque_ashpa_kausay.png" alt="COMPLEJO ASHPA KAUSAY (PARQUE ECOLÓGICO)" class="gallery-image">
                <div class="gallery-item-overlay" aria-hidden="true">
                    <span class="gallery-item-title"></span>
                    <span class="gallery-item-cta" data-i18n="gallery_ver_mas">ver mas</span>
                    <a href="https://maps.app.goo.gl/iS7U5LmQjgv7DkJBA" class="gallery-item-map-link" target="_blank" rel="noopener" aria-label="Ver en mapa"><img src="img/icons/map_icon.png" alt="" class="gallery-item-map-icon"></a>
                </div>
            </div>
            <div class="gallery-item" data-modal-id="2" data-map-url="https://maps.app.goo.gl/DTnFZimEtd5xyTEM8">
                <img src="img/landing/parque_aguirre.png" alt="EL PARQUE AGUIRRE (UN LUGAR PARA RESPIRAR PAZ)" class="gallery-image">
                <div class="gallery-item-overlay" aria-hidden="true">
                    <span class="gallery-item-title"></span>
                    <span class="gallery-item-cta" data-i18n="gallery_ver_mas">ver mas</span>
                    <a href="https://maps.app.goo.gl/DTnFZimEtd5xyTEM8" class="gallery-item-map-link" target="_blank" rel="noopener" aria-label="Ver en mapa"><img src="img/icons/map_icon.png" alt="" class="gallery-item-map-icon"></a>
                </div>
            </div>
            <div class="gallery-item" data-modal-id="3" data-map-url="https://maps.app.goo.gl/NLQWxGaEPUqW2giw7">
                <img src="img/landing/costanera.png" alt="COSTANERA" class="gallery-image">
                <div class="gallery-item-overlay" aria-hidden="true">
                    <span class="gallery-item-title"></span>
                    <span class="gallery-item-cta" data-i18n="gallery_ver_mas">ver mas</span>
                    <a href="https://maps.app.goo.gl/NLQWxGaEPUqW2giw7" class="gallery-item-map-link" target="_blank" rel="noopener" aria-label="Ver en mapa"><img src="img/icons/map_icon.png" alt="" class="gallery-item-map-icon"></a>
                </div>
            </div>
            <div class="gallery-item" data-modal-id="4" data-map-url="https://maps.app.goo.gl/jGn6mc8M4A3AyeMt6">
                <img src="img/landing/monumento_francisco_aguirre.png" alt="MONUMENTO A FRANCISCO DE AGUIRRE" class="gallery-image">
                <div class="gallery-item-overlay" aria-hidden="true">
                    <span class="gallery-item-title"></span>
                    <span class="gallery-item-cta" data-i18n="gallery_ver_mas">ver mas</span>
                    <a href="https://maps.app.goo.gl/jGn6mc8M4A3AyeMt6" class="gallery-item-map-link" target="_blank" rel="noopener" aria-label="Ver en mapa"><img src="img/icons/map_icon.png" alt="" class="gallery-item-map-icon"></a>
                </div>
            </div>
        </div>
        <div class="gallery-row gallery-row-2">
            <div class="gallery-item" data-modal-id="5" data-map-url="https://maps.app.goo.gl/EbTxmJrsHu9aWH2D6">
                <img src="img/landing/plaza_libertad.png" alt="LA PLAZA LIBERTAD" class="gallery-image">
                <div class="gallery-item-overlay" aria-hidden="true">
                    <span class="gallery-item-title"></span>
                    <span class="gallery-item-cta" data-i18n="gallery_ver_mas">ver mas</span>
                    <a href="https://maps.app.goo.gl/EbTxmJrsHu9aWH2D6" class="gallery-item-map-link" target="_blank" rel="noopener" aria-label="Ver en mapa"><img src="img/icons/map_icon.png" alt="" class="gallery-item-map-icon"></a>
                </div>
            </div>
            <div class="gallery-item" data-modal-id="6" data-map-url="https://maps.app.goo.gl/WzGr46GufNGkyGRh6">
                <img src="img/landing/jardin_botanico.png" alt="JARDIN BOTANICO" class="gallery-image">
                <div class="gallery-item-overlay" aria-hidden="true">
                    <span class="gallery-item-title"></span>
                    <span class="gallery-item-cta" data-i18n="gallery_ver_mas">ver mas</span>
                    <a href="https://maps.app.goo.gl/WzGr46GufNGkyGRh6" class="gallery-item-map-link" target="_blank" rel="noopener" aria-label="Ver en mapa"><img src="img/icons/map_icon.png" alt="" class="gallery-item-map-icon"></a>
                </div>
            </div>
            <div class="gallery-item" data-modal-id="7" data-map-url="https://maps.app.goo.gl/2Pjd4fPX1ufqcYYx7">
                <img src="img/landing/estadio_hockey.png" alt="ESTADIO DE HOCKEY" class="gallery-image">
                <div class="gallery-item-overlay" aria-hidden="true">
                    <span class="gallery-item-title"></span>
                    <span class="gallery-item-cta" data-i18n="gallery_ver_mas">ver mas</span>
                    <a href="https://maps.app.goo.gl/2Pjd4fPX1ufqcYYx7" class="gallery-item-map-link" target="_blank" rel="noopener" aria-label="Ver en mapa"><img src="img/icons/map_icon.png" alt="" class="gallery-item-map-icon"></a>
                </div>
            </div>
            <div class="gallery-item" data-modal-id="11" data-map-url="https://maps.app.goo.gl/STFUxjopFBHAWJ5U7">
                <img src="img/landing/parque_santo.png" alt="UN PARQUE CASI SANTO" class="gallery-image">
                <div class="gallery-item-overlay" aria-hidden="true">
                    <span class="gallery-item-title"></span>
                    <span class="gallery-item-cta" data-i18n="gallery_ver_mas">ver mas</span>
                    <a href="https://maps.app.goo.gl/STFUxjopFBHAWJ5U7" class="gallery-item-map-link" target="_blank" rel="noopener" aria-label="Ver en mapa"><img src="img/icons/map_icon.png" alt="" class="gallery-item-map-icon"></a>
                </div>
            </div>
        </div>
        <div class="gallery-row gallery-row-3">
            <div class="gallery-item" data-modal-id="9" data-map-url="https://maps.app.goo.gl/XXSRaBF56LJP5Ax68">
                <img src="img/landing/parque_sur.png" alt="OVALO PARQUE SUR" class="gallery-image">
                <div class="gallery-item-overlay" aria-hidden="true">
                    <span class="gallery-item-title"></span>
                    <span class="gallery-item-cta" data-i18n="gallery_ver_mas">ver mas</span>
                    <a href="https://maps.app.goo.gl/XXSRaBF56LJP5Ax68" class="gallery-item-map-link" target="_blank" rel="noopener" aria-label="Ver en mapa"><img src="img/icons/map_icon.png" alt="" class="gallery-item-map-icon"></a>
                </div>
            </div>
            <div class="gallery-item" data-modal-id="10" data-map-url="https://maps.app.goo.gl/zMCaRAyf4c5wxKhH9">
                <img src="img/landing/complejo_casa_taboada.png" alt="COMPLEJO CASA TABOADA" class="gallery-image">
                <div class="gallery-item-overlay" aria-hidden="true">
                    <span class="gallery-item-title"></span>
                    <span class="gallery-item-cta" data-i18n="gallery_ver_mas">ver mas</span>
                    <a href="https://maps.app.goo.gl/zMCaRAyf4c5wxKhH9" class="gallery-item-map-link" target="_blank" rel="noopener" aria-label="Ver en mapa"><img src="img/icons/map_icon.png" alt="" class="gallery-item-map-icon"></a>
                </div>
            </div>
            <div class="gallery-item" data-modal-id="8" data-map-url="https://maps.app.goo.gl/G2vMm39bbMAdohbQ7">
                <img src="img/landing/parque_norte.png" alt="PARQUE NORTE" class="gallery-image">
                <div class="gallery-item-overlay" aria-hidden="true">
                    <span class="gallery-item-title"></span>
                    <span class="gallery-item-cta" data-i18n="gallery_ver_mas">ver mas</span>
                    <a href="https://maps.app.goo.gl/G2vMm39bbMAdohbQ7" class="gallery-item-map-link" target="_blank" rel="noopener" aria-label="Ver en mapa"><img src="img/icons/map_icon.png" alt="" class="gallery-item-map-icon"></a>
                </div>
            </div>
            <div class="gallery-item" data-modal-id="12" data-map-url="https://maps.app.goo.gl/VWXvXmiw75PTnYuC6">
                <img src="img/landing/domo_parque_encuentro.png" alt="DOMO EN PARQUE DEL ENCUENTRO" class="gallery-image">
                <div class="gallery-item-overlay" aria-hidden="true">
                    <span class="gallery-item-title"></span>
                    <span class="gallery-item-cta" data-i18n="gallery_ver_mas">ver mas</span>
                    <a href="https://maps.app.goo.gl/VWXvXmiw75PTnYuC6" class="gallery-item-map-link" target="_blank" rel="noopener" aria-label="Ver en mapa"><img src="img/icons/map_icon.png" alt="" class="gallery-item-map-icon"></a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- GALLERY SECTION -->

<!-- MODAL WINDOW -->
<div id="gallery-modal" class="gallery-modal">
    <div class="gallery-modal-overlay"></div>
    <div class="gallery-modal-content">
        <button class="gallery-modal-close" aria-label="Close">&times;</button>
        <div class="gallery-modal-image-container">
            <img id="modal-second-image" src="" alt="" class="gallery-modal-second-image">
        </div>
        <div class="gallery-modal-text-container">
            <h2 id="modal-title" class="gallery-modal-title" data-i18n-html=""></h2>
            <div id="modal-content" class="gallery-modal-content-text"></div>
        </div>
    </div>
</div>
<!-- MODAL WINDOW -->

<!-- FOOTER -->
<div class="landing-footer" id="contactos">
    <div class="landing-footer-container">
        <div class="landing-footer-columns">
            <div class="landing-footer-column landing-footer-accesos">
                <h3 class="landing-footer-title" data-i18n="footer_accesos">ACCESOS</h3>
                <ul class="landing-footer-links">
                    <li><a href="#nosotros" data-i18n="footer_nosotros">Nosotros</a></li>
                    <li><a href="<?= htmlspecialchars($__pdf_es) ?>" class="js-pdf-link" target="_blank" rel="noopener" data-pdf-url-es="<?= htmlspecialchars($__pdf_es) ?>" data-pdf-url-en="<?= htmlspecialchars($__pdf_en) ?>" data-i18n="footer_oferta">Oferta exportable</a></li>
                    <li><a href="https://turismosantiago.gob.ar/" target="_blank" rel="noopener" data-i18n="nav_turismo">Turismo</a></li>
                    <li><a href="#contactos" data-i18n="footer_contacto">Contacto</a></li>
                </ul>
            </div>
            <div class="landing-footer-column landing-footer-empresas">
                <h3 class="landing-footer-title" data-i18n="footer_empresas">EMPRESAS</h3>
                <ul class="landing-footer-links">
                    <li><a href="https://drive.google.com/file/d/1AIABKb8UhAcMzyNn-1A3F3gkD7ig854_/view" target="_blank" rel="noopener" data-i18n="footer_registro">Cómo registrarse</a></li>
                    <li><a href="https://drive.google.com/file/d/1AIABKb8UhAcMzyNn-1A3F3gkD7ig854_/view" target="_blank" rel="noopener" data-i18n="footer_cargar">Cómo cargar productos/servicios</a></li>
                </ul>
            </div>
            <div class="landing-footer-column landing-footer-redes">
                <h3 class="landing-footer-title" data-i18n="footer_redes">REDES</h3>
                <div class="landing-footer-social">
                    <a href="#" target="_blank" rel="noopener" aria-label="Instagram" class="landing-footer-social-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                    <a href="#" target="_blank" rel="noopener" aria-label="X (Twitter)" class="landing-footer-social-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="#" target="_blank" rel="noopener" aria-label="Facebook" class="landing-footer-social-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                </div>
            </div>
        </div>
        <div class="landing-footer-bottom">
            <div class="landing-footer-bottom-row">
                <div class="landing-footer-logos">
                    <div class="landing-footer-logo">
                        <img src="img/logo_white.png" alt="Santiago del Estero" class="landing-footer-logo-image">
                    </div>
                    <div class="landing-footer-logo landing-footer-logo-cfi">
                        <img src="img/logo_cfi.svg" alt="CFI" class="landing-footer-logo-image">
                    </div>
                </div>
                <div class="footer-pdf-group">
                    <a href="index.php?page=clasico_pdf_es" class="btn btn-footer-pdf js-pdf-link" target="_blank" rel="noopener" aria-label="PDF Clásico" data-pdf-url-es="index.php?page=clasico_pdf_es" data-pdf-url-en="index.php?page=clasico_pdf_en"></a>
                    <a href="index.php?page=corporativo_pdf_es" class="btn btn-footer-pdf js-pdf-link" target="_blank" rel="noopener" aria-label="PDF Corporativo" data-pdf-url-es="index.php?page=corporativo_pdf_es" data-pdf-url-en="index.php?page=corporativo_pdf_en"></a>
                    <a href="index.php?page=moderno_pdf_es" class="btn btn-footer-pdf js-pdf-link" target="_blank" rel="noopener" aria-label="PDF Moderno" data-pdf-url-es="index.php?page=moderno_pdf_es" data-pdf-url-en="index.php?page=moderno_pdf_en"></a>
                </div>
            </div>
            <p class="landing-footer-copyright" data-i18n="footer_copyright">Copyright © 2026. Santiago del Estero. Todos los derechos reservados.</p>
        </div>
    </div>
</div>
<!-- FOOTER -->

<script src="js/i18n.js?v=<?= asset_version('js/i18n.js') ?>"></script>
<script>
function toggleLangMenu() {
  const menu = document.getElementById('landing_header_lang_menu');
  menu.classList.toggle('hidden');
}
document.addEventListener('DOMContentLoaded', () => {
  initLang('landing');
  // Burger menu
  const burgerBtn = document.getElementById('hero-burger-btn');
  const heroHeader = document.querySelector('.hero-header');
  if (burgerBtn && heroHeader) {
    burgerBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      heroHeader.classList.toggle('nav-open');
      burgerBtn.setAttribute('aria-expanded', heroHeader.classList.contains('nav-open'));
    });
    heroHeader.querySelectorAll('.nav-container .nav-link:not(.oferta-dropdown-trigger), .nav-container .nav-whatsapp').forEach(el => {
      el.addEventListener('click', () => {
        heroHeader.classList.remove('nav-open');
        burgerBtn.setAttribute('aria-expanded', 'false');
      });
    });
  }
  // После окончания фонового видео показываем статичную картинку
  const heroVideo = document.getElementById('hero-bg-video');
  const heroSection = document.querySelector('.hero-section');
  if (heroVideo && heroSection) {
    heroVideo.addEventListener('ended', function() {
      heroSection.classList.add('hero-video-ended');
    });
  }
  // Oferta exportable dropdown
  const ofertaDropdown = document.getElementById('oferta-dropdown');
  const ofertaBtn = document.getElementById('oferta-dropdown-btn');
  const ofertaMenu = document.getElementById('oferta-dropdown-menu');
  if (ofertaDropdown && ofertaBtn && ofertaMenu) {
    ofertaBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      ofertaDropdown.classList.toggle('open');
      ofertaBtn.setAttribute('aria-expanded', ofertaDropdown.classList.contains('open'));
    });
    document.querySelectorAll('.oferta-dropdown-item:not(.js-pdf-link)').forEach(link => {
      link.addEventListener('click', function() {
        const url = this.getAttribute('data-pdf-url');
        if (url) this.href = url + (url.indexOf('?') >= 0 ? '&' : '?') + 't=' + Date.now();
      });
    });
  }
  document.addEventListener('click', (e) => {
    if (ofertaDropdown && !ofertaDropdown.contains(e.target)) {
      ofertaDropdown.classList.remove('open');
      if (ofertaBtn) ofertaBtn.setAttribute('aria-expanded', 'false');
    }
  });
  // Preload hover images for smooth transition
  const estadioHoverImage = new Image();
  estadioHoverImage.src = 'img/landing/landing_estadio_2.png';
  const termalHoverImage = new Image();
  termalHoverImage.src = 'img/landing/landing_termal_2.png';
  
  // Gallery modal functionality
  const modal = document.getElementById('gallery-modal');
  const modalOverlay = modal.querySelector('.gallery-modal-overlay');
  const modalClose = modal.querySelector('.gallery-modal-close');
  const modalSecondImage = document.getElementById('modal-second-image');
  const modalTitle = document.getElementById('modal-title');
  const modalContent = document.getElementById('modal-content');
  const galleryItems = document.querySelectorAll('.gallery-item');
  
  // Modal data mapping
  const modalData = {
    1: { secondImage: 'img/landing/parque_ashpa_kausay_2.png', titleKey: 'modal_1_title', contentKey: 'modal_1_content' },
    2: { secondImage: 'img/landing/parque_aguirre_2.png', titleKey: 'modal_2_title', contentKey: 'modal_2_content' },
    3: { secondImage: 'img/landing/costanera_2.png', titleKey: 'modal_3_title', contentKey: 'modal_3_content' },
    4: { secondImage: 'img/landing/monumento_francisco_aguirre_2.png', titleKey: 'modal_4_title', contentKey: 'modal_4_content' },
    5: { secondImage: 'img/landing/plaza_libertad_2.png', titleKey: 'modal_5_title', contentKey: 'modal_5_content' },
    6: { secondImage: 'img/landing/jardin_botanico_2.png', titleKey: 'modal_6_title', contentKey: 'modal_6_content' },
    7: { secondImage: 'img/landing/estadio_hockey_2.png', titleKey: 'modal_7_title', contentKey: 'modal_7_content' },
    8: { secondImage: 'img/landing/parque_norte_2.png', titleKey: 'modal_8_title', contentKey: 'modal_8_content' },
    9: { secondImage: 'img/landing/parque_sur_2.png', titleKey: 'modal_9_title', contentKey: 'modal_9_content' },
    10: { secondImage: 'img/landing/complejo_casa_taboada_2.png', titleKey: 'modal_10_title', contentKey: 'modal_10_content' },
    11: { secondImage: 'img/landing/parque_santo_2.png', titleKey: 'modal_11_title', contentKey: 'modal_11_content' },
    12: { secondImage: 'img/landing/domo_parque_encuentro_2.png', titleKey: 'modal_12_title', contentKey: 'modal_12_content' }
  };
  
  async function openModal(modalId) {
    const data = modalData[modalId];
    if (!data) return;
    
    modalSecondImage.src = data.secondImage;
    modalTitle.setAttribute('data-i18n-html', data.titleKey);
    modalContent.setAttribute('data-i18n-html', data.contentKey);
    
    // Update translations and then open modal
    const currentLang = localStorage.getItem('lang') || 'es';
    await setLang('landing', currentLang);
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  
  function closeModal() {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
  
  // Fill overlay title from image alt
  galleryItems.forEach(item => {
    const img = item.querySelector('.gallery-image');
    const titleEl = item.querySelector('.gallery-item-title');
    if (img && titleEl) titleEl.textContent = img.getAttribute('alt') || '';
  });

  // Open modal on gallery item click (not when clicking map link)
  galleryItems.forEach(item => {
    item.addEventListener('click', (e) => {
      if (e.target.closest('.gallery-item-map-link')) return;
      const modalId = parseInt(item.getAttribute('data-modal-id'));
      openModal(modalId);
    });
  });
  
  // Close modal handlers
  modalClose.addEventListener('click', closeModal);
  modalOverlay.addEventListener('click', closeModal);
  
  // Close on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('active')) {
      closeModal();
    }
  });

  // Products carousel (infinite loop)
  const carouselSection = document.querySelector('.products-carousel-section');
  if (carouselSection) {
    const track = carouselSection.querySelector('.products-carousel-track');
    const slides = carouselSection.querySelectorAll('.products-carousel-slide');
    const prevBtn = carouselSection.querySelector('.products-carousel-btn-prev');
    const nextBtn = carouselSection.querySelector('.products-carousel-btn-next');
    const totalSlides = slides.length;
    const uniqueCount = totalSlides / 2;
    let currentIndex = 0;
    let slidesToShow = 4;
    let isTransitioning = false;
    const gap = 20;
    const updateSlidesToShow = () => {
      slidesToShow = window.innerWidth >= 1200 ? 4 : window.innerWidth >= 768 ? 3 : window.innerWidth >= 480 ? 2 : 1;
      carouselSection.classList.remove('slides-1','slides-2','slides-3','slides-4');
      carouselSection.classList.add('slides-' + slidesToShow);
    };
    updateSlidesToShow();
    window.addEventListener('resize', () => { updateSlidesToShow(); applyTransform(false); });
    const getSlideWidth = () => {
      const container = track && track.parentElement;
      if (!container) return 0;
      return (container.offsetWidth + gap) / slidesToShow - gap;
    };
    function applyTransform(withTransition = true) {
      if (!track) return;
      const w = getSlideWidth();
      if (w <= 0) return;
      track.style.transition = withTransition ? 'transform 0.3s ease' : 'none';
      track.style.transform = `translateX(-${currentIndex * (w + gap)}px)`;
    }
    function onTransitionEnd() {
      isTransitioning = false;
      if (currentIndex >= uniqueCount) {
        currentIndex = currentIndex - uniqueCount;
        applyTransform(false);
      } else if (currentIndex < 0) {
        currentIndex = currentIndex + uniqueCount;
        applyTransform(false);
      }
    }
    function goToSlide(idx) {
      if (isTransitioning) return;
      isTransitioning = true;
      currentIndex = idx;
      applyTransform(true);
      const te = () => { track.removeEventListener('transitionend', te); onTransitionEnd(); };
      track.addEventListener('transitionend', te);
      setTimeout(() => { if (isTransitioning) { track.removeEventListener('transitionend', te); onTransitionEnd(); } }, 350);
    }
    if (prevBtn) prevBtn.addEventListener('click', () => goToSlide(currentIndex - 1));
    if (nextBtn) nextBtn.addEventListener('click', () => goToSlide(currentIndex + 1));
    currentIndex = 0;
    applyTransform(false);
    onTransitionEnd();
  }
});
document.addEventListener('click', function (e) {
  const langBox = document.querySelector('.landing_header_lang');
  const menu = document.getElementById('landing_header_lang_menu');
  if (!langBox.contains(e.target)) {
    menu.classList.add('hidden');
  }
});
</script>