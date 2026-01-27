<!-- HEADER -->
<div class="hero-section">
    <header class="hero-header">
        <div class="logo">
            <img src="img/logo.svg" alt="Santiago del Estero" class="logo-image">
        </div>
        <div class="nav-container">
            <nav class="hero-nav">
                <a data-i18n="nav_about" href="#nosotros" class="nav-link">Nosotros</a>
                <a data-i18n="nav_exportable" href="#oferta" class="nav-link">Oferta exportable</a>
                <a data-i18n="nav_news" href="#noticias" class="nav-link">Noticias</a>
                <a data-i18n="nav_contact" href="#contactos" class="nav-link">Contactos</a>
                <a href="https://wa.me/" class="nav-whatsapp" target="_blank">
                    <img src="img/icono_whatsapp.png" alt="WhatsApp" class="whatsapp-icon">
                </a>
            </nav>
        </div>
        <div class="hero-buttons">
            <a data-i18n="btn_register" onclick="location.href='?page=regnew';" class="btn btn-register">Registrarse</a>
            <a data-i18n="btn_login" onclick="location.href='?page=login';" class="btn btn-login">Entrar</a>
        </div>
    </header>
    <div class="landing_header_lang" onclick="toggleLangMenu()">
        <img src="img/icons/lang.png" />
        <span id="current-lang">Es</span>

        <ul id="landing_header_lang_menu" class="landing_header_lang_menu hidden">
            <li onclick="setLang('landing', 'es')">Español</li>
            <li onclick="setLang('landing', 'en')">English</li>
        </ul>
    </div>
    <div class="hero-content">
        <h1 data-i18n="hero_title" class="hero-title">Conectamos el trabajo local<br>con el mercado global</h1>
    </div>
    <div class="hero-footer">
        <div class="hero-tagline">
            <span data-i18n="hero_tagline_normal" class="tagline-normal">CON </span>
            <span data-i18n="hero_tagline_bold" class="tagline-bold">SU GENTE</span>
            <span data-i18n="hero_tagline_normal_2" class="tagline-normal">, EL NORTE AVANZA</span><br>
            <span data-i18n="hero_tagline_normal_3" class="tagline-normal">HACIA UN </span>
            <span data-i18n="hero_tagline_bold_2" class="tagline-bold">FUTURO SOSTENIBLE.</span>
        </div>
    </div>
</div>
<!-- HEADER -->

<!-- ESTADIO SECTION -->
<div class="estadio-section">
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
        <div class="gallery-item" data-modal-id="1">
            <img src="img/landing/parque_ashpa_kausay.png" alt="COMPLEJO ASHPA KAUSAY PARQUE ECOLÓGICO" class="gallery-image">
        </div>
        <div class="gallery-item" data-modal-id="2">
            <img src="img/landing/parque_aguirre.png" alt="EL PARQUE AGUIRRE UN LUGAR PARA RESPIRAR PAZ" class="gallery-image">
        </div>
        <div class="gallery-item" data-modal-id="3">
            <img src="img/landing/costanera.png" alt="COSTANERA" class="gallery-image">
        </div>
        <div class="gallery-item" data-modal-id="4">
            <img src="img/landing/monumento_francisco_aguirre.png" alt="MONUMENTO A FRANCISCO DE AGUIRRE" class="gallery-image">
        </div>
        <div class="gallery-item" data-modal-id="5">
            <img src="img/landing/plaza_libertad.png" alt="LA PLAZA LIBERTAD" class="gallery-image">
        </div>
        <div class="gallery-item" data-modal-id="6">
            <img src="img/landing/jardin_botanico.png" alt="JARDIN BOTANICO" class="gallery-image">
        </div>
        <div class="gallery-item" data-modal-id="7">
            <img src="img/landing/estadio_hockey.png" alt="ESTADIO DE HOCKEY" class="gallery-image">
        </div>
        <div class="gallery-item" data-modal-id="8">
            <img src="img/landing/parque_norte.png" alt="PARQUE NORTE" class="gallery-image">
        </div>
        <div class="gallery-item" data-modal-id="9">
            <img src="img/landing/parque_sur.png" alt="OVALO PARQUE SUR" class="gallery-image">
        </div>
        <div class="gallery-item" data-modal-id="10">
            <img src="img/landing/complejo_casa_taboada.png" alt="COMPLEJO CASA TABOADA" class="gallery-image">
        </div>
        <div class="gallery-item" data-modal-id="11">
            <img src="img/landing/parque_santo.png" alt="UN PARQUE CASI SANTO" class="gallery-image">
        </div>
        <div class="gallery-item" data-modal-id="12">
            <img src="img/landing/domo_parque_encuentro.png" alt="DOMO EN PARQUE DEL ENCUENTRO" class="gallery-image">
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

<script src="https://cdn.jsdelivr.net/npm/masonry-layout@4.2.2/dist/masonry.pkgd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/imagesloaded@5.0.0/imagesloaded.pkgd.min.js"></script>
<script src="js/i18n.js?v=1.0.4"></script>
<script>
function toggleLangMenu() {
  const menu = document.getElementById('landing_header_lang_menu');
  menu.classList.toggle('hidden');
}
document.addEventListener('DOMContentLoaded', () => {
  initLang('landing');
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
  
  // Open modal on gallery item click
  galleryItems.forEach(item => {
    item.addEventListener('click', () => {
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
  
  // Initialize Masonry layout
  const galleryContainer = document.querySelector('.gallery-container');
  if (galleryContainer && typeof Masonry !== 'undefined' && typeof imagesLoaded !== 'undefined') {
    imagesLoaded(galleryContainer, () => {
      const masonry = new Masonry(galleryContainer, {
        itemSelector: '.gallery-item',
        columnWidth: '.gallery-item',
        percentPosition: true,
        gutter: 20
      });
    });
  } else if (galleryContainer && typeof Masonry !== 'undefined') {
    // Fallback if imagesLoaded is not available
    setTimeout(() => {
      const masonry = new Masonry(galleryContainer, {
        itemSelector: '.gallery-item',
        columnWidth: '.gallery-item',
        percentPosition: true,
        gutter: 20
      });
    }, 500);
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