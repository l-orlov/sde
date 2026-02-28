<?php
$__landing_config = file_exists(__DIR__ . '/config/config.php') ? (require __DIR__ . '/config/config.php') : [];
$__web_base = rtrim($__landing_config['web_base'] ?? '', '/');
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
    'static_pdf'   => $__web_base . '/index.php?page=download_oferta_pdf',
];
?>
<div class="search-page-wrap">
    <div class="hero-header hero-header-search">
        <div class="hero-header-search-container">
        <a href="?page=landing" class="logo logo-link">
        <img src="img/logo.svg" alt="Santiago del Estero" class="logo-image">
        </a>
        <div class="nav-container">
        <nav class="hero-nav">
            <div class="oferta-dropdown" id="oferta-dropdown">
                <button type="button" class="nav-link oferta-dropdown-trigger nav-link-search" data-i18n="nav_exportable" aria-expanded="false" aria-haspopup="true" id="oferta-dropdown-btn">Oferta Exportable</button>
                <ul class="oferta-dropdown-menu" id="oferta-dropdown-menu" role="menu" aria-label="Formatos de oferta exportable">
                    <li role="none">
                        <a role="menuitem" class="oferta-dropdown-item js-pdf-link" href="<?= htmlspecialchars($__pdf_oferta_urls['static_pdf']) ?>" target="_blank" rel="noopener" data-pdf-url-es="<?= htmlspecialchars($__pdf_oferta_urls['static_pdf']) ?>" data-pdf-url-en="<?= htmlspecialchars($__pdf_oferta_urls['static_pdf']) ?>"><span class="oferta-dropdown-name" data-i18n="pdf_name_clasico">Clásico</span><img src="img/icons/clasico_icon.png" alt="" class="oferta-dropdown-icon"></a>
                    </li>
                    <li role="none">
                        <a role="menuitem" class="oferta-dropdown-item js-pdf-link" href="<?= htmlspecialchars($__pdf_oferta_urls['static_pdf']) ?>" target="_blank" rel="noopener" data-pdf-url-es="<?= htmlspecialchars($__pdf_oferta_urls['static_pdf']) ?>" data-pdf-url-en="<?= htmlspecialchars($__pdf_oferta_urls['static_pdf']) ?>"><span class="oferta-dropdown-name" data-i18n="pdf_name_corporativo">Corporativo</span><img src="img/icons/corporativo_icon.png" alt="" class="oferta-dropdown-icon"></a>
                    </li>
                    <li role="none">
                        <a role="menuitem" class="oferta-dropdown-item js-pdf-link" href="<?= htmlspecialchars($__pdf_oferta_urls['static_pdf']) ?>" target="_blank" rel="noopener" data-pdf-url-es="<?= htmlspecialchars($__pdf_oferta_urls['static_pdf']) ?>" data-pdf-url-en="<?= htmlspecialchars($__pdf_oferta_urls['static_pdf']) ?>"><span class="oferta-dropdown-name" data-i18n="pdf_name_moderno">Moderno</span><img src="img/icons/moderno_icon.png" alt="" class="oferta-dropdown-icon"></a>
                    </li>
                </ul>
            </div>
            <span class="hero-nav-sep hero-nav-sep-search" aria-hidden="true">|</span>
            <a data-i18n="nav_search" href="?page=search" class="nav-link nav-link-search">Buscar</a>
            <span class="hero-nav-sep hero-nav-sep-search" aria-hidden="true">|</span>
            <a data-i18n="nav_turismo" href="?page=landing#turismo" class="nav-link nav-link-search">Turismo</a>
            <span class="hero-nav-sep hero-nav-sep-search" aria-hidden="true">|</span>
            <a data-i18n="nav_contact" href="?page=landing#contactos" class="nav-link nav-link-search">Contacto</a>
            <a href="https://wa.me/" class="nav-whatsapp" target="_blank" rel="noopener">
                <img src="img/icono_whatsapp.png" alt="WhatsApp" class="whatsapp-icon">
            </a>
        </nav>
        </div>
        <div class="hero-buttons">
        <a data-i18n="btn_register" href="?page=regnew" class="btn btn-register">Registrarse</a>
        <a data-i18n="btn_login" href="?page=login" class="btn btn-login">Entrar</a>
        </div>
        <div class="landing_header_lang" onclick="toggleLangMenu()">
        <img src="img/icons/lang.png" alt="">
        <span id="current-lang">ES</span>
        <ul id="landing_header_lang_menu" class="landing_header_lang_menu hidden">
            <li onclick="setLang('search', 'es')">Español</li>
            <li onclick="setLang('search', 'en')">English</li>
        </ul>
        </div>
        </div>
    </div>

    <div class="search-page-main">
        <div class="search-page-top-row">
            <a href="?page=landing" class="search-back-btn" data-i18n="search_back">Volver</a>
        </div>
        <h1 class="search-page-title" data-i18n="search_title">BUSCADOR DE PRODUCTOS Y SERVICIOS</h1>

        <div class="search-box-wrap">
            <span class="search-box-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            </span>
            <input type="text" id="search-input" class="search-box-input" placeholder="Buscar por código arancelario o nombre" data-i18n-placeholder="search_placeholder" autocomplete="off">
            <button type="button" class="search-box-clear" id="search-clear" aria-label="Limpiar" title="Limpiar" style="display: none;">&times;</button>
        </div>

        <div class="search-suggestions" id="search-suggestions" role="listbox" aria-label="Sugerencias" style="display: none;"></div>

        <div class="search-results" id="search-results"></div>
    </div>

    <div class="landing-footer" id="contactos">
        <div class="landing-footer-container">
            <div class="landing-footer-columns">
                <div class="landing-footer-column landing-footer-accesos">
                    <h3 class="landing-footer-title" data-i18n="footer_accesos">ACCESOS</h3>
                    <ul class="landing-footer-links">
                        <li><a href="?page=landing#nosotros" data-i18n="footer_nosotros">Nosotros</a></li>
                        <li><a href="<?= htmlspecialchars($__pdf_oferta_urls['static_pdf']) ?>" class="js-pdf-link" target="_blank" rel="noopener" data-pdf-url-es="<?= htmlspecialchars($__pdf_oferta_urls['static_pdf']) ?>" data-pdf-url-en="<?= htmlspecialchars($__pdf_oferta_urls['static_pdf']) ?>" data-i18n="footer_oferta">Oferta exportable</a></li>
                        <li><a href="?page=landing#turismo" data-i18n="nav_turismo">Turismo</a></li>
                        <li><a href="?page=landing#noticias" data-i18n="nav_news">Noticias</a></li>
                        <li><a href="?page=landing#contactos" data-i18n="footer_contacto">Contacto</a></li>
                    </ul>
                </div>
                <div class="landing-footer-column landing-footer-empresas">
                    <h3 class="landing-footer-title" data-i18n="footer_empresas">EMPRESAS</h3>
                    <ul class="landing-footer-links">
                        <li><a href="?page=regnew" data-i18n="footer_registro">Cómo registrarse</a></li>
                        <li><a href="?page=landing#empresas_cargar" data-i18n="footer_cargar">Cómo cargar productos/servicios</a></li>
                        <li><a href="?page=landing#faq" data-i18n="footer_faq">Preguntas frecuentes</a></li>
                        <li><a href="?page=landing#soporte" data-i18n="footer_soporte">Soporte técnico / Mesa de ayuda</a></li>
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
                <div class="landing-footer-logo">
                    <img src="img/logo_white.png" alt="Santiago del Estero" class="landing-footer-logo-image">
                </div>
                <p class="landing-footer-copyright" data-i18n="footer_copyright">Copyright © 2026. Santiago del Estero. Todos los derechos reservados.</p>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var searchInput = document.getElementById('search-input');
    var searchClear = document.getElementById('search-clear');
    var searchSuggestions = document.getElementById('search-suggestions');
    var searchResults = document.getElementById('search-results');
    var baseUrl = '<?= addslashes($__web_base) ?>' || '';

    if (searchClear) {
        searchClear.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            searchClear.style.display = 'none';
            if (searchSuggestions) searchSuggestions.style.display = 'none';
            searchSuggestions.innerHTML = '';
            searchResults.innerHTML = '';
        });
    }
    var searchDebounceTimer = null;
    var MIN_SEARCH_LEN = 3;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchClear.style.display = this.value.trim() ? 'flex' : 'none';
            var q = this.value.trim();
            if (q.length < MIN_SEARCH_LEN) {
                searchSuggestions.style.display = 'none';
                searchSuggestions.innerHTML = '';
                searchResults.innerHTML = '';
                if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
                return;
            }
            // Sugerencias (códigos arancelarios)
            fetch((baseUrl || '') + '/index.php?page=search_api&q=' + encodeURIComponent(q) + '&suggest=1')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.suggestions && data.suggestions.length) {
                        searchSuggestions.innerHTML = data.suggestions.map(function(s) {
                            return '<div class="search-suggestion-item" role="option" tabindex="0" data-value="' + (s.value || '').replace(/"/g, '&quot;') + '">' +
                                '<span class="search-suggestion-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span>' +
                                (s.label || s.value || '') + '</div>';
                        }).join('');
                        searchSuggestions.style.display = 'block';
                        searchSuggestions.querySelectorAll('.search-suggestion-item').forEach(function(el) {
                            el.addEventListener('click', function() {
                                searchInput.value = el.getAttribute('data-value') || '';
                                searchSuggestions.style.display = 'none';
                                doSearch();
                            });
                        });
                    } else {
                        searchSuggestions.style.display = 'none';
                        searchSuggestions.innerHTML = '';
                    }
                })
                .catch(function() { searchSuggestions.style.display = 'none'; });

            // Resultados (productos/servicios) con debounce 300 ms
            if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(function() {
                searchDebounceTimer = null;
                doSearch();
            }, 300);
        });
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchSuggestions.style.display = 'none';
                if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
                searchDebounceTimer = null;
                doSearch();
            }
        });
    }

    function doSearch() {
        var q = (searchInput && searchInput.value.trim()) || '';
        if (!q) {
            searchResults.innerHTML = '';
            return;
        }
        searchResults.innerHTML = '<p class="search-loading">Cargando...</p>';
        fetch((baseUrl || '') + '/index.php?page=search_api&q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var currentQ = (searchInput && searchInput.value.trim()) || '';
                if (currentQ !== q) return;
                if (!data.items || !data.items.length) {
                    searchResults.innerHTML = '<p class="search-no-results" data-i18n="search_no_results">No se encontraron resultados.</p>';
                    return;
                }
                searchResults.innerHTML = data.items.map(function(item) {
                    var img = (item.image_url ? '<img src="' + item.image_url.replace(/"/g, '&quot;') + '" alt="" class="search-card-img">' : '<div class="search-card-img search-card-img-placeholder"></div>');
                    var typeLabel = (item.type === 'service' ? 'Servicio' : 'Producto');
                    var name = (item.name || '').replace(/</g, '&lt;');
                    var code = (item.tariff_code || '').replace(/</g, '&lt;');
                    return '<article class="search-card">' +
                        '<div class="search-card-body">' +
                        '<div class="search-card-header-row">' +
                        '<span class="search-card-title">' + typeLabel + ' - ' + name + '</span>' +
                        '<span class="search-card-code-block">' + code + '</span>' +
                        '</div>' +
                        '<div class="search-card-divider"></div>' +
                        '<div class="search-card-content-row">' +
                        '<div class="search-card-text">' +
                        '<p class="search-card-company"><span class="search-card-label">Empresa</span><br><span>' + (item.company_name || 'Nombre de empresa').replace(/</g, '&lt;') + '</span></p>' +
                        '<p class="search-card-contact"><span class="search-card-label">Contacto</span><br>' +
                            (item.email ? item.email.replace(/</g, '&lt;') + '<br>' : '') +
                            (item.phone ? item.phone.replace(/</g, '&lt;') + '<br>' : '') +
                            (item.website ? item.website.replace(/</g, '&lt;') : '') + '</p>' +
                        '<p class="search-card-locality"><span class="search-card-label">Localidad</span><br><span>' + (item.locality || 'Localidad/Departamento').replace(/</g, '&lt;') + '</span></p>' +
                        '</div>' +
                        '<div class="search-card-image">' + img + '</div>' +
                        '</div>' +
                        '</div></article>';
                }).join('');
            })
            .catch(function() {
                searchResults.innerHTML = '<p class="search-no-results">Error al buscar. Intente de nuevo.</p>';
            });
    }
})();
</script>
<script src="js/i18n.js?v=<?= asset_version('js/i18n.js') ?>"></script>
<script>
function toggleLangMenu() {
  var menu = document.getElementById('landing_header_lang_menu');
  if (menu) menu.classList.toggle('hidden');
  var oferta = document.getElementById('oferta-dropdown');
  if (oferta) oferta.classList.remove('open');
}
document.addEventListener('DOMContentLoaded', function() {
  if (typeof initLang === 'function') initLang('search', 'es');
  var ofertaBtn = document.getElementById('oferta-dropdown-btn');
  var ofertaMenu = document.getElementById('oferta-dropdown-menu');
  var ofertaDropdown = document.getElementById('oferta-dropdown');
  if (ofertaBtn && ofertaMenu && ofertaDropdown) {
    ofertaBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      ofertaDropdown.classList.toggle('open');
    });
    document.addEventListener('click', function() { ofertaDropdown.classList.remove('open'); });
  }
});
</script>
