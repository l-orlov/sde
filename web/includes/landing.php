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
<script src="js/i18n.js?v=1.0.4"></script>
<script>
function toggleLangMenu() {
  const menu = document.getElementById('landing_header_lang_menu');
  menu.classList.toggle('hidden');
}
document.addEventListener('DOMContentLoaded', () => {
  initLang('landing');
});
document.addEventListener('click', function (e) {
  const langBox = document.querySelector('.landing_header_lang');
  const menu = document.getElementById('landing_header_lang_menu');
  if (!langBox.contains(e.target)) {
    menu.classList.add('hidden');
  }
});
</script>