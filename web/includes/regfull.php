<!-- EMPRESA -->
<div class="regfull-lang" onclick="toggleRegfullLangMenu()">
  <img src="img/icons/lang.png" alt="Language">
  <span id="regfull-current-lang">Es</span>
  <ul id="regfull_lang_menu" class="regfull_lang_menu hidden">
    <li onclick="setLang('regfull', 'es')">Español</li>
    <li onclick="setLang('regfull', 'en')">English</li>
    <!-- <li onclick="setLang('regfull', 'ru')">Русский</li> -->
  </ul>
</div>
<div class="datos">
  <div class="datos_header">
    <h1 data-i18n="regfull_section1_title">1. Datos de la empresa</h1>
    <img src="img/icons/datos.png">
  </div>
  <div class="form" novalidate>
    <!-- Nombre -->
    <div class="label"><label for="nombre" data-i18n="regfull_company_name">Nombre de la Empresa/Emprendimiento <span class="req">*</span></label></div>
    <div class="field"><input type="search" id="nombre" name="nombre" required></div>
    <!-- CUIT -->
    <div class="label"><label for="cuit" data-i18n="regfull_cuit">CUIT / Identificación Fiscal <span class="req">*</span></label></div>
    <div class="field"><input type="search" id="cuit" name="cuit" data-i18n-placeholder="regfull_cuit_placeholder" placeholder="XX-XXXXXXXX-X" required></div>
    <!-- Razón social -->
    <div class="label"><label for="razon" data-i18n="regfull_razon_social">Razón social <span class="req">*</span></label></div>
    <div class="field"><input type="search" id="razon" name="razon" required></div>
    <!-- Fecha de inicio -->
    <div class="label"><label for="inicio" data-i18n="regfull_start_date">Fecha de Inicio de Actividad <span class="req">*</span></label></div>
    <div class="field"><input type="search" id="inicio" name="inicio" data-i18n-placeholder="regfull_date_placeholder" placeholder="dd/mm/aaaa" inputmode="numeric" required></div>
    <!-- Página web -->
    <div class="label"><label for="web" data-i18n="regfull_website">Página web (si aplica)</label></div>
    <div class="field"><input type="search" id="web" name="web" type="url" data-i18n-placeholder="regfull_website_placeholder" placeholder="http://…"></div>
    <!-- Redes sociales -->
    <div class="label"><span data-i18n="regfull_social_networks">Redes sociales:</span></div>
    <div class="field" id="social-wrapper">
      <div class="social_row">
        <div class="custom-dropdown">
          <div class="dropdown-selected">
            <span class="selected-text">…</span>
            <span class="dropdown-arrow">▼</span>
          </div>
          <div class="dropdown-options">
            <div class="dropdown-option" data-value="">…</div>
            <div class="dropdown-option" data-value="Instagram">Instagram</div>
            <div class="dropdown-option" data-value="Facebook">Facebook</div>
            <div class="dropdown-option" data-value="LinkedIn">LinkedIn</div>
            <div class="dropdown-option" data-value="X (Twitter)">X (Twitter)</div>
            <div class="dropdown-option" data-value="TikTok">TikTok</div>
            <div class="dropdown-option" data-value="YouTube">YouTube</div>
            <div class="dropdown-option" data-value="Otra">Otra</div>
          </div>
          <input type="hidden" class="net" name="social_tipo[]" value="">
        </div>
        <input class="net-other" type="text" data-i18n-placeholder="regfull_social_enter_network" placeholder="Ingresa la red…" hidden>
        <input class="net-final" type="hidden" name="social_tipo[]">
        <div class="inline">
          <input name="social_url[]" type="url" data-i18n-placeholder="regfull_website_placeholder" placeholder="http://…">
          <button type="button" class="remove" hidden>&times;</button>
        </div>
      </div>
      <button type="button" class="add_more" id="add-social" data-i18n="regfull_add_more">agregar más</button>
    </div>
    <!-- Domicilio Legal -->
    <div class="address">
      <div class="label"><span data-i18n="regfull_legal_address">Domicilio Legal <span class="req">*</span></span></div>
      <div class="address_grid">
        <label class="label_span" data-i18n="regfull_street">Calle <span class="req">*</span></label>
        <input type="search" class="span_right">
        <label class="label_span" data-i18n="regfull_number">Altura <span class="req">*</span></label>
        <input type="search" class="">
        <label class="label_span" data-i18n="regfull_postal_code">Código Postal <span class="req">*</span></label>
        <input type="search" class="">
        <label class="label_span" data-i18n="regfull_floor">Piso</label>
        <input type="search" class="">
        <label class="label_span" data-i18n="regfull_apartment">Departamento</label>
        <input type="search" class="">
        <label class="label_span" data-i18n="regfull_locality">Localidad <span class="req">*</span></label>
        <div class="span_right">
          <div class="custom-dropdown">
            <div class="dropdown-selected">
              <span class="selected-text">…</span>
              <span class="dropdown-arrow">▼</span>
            </div>
            <div class="dropdown-options">
              <div class="dropdown-option" data-value="">…</div>
              <div class="dropdown-option" data-value="Santiago del Estero (la capital)">Santiago del Estero (la capital)</div>
              <div class="dropdown-option" data-value="La Banda">La Banda</div>
              <div class="dropdown-option" data-value="Termas de Río Hondo">Termas de Río Hondo</div>
              <div class="dropdown-option" data-value="Añatuya">Añatuya</div>
              <div class="dropdown-option" data-value="Frías">Frías</div>
              <div class="dropdown-option" data-value="Fernández">Fernández</div>
              <div class="dropdown-option" data-value="Clodomira">Clodomira</div>
              <div class="dropdown-option" data-value="Suncho">Suncho</div>
              <div class="dropdown-option" data-value="Corral">Corral</div>
              <div class="dropdown-option" data-value="Loreto">Loreto</div>
              <div class="dropdown-option" data-value="Quimilí">Quimilí</div>
              <div class="dropdown-option" data-value="Beltrán">Beltrán</div>
              <div class="dropdown-option" data-value="Pampa de los Guanacos">Pampa de los Guanacos</div>
              <div class="dropdown-option" data-value="Bandera">Bandera</div>
              <div class="dropdown-option" data-value="Monte Quemado y Sumampa">Monte Quemado y Sumampa</div>
            </div>
            <input type="hidden" name="localidad_legal" value="">
          </div>
        </div>
        <label class="label_span" data-i18n="regfull_department">Departamento <span class="req">*</span></label>
        <div class="span_right">
          <div class="custom-dropdown">
            <div class="dropdown-selected">
              <span class="selected-text">…</span>
              <span class="dropdown-arrow">▼</span>
            </div>
            <div class="dropdown-options">
              <div class="dropdown-option" data-value="">…</div>
              <div class="dropdown-option" data-value="Aguirre">Aguirre</div>
              <div class="dropdown-option" data-value="Alberdi">Alberdi</div>
              <div class="dropdown-option" data-value="Atamisqui">Atamisqui</div>
              <div class="dropdown-option" data-value="Avellaneda">Avellaneda</div>
              <div class="dropdown-option" data-value="Banda">Banda</div>
              <div class="dropdown-option" data-value="Belgrano">Belgrano</div>
              <div class="dropdown-option" data-value="Capital">Capital</div>
              <div class="dropdown-option" data-value="Choya">Choya</div>
              <div class="dropdown-option" data-value="Copo">Copo</div>
              <div class="dropdown-option" data-value="Figueroa">Figueroa</div>
              <div class="dropdown-option" data-value="General Taboada">General Taboada</div>
              <div class="dropdown-option" data-value="Guasayán">Guasayán</div>
              <div class="dropdown-option" data-value="Jiménez">Jiménez</div>
              <div class="dropdown-option" data-value="Juan Felipe Ibarra">Juan Felipe Ibarra</div>
              <div class="dropdown-option" data-value="Loreto">Loreto</div>
              <div class="dropdown-option" data-value="Mitre">Mitre</div>
              <div class="dropdown-option" data-value="Moreno">Moreno</div>
              <div class="dropdown-option" data-value="Ojo de Agua">Ojo de Agua</div>
              <div class="dropdown-option" data-value="Pellegrini">Pellegrini</div>
              <div class="dropdown-option" data-value="Quebrachos">Quebrachos</div>
              <div class="dropdown-option" data-value="Río Hondo">Río Hondo</div>
              <div class="dropdown-option" data-value="Rivadavia">Rivadavia</div>
              <div class="dropdown-option" data-value="Robles">Robles</div>
              <div class="dropdown-option" data-value="Salavina">Salavina</div>
              <div class="dropdown-option" data-value="San Martín">San Martín</div>
              <div class="dropdown-option" data-value="Sarmiento">Sarmiento</div>
              <div class="dropdown-option" data-value="Silípica">Silípica</div>
            </div>
            <input type="hidden" name="departamento_legal" value="">
          </div>
        </div>
      </div>
    </div>
    <!-- Dirección administrativa -->
    <div class="address">
      <div class="label"><span data-i18n="regfull_admin_address">Dirección administrativa <span class="req">*</span></span></div>
      <div class="address_grid">
        <label class="label_span" data-i18n="regfull_street">Calle <span class="req">*</span></label>
        <input type="search" class="span_right">
        <label class="label_span" data-i18n="regfull_number">Altura <span class="req">*</span></label>
        <input type="search" class="">
        <label class="label_span" data-i18n="regfull_postal_code">Código Postal <span class="req">*</span></label>
        <input type="search" class="">
        <label class="label_span" data-i18n="regfull_floor">Piso</label>
        <input type="search" class="">
        <label class="label_span" data-i18n="regfull_apartment">Departamento</label>
        <input type="search" class="">
        <label class="label_span" data-i18n="regfull_locality">Localidad <span class="req">*</span></label>
        <div class="span_right">
          <div class="custom-dropdown">
            <div class="dropdown-selected">
              <span class="selected-text">…</span>
              <span class="dropdown-arrow">▼</span>
            </div>
            <div class="dropdown-options">
              <div class="dropdown-option" data-value="">…</div>
              <div class="dropdown-option" data-value="Santiago del Estero (la capital)">Santiago del Estero (la capital)</div>
              <div class="dropdown-option" data-value="La Banda">La Banda</div>
              <div class="dropdown-option" data-value="Termas de Río Hondo">Termas de Río Hondo</div>
              <div class="dropdown-option" data-value="Añatuya">Añatuya</div>
              <div class="dropdown-option" data-value="Frías">Frías</div>
              <div class="dropdown-option" data-value="Fernández">Fernández</div>
              <div class="dropdown-option" data-value="Clodomira">Clodomira</div>
              <div class="dropdown-option" data-value="Suncho">Suncho</div>
              <div class="dropdown-option" data-value="Corral">Corral</div>
              <div class="dropdown-option" data-value="Loreto">Loreto</div>
              <div class="dropdown-option" data-value="Quimilí">Quimilí</div>
              <div class="dropdown-option" data-value="Beltrán">Beltrán</div>
              <div class="dropdown-option" data-value="Pampa de los Guanacos">Pampa de los Guanacos</div>
              <div class="dropdown-option" data-value="Bandera">Bandera</div>
              <div class="dropdown-option" data-value="Monte Quemado y Sumampa">Monte Quemado y Sumampa</div>
            </div>
            <input type="hidden" name="localidad_admin" value="">
          </div>
        </div>
        <label class="label_span" data-i18n="regfull_department">Departamento <span class="req">*</span></label>
        <div class="span_right">
          <div class="custom-dropdown">
            <div class="dropdown-selected">
              <span class="selected-text">…</span>
              <span class="dropdown-arrow">▼</span>
            </div>
            <div class="dropdown-options">
              <div class="dropdown-option" data-value="">…</div>
              <div class="dropdown-option" data-value="Aguirre">Aguirre</div>
              <div class="dropdown-option" data-value="Alberdi">Alberdi</div>
              <div class="dropdown-option" data-value="Atamisqui">Atamisqui</div>
              <div class="dropdown-option" data-value="Avellaneda">Avellaneda</div>
              <div class="dropdown-option" data-value="Banda">Banda</div>
              <div class="dropdown-option" data-value="Belgrano">Belgrano</div>
              <div class="dropdown-option" data-value="Capital">Capital</div>
              <div class="dropdown-option" data-value="Choya">Choya</div>
              <div class="dropdown-option" data-value="Copo">Copo</div>
              <div class="dropdown-option" data-value="Figueroa">Figueroa</div>
              <div class="dropdown-option" data-value="General Taboada">General Taboada</div>
              <div class="dropdown-option" data-value="Guasayán">Guasayán</div>
              <div class="dropdown-option" data-value="Jiménez">Jiménez</div>
              <div class="dropdown-option" data-value="Juan Felipe Ibarra">Juan Felipe Ibarra</div>
              <div class="dropdown-option" data-value="Loreto">Loreto</div>
              <div class="dropdown-option" data-value="Mitre">Mitre</div>
              <div class="dropdown-option" data-value="Moreno">Moreno</div>
              <div class="dropdown-option" data-value="Ojo de Agua">Ojo de Agua</div>
              <div class="dropdown-option" data-value="Pellegrini">Pellegrini</div>
              <div class="dropdown-option" data-value="Quebrachos">Quebrachos</div>
              <div class="dropdown-option" data-value="Río Hondo">Río Hondo</div>
              <div class="dropdown-option" data-value="Rivadavia">Rivadavia</div>
              <div class="dropdown-option" data-value="Robles">Robles</div>
              <div class="dropdown-option" data-value="Salavina">Salavina</div>
              <div class="dropdown-option" data-value="San Martín">San Martín</div>
              <div class="dropdown-option" data-value="Sarmiento">Sarmiento</div>
              <div class="dropdown-option" data-value="Silípica">Silípica</div>
            </div>
            <input type="hidden" name="departamento_admin" value="">
          </div>
        </div>
      </div>
    </div>
    <!-- Contacto -->
    <div class="contacto_datos">
      <div class="label"><span data-i18n="regfull_contact_person">Persona de Contacto <span class="req">*</span></span></div>
      <div class="contacto_grid">
        <input type="search" class="span_all">
        <label class="label_span" data-i18n="regfull_contact_position">Cargo de Persona de contacto <span class="req">*</span></label>
        <input type="search">
        <label class="label_span" data-i18n="regfull_email">E-mail <span class="req">*</span></label>
        <input type="search" type="email">
        <label class="label_span" data-i18n="regfull_phone">Teléfono <span class="req">*</span></label>
        <div class="phone_inline">
          <input type="search" class="area" data-i18n-placeholder="regfull_area_code" placeholder="Código de área">
          <input type="search" placeholder="">
        </div>
      </div>
    </div>
  </div>
</div>
<div class="datos">
  <div class="datos_header">
    <h1 data-i18n="regfull_section2_title">2. Clasificación de la Empresa</h1>
    <img src="img/icons/clasificacion.png">
  </div>
  <div class="form" novalidate>
    <div class="label"><label data-i18n="regfull_org_type">Tipo de Organización <span class="req">*</span></label></div>
    <div class="field">
      <div class="custom-dropdown">
        <div class="dropdown-selected">
          <span class="selected-text">…</span>
          <span class="dropdown-arrow">▼</span>
        </div>
        <div class="dropdown-options">
          <div class="dropdown-option" data-value="">…</div>
          <div class="dropdown-option" data-value="Empresa grande">Empresa grande</div>
          <div class="dropdown-option" data-value="PyME">PyME</div>
          <div class="dropdown-option" data-value="Cooperativa">Cooperativa</div>
          <div class="dropdown-option" data-value="Emprendimiento">Emprendimiento</div>
          <div class="dropdown-option" data-value="Startup">Startup</div>
          <div class="dropdown-option" data-value="Clúster">Clúster</div>
          <div class="dropdown-option" data-value="Consorcio">Consorcio</div>
          <div class="dropdown-option" data-value="Otros (especificar)">Otros (especificar)</div>
        </div>
        <input type="hidden" name="tipo_organizacion" value="">
      </div>
    </div>
    <div class="label"><label data-i18n="regfull_main_activity">Actividad Principal <span class="req">*</span></label></div>
    <div class="field">
      <div class="custom-dropdown">
        <div class="dropdown-selected">
          <span class="selected-text">…</span>
          <span class="dropdown-arrow">▼</span>
        </div>
        <div class="dropdown-options">
          <div class="dropdown-option" data-value="">…</div>
          <div class="dropdown-option" data-value="Agroindustria">Agroindustria</div>
          <div class="dropdown-option" data-value="Industria manufacturera">Industria manufacturera</div>
          <div class="dropdown-option" data-value="Servicios basados en conocimiento">Servicios basados en conocimiento</div>
          <div class="dropdown-option" data-value="Turismo">Turismo</div>
          <div class="dropdown-option" data-value="Economía cultural/creativa">Economía cultural/creativa</div>
          <div class="dropdown-option" data-value="Otros (especificar)">Otros (especificar)</div>
        </div>
        <input type="hidden" name="actividad_principal" value="">
      </div>
    </div>
  </div>
</div>
<script>

document.addEventListener('DOMContentLoaded', () => {
  const wrapper = document.getElementById('social-wrapper');
  const addBtn  = document.getElementById('add-social');
  function bindRow(row) {
    const dropdown = row.querySelector('.custom-dropdown');
    const hiddenInput = row.querySelector('input.net');
    const other = row.querySelector('input.net-other');
    const final = row.querySelector('input.net-final');
    const rmBtn = row.querySelector('.remove');
    
    // Initialize custom dropdown
    if (dropdown && hiddenInput) {
      initCustomDropdown(dropdown, hiddenInput);
    }
    
    syncFinal();
    
    // Listen for changes in the hidden input (set by custom dropdown)
    hiddenInput.addEventListener('change', () => {
      if (hiddenInput.value === 'Otra') {
        other.hidden = false;
        other.required = true;
        other.focus();
      } else {
        other.hidden = true;
        other.required = false;
        other.value = '';
      }
      syncFinal();
    });
    
    other.addEventListener('input', syncFinal);
    
    if (rmBtn && !rmBtn._bound) {
      rmBtn.addEventListener('click', () => {
        row.remove();
        updateRemoveButtons();
      });
      rmBtn._bound = true;
    }
    
    function syncFinal() {
      if (hiddenInput.value === 'Otra') {
        final.value = other.value.trim();
      } else {
        final.value = hiddenInput.value;
      }
    }
  }
  function updateRemoveButtons() {
    const rows = wrapper.querySelectorAll('.social_row');
    rows.forEach((row, idx) => {
      const rm = row.querySelector('.remove');
      if (!rm) return;
      rm.hidden = (rows.length === 1 || idx === 0);
    });
  }
  addBtn.addEventListener('click', () => {
    const first = wrapper.querySelector('.social_row');
    const clone = first.cloneNode(true);
    const dropdown = clone.querySelector('.custom-dropdown');
    const hiddenInput = clone.querySelector('input.net');
    const other = clone.querySelector('input.net-other');
    const final = clone.querySelector('input.net-final');
    const url = clone.querySelector('input[name="social_url[]"]');
    
    // Reset dropdown to default state
    const selectedText = dropdown.querySelector('.selected-text');
    selectedText.textContent = '…';
    hiddenInput.value = '';
    other.value = '';
    other.hidden = true;
    other.required = false;
    final.value = '';
    if (url) url.value = '';
    
    // Remove selected class from all options
    dropdown.querySelectorAll('.dropdown-option').forEach(opt => {
      opt.classList.remove('selected');
    });
    
    const rm = clone.querySelector('.remove');
    if (rm) rm.hidden = false;
    addBtn.before(clone);
    bindRow(clone);
    updateRemoveButtons();
  });
  const firstRow = wrapper.querySelector('.social_row');
  if (firstRow) bindRow(firstRow);
  updateRemoveButtons();
  
  // Initialize all custom dropdowns (except social media ones which are handled by bindRow)
  document.querySelectorAll('.custom-dropdown').forEach(dropdown => {
    // Skip social media dropdowns (they have input.net class)
    const socialInput = dropdown.querySelector('input.net');
    if (socialInput) {
      return; // Skip this dropdown, it's handled by bindRow
    }
    
    const hiddenInput = dropdown.querySelector('input[type="hidden"]');
    if (hiddenInput) {
      initCustomDropdown(dropdown, hiddenInput);
    }
  });
});
</script>
<!-- EMPRESA -->

<!-- PRODUCTOS -->
<div class="datos">
  <div class="datos_header">
    <h1 data-i18n="regfull_section3_title">3. Información sobre Productos y Servicios</h1>
    <img src="img/icons/sobre_productos.png">
  </div>
  <div class="form" novalidate>
    <div class="label"><span data-i18n="regfull_main_product">Producto o servicio principal <span class="req">*</span></span></div>
    <div class="producto_grid">
      <input type="search" class="span_all">
      <label class="label_span" data-i18n="regfull_tariff_code">Código Arancelario <span class="req">*</span></label>
      <input type="search">
      <label class="label_span" data-i18n="regfull_description">Descripción <span class="req">*</span></label>
      <input type="search" type="email">
      <label class="label_span" data-i18n="regfull_annual_volume">Volumen de Producción Anual <span class="req">*</span></label>
      <div class="anual_inline">
        <div class="field">
          <div class="custom-dropdown">
            <div class="dropdown-selected">
              <span class="selected-text">…</span>
              <span class="dropdown-arrow">▼</span>
            </div>
            <div class="dropdown-options">
              <div class="dropdown-option" data-value="">…</div>
              <div class="dropdown-option" data-value="kg">kg</div>
              <div class="dropdown-option" data-value="toneladas">toneladas</div>
              <div class="dropdown-option" data-value="litros">litros</div>
              <div class="dropdown-option" data-value="unidades">unidades</div>
              <div class="dropdown-option" data-value="horas">horas</div>
            </div>
            <input type="hidden" name="volumen_unidad" value="">
          </div>
        </div>
        <input type="search">
      </div>
      <label class="label_span" data-i18n="regfull_annual_export">Exportación Anual (USD) <span class="req">*</span></label>
      <input type="search" type="email">
      <label class="label_span" data-i18n="regfull_product_photo">Foto del Producto <span class="req">*</span></label>
      <input class="file-ph" data-i18n-placeholder="regfull_upload_file" placeholder="subir archivo (JPG, PNG, PDF) ">
    </div>
    <div class="label"><span data-i18n="regfull_secondary_products">Lista de Productos/Servicios Secundarios</span></div>
    <div class="producto_sec">
      <div class="sec-list"></div>
      <button type="button" class="add_more sec-add" data-i18n="regfull_add_more">agregar más</button>
      <template class="sec-template">
        <div class="sec_item">
          <div class="producto_grid">
            <input type="search" class="span_all">
            <label class="label_span" data-i18n="regfull_tariff_code">Código Arancelario <span class="req">*</span></label>
            <input type="search">
            <label class="label_span" data-i18n="regfull_description">Descripción <span class="req">*</span></label>
            <input type="search">
            <label class="label_span" data-i18n="regfull_annual_volume">Volumen de Producción Anual <span class="req">*</span></label>
            <div class="anual_inline">
              <div class="field">
                <div class="custom-dropdown">
                  <div class="dropdown-selected">
                    <span class="selected-text">…</span>
                    <span class="dropdown-arrow">▼</span>
                  </div>
                  <div class="dropdown-options">
                    <div class="dropdown-option" data-value="">…</div>
                    <div class="dropdown-option" data-value="kg">kg</div>
                    <div class="dropdown-option" data-value="toneladas">toneladas</div>
                    <div class="dropdown-option" data-value="litros">litros</div>
                    <div class="dropdown-option" data-value="unidades">unidades</div>
                    <div class="dropdown-option" data-value="horas">horas</div>
                  </div>
                  <input type="hidden" name="volumen_unidad_sec[]" value="">
                </div>
              </div>
              <input type="search">
            </div>
            <label class="label_span" data-i18n="regfull_annual_export">Exportación Anual (USD) <span class="req">*</span></label>
            <input type="search">
            <label class="label_span" data-i18n="regfull_product_photo">Foto del Producto <span class="req">*</span></label>
            <input class="file-ph" data-i18n-placeholder="regfull_upload_file" placeholder="subir archivo (JPG, PNG, PDF) ">
          </div>
          <div class="sec-actions">
            <button type="button" class="sec-remove" aria-label="Eliminar" data-i18n-aria-label="regfull_remove">×</button>
          </div>
        </div>
      </template>
    </div>
    <!-- Certificaciones -->
    <div class="label"><span data-i18n="regfull_certifications">Certificaciones <span class="req">*</span></span></div>
    <div class="field">
      <input type="search" data-i18n-placeholder="regfull_certifications_placeholder" placeholder="ejemplo: orgánico, comercio justo, ISO, halal, kosher, etc.">
    </div>
    <!-- Exportación Anual (USD) -->
    <div class="label"><span data-i18n="regfull_annual_export_title">Exportación Anual (USD)</span></div>
    <div class="field exp_anual">
      <div class="exp_anual_grid">
        <label class="label_span">2022 <span class="req">*</span></label>
        <input type="search">
        <label class="label_span">2023 <span class="req">*</span></label>
        <input type="search">
        <label class="label_span">2024 <span class="req">*</span></label>
        <input type="search">
      </div>
    </div>
    <!-- Mercados Actuales (Continente) -->
    <div class="label"><span data-i18n="regfull_current_markets">Mercados Actuales (Continente)</span></div>
    <div class="field mercados_act">
      <div class="act-list"></div>
      <button type="button" class="add_more act-add" data-i18n="regfull_add_more">agregar más</button>
      <template class="act-item-tpl">
        <div class="act-row">
          <div class="custom-dropdown">
            <div class="dropdown-selected">
              <span class="selected-text">…</span>
              <span class="dropdown-arrow">▼</span>
            </div>
            <div class="dropdown-options">
              <div class="dropdown-option" data-value="">…</div>
              <div class="dropdown-option" data-value="América del Norte">América del Norte</div>
              <div class="dropdown-option" data-value="América del Sur">América del Sur</div>
              <div class="dropdown-option" data-value="Europa">Europa</div>
              <div class="dropdown-option" data-value="Asia">Asia</div>
              <div class="dropdown-option" data-value="África">África</div>
              <div class="dropdown-option" data-value="Oceanía">Oceanía</div>
            </div>
            <input type="hidden" name="mercados_actuales[]" value="">
          </div>
          <button type="button" class="remove" aria-label="Eliminar" data-i18n-aria-label="regfull_remove">&times;</button>
        </div>
      </template>
    </div>
    <!-- Mercados de Interés (Continente) -->
    <div class="label">
      <span data-i18n="regfull_interest_markets">Mercados de Interés (Continente) <span class="req">*</span></span>
      <div class="sub" data-i18n="regfull_interest_markets_sub">(a donde le gustaría exportar)</div>
    </div>
    <div class="act-row">
      <div class="custom-dropdown">
        <div class="dropdown-selected">
          <span class="selected-text">…</span>
          <span class="dropdown-arrow">▼</span>
        </div>
        <div class="dropdown-options">
          <div class="dropdown-option" data-value="">…</div>
          <div class="dropdown-option" data-value="América del Norte">América del Norte</div>
          <div class="dropdown-option" data-value="América del Sur">América del Sur</div>
          <div class="dropdown-option" data-value="Europa">Europa</div>
          <div class="dropdown-option" data-value="Asia">Asia</div>
          <div class="dropdown-option" data-value="África">África</div>
          <div class="dropdown-option" data-value="Oceanía">Oceanía</div>
        </div>
        <input type="hidden" name="mercados_interes" value="">
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.producto_sec').forEach(sec => {
    const list = sec.querySelector('.sec-list');
    const tpl  = sec.querySelector('.sec-template');
    const add  = sec.querySelector('.sec-add');
    function addCard(){
      const node = tpl.content.firstElementChild.cloneNode(true);
      node.querySelector('.sec-remove').addEventListener('click', () => node.remove());
      
      // Initialize custom dropdown in the new card
      const dropdown = node.querySelector('.custom-dropdown');
      if (dropdown) {
        const hiddenInput = dropdown.querySelector('input[type="hidden"]');
        if (hiddenInput) {
          initCustomDropdown(dropdown, hiddenInput);
        }
      }
      
      list.appendChild(node);
    }
    add.addEventListener('click', addCard);
  });
  document.querySelectorAll('.mercados_act').forEach(box => {
    const list = box.querySelector('.act-list');
    const tpl  = box.querySelector('.act-item-tpl');
    const add  = box.querySelector('.act-add');
    function updateRemoves(){
      const rows = list.querySelectorAll('.act-row');
      rows.forEach((row, i) => {
        const btn = row.querySelector('.remove');
        if (!btn) return;
        btn.hidden = (rows.length === 1 && i === 0);
      });
    }
    function addRow(){
      const node = tpl.content.firstElementChild.cloneNode(true);
      node.querySelector('.remove').addEventListener('click', () => {
        node.remove();
        updateRemoves();
      });
      
      // Initialize custom dropdown in the new row
      const dropdown = node.querySelector('.custom-dropdown');
      if (dropdown) {
        const hiddenInput = dropdown.querySelector('input[type="hidden"]');
        if (hiddenInput) {
          initCustomDropdown(dropdown, hiddenInput);
        }
      }
      
      list.appendChild(node);
      updateRemoves();
    }
    add.addEventListener('click', addRow);
    if (!list.children.length) addRow();
  });
});
</script>
<!-- PRODUCTOS -->

<!-- competitividad -->
<div class="datos">
  <div class="datos_header">
    <h1 data-i18n="regfull_section4_title">4. Competitividad y Diferenciación</h1>
    <img src="img/icons/competitividad.png">
  </div>
  <div class="form" novalidate>
    <div class="compet_blk">
      <div class="label">
        <span data-i18n="regfull_differentiation_factors">Factores de Diferenciación</span>
        <div class="sub" data-i18n="regfull_select_multiple">(puede seleccionar varias opciones)</div>
      </div>
      <div class="field">
        <div class="factors_grid">
          <label class="chk"><input type="checkbox"><span data-i18n="regfull_quality">Calidad</span></label>
          <label class="chk"><input type="checkbox"><span data-i18n="regfull_innovation">Innovación</span></label>
          <label class="chk"><input type="checkbox"><span data-i18n="regfull_territorial_origin">Origen territorial</span></label>
          <label class="chk"><input type="checkbox"><span data-i18n="regfull_traceability">Trazabilidad</span></label>
          <label class="chk"><input type="checkbox"><span data-i18n="regfull_competitive_price">Precio competitivo</span></label>
          <div class="other">
            <label class="chk"><input type="checkbox" class="otros_cb"><span data-i18n="regfull_others">Otros</span></label>
            <input type="search" class="otros_inp" placeholder="" disabled>
          </div>
        </div>
      </div>
      <!-- 2) Historia -->
      <div class="label"><label data-i18n="regfull_company_history">Historia de la Empresa y del Producto <span class="req">*</span></label></div>
      <div class="field"><textarea class="ta" rows="4"></textarea></div>
      <!-- 3) Premios -->
      <div class="label"><label data-i18n="regfull_awards">Premios <span class="req">*</span></label></div>
      <div class="field">
        <div class="yesno_line with_input">
          <label class="yn"><input type="radio" name="premios" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="premios" value="no"><span data-i18n="regfull_no">No</span></label>
          <input type="search" class="detail">
        </div>
      </div>
      <!-- 4) Ferias -->
      <div class="label"><label data-i18n="regfull_fairs">Ferias <span class="req">*</span></label></div>
      <div class="field">
        <div class="yesno_line">
          <label class="yn"><input type="radio" name="ferias" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="ferias" value="no"><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
      <!-- 5) Rondas -->
      <div class="label"><label data-i18n="regfull_rounds">Rondas <span class="req">*</span></label></div>
      <div class="field">
        <div class="yesno_line">
          <label class="yn"><input type="radio" name="rondas" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="rondas" value="no"><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
      <!-- 6) Experiencia Exportadora previa -->
      <div class="label"><label data-i18n="regfull_export_experience">Experiencia Exportadora previa <span class="req">*</span></label></div>
      <div class="field">
        <div class="custom-dropdown">
          <div class="dropdown-selected">
            <span class="selected-text">…</span>
            <span class="dropdown-arrow">▼</span>
          </div>
          <div class="dropdown-options">
            <div class="dropdown-option" data-value="">…</div>
            <div class="dropdown-option" data-value="Sí, ya exportamos regularmente">Sí, ya exportamos regularmente</div>
            <div class="dropdown-option" data-value="Hemos exportado ocasionalmente">Hemos exportado ocasionalmente</div>
            <div class="dropdown-option" data-value="Nunca exportamos">Nunca exportamos</div>
          </div>
          <input type="hidden" name="experiencia_exportadora" value="">
        </div>
      </div>
      <!-- 7) Referencias comerciales -->
      <div class="label"><label data-i18n="regfull_commercial_references">Referencias comerciales <span class="req">*</span></label></div>
      <div class="field"><textarea class="ta" rows="4"></textarea></div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.compet_blk .other').forEach(box => {
    const cb = box.querySelector('.otros_cb');
    const inp = box.querySelector('.otros_inp');
    const sync = () => { inp.disabled = !cb.checked; if (!cb.checked) inp.value = ''; };
    cb.addEventListener('change', sync);
    sync();
  });
});
</script>
<!-- competitividad -->

<!-- visual -->
<div class="datos">
  <div class="datos_header">
    <h1 data-i18n="regfull_section5_title">5. Información Visual y Promocional</h1>
    <img src="img/icons/visual.png">
  </div>
  <div class="form" novalidate>
    <div class="label"><span data-i18n="regfull_company_logo">Adjuntar Logo de la Empresa <span class="req">*</span></span></div>
    <div class="field visual-row">
      <div class="files-list" data-ph="subir archivo (JPG, PNG, PDF)">
        <div class="file-item">
          <input class="file-ph" data-i18n-placeholder="regfull_upload_file" placeholder="subir archivo (JPG, PNG, PDF)">
          <button type="button" class="remove" aria-label="Eliminar" data-i18n-aria-label="regfull_remove" hidden>&times;</button>
        </div>
      </div>
      <button type="button" class="add_more" data-i18n="regfull_add_more">agregar más</button>
    </div>
    <div class="label"><span data-i18n="regfull_process_photos">Adjuntar Fotos de los Procesos/Servicios <span class="req">*</span></span></div>
    <div class="field visual-row">
      <div class="files-list" data-ph="subir archivo (JPG, PNG, PDF)">
        <div class="file-item">
          <input class="file-ph" data-i18n-placeholder="regfull_upload_file" placeholder="subir archivo (JPG, PNG, PDF)">
          <button type="button" class="remove" aria-label="Eliminar" data-i18n-aria-label="regfull_remove" hidden>&times;</button>
        </div>
      </div>
      <button type="button" class="add_more" data-i18n="regfull_add_more">agregar más</button>
    </div>
    <div class="label"><span data-i18n="regfull_digital_catalog">Adjuntar Catálogo Digital (si existe)</span></div>
    <div class="field visual-row">
      <div class="files-list" data-ph="subir archivo (JPG, PNG, PDF)">
        <div class="file-item">
          <input class="file-ph" data-i18n-placeholder="regfull_upload_file" placeholder="subir archivo (JPG, PNG, PDF)">
          <button type="button" class="remove" aria-label="Eliminar" data-i18n-aria-label="regfull_remove" hidden>&times;</button>
        </div>
      </div>
      <button type="button" class="add_more" data-i18n="regfull_add_more">agregar más</button>
    </div>
    <div class="label"><span data-i18n="regfull_institutional_video">Adjuntar Video Institucional (si existe)</span></div>
    <div class="field visual-row">
      <div class="files-list" data-ph="subir archivo (MP4, MKV, AVI)">
        <div class="file-item">
          <input class="file-ph" data-i18n-placeholder="regfull_upload_video" placeholder="subir archivo (MP4, MKV, AVI)">
          <button type="button" class="remove" aria-label="Eliminar" data-i18n-aria-label="regfull_remove" hidden>&times;</button>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.visual-row').forEach(row => {
    const list   = row.querySelector('.files-list');
    const addBtn = row.querySelector('.add_more');
    const ph     = list?.getAttribute('data-ph') || '';
    function updateRemoveButtons(){
      const items = list.querySelectorAll('.file-item');
      items.forEach((it, i) => {
        const rm = it.querySelector('.remove');
        if (!rm) return;
        rm.hidden = (i === 0);
      });
    }
    function bindRemove(btn, item){
      btn.addEventListener('click', () => {
        item.remove();
        updateRemoveButtons();
      });
    }
    if (addBtn){
      addBtn.addEventListener('click', () => {
        const item = document.createElement('div');
        item.className = 'file-item';
        const input = document.createElement('input');
        input.className = 'file-ph';
        input.placeholder = ph;
        const rm = document.createElement('button');
        rm.type = 'button';
        rm.className = 'remove';
        rm.setAttribute('aria-label','Eliminar');
        rm.textContent = '×';
        item.append(input, rm);
        list.appendChild(item);
        bindRemove(rm, item);
        updateRemoveButtons();
      });
    }
    list.querySelectorAll('.file-item .remove').forEach((btn) => {
      bindRemove(btn, btn.closest('.file-item'));
    });
    updateRemoveButtons();
  });
});
</script>
<!-- visual -->

<!-- logistica -->
<div class="datos">
  <div class="datos_header">
    <h1 data-i18n="regfull_section6_title">6. Logística y Distribución</h1>
    <img src="img/icons/logistica.png">
  </div>
  <div class="form" novalidate>
    <div class="label"><span data-i18n="regfull_export_capacity">Capacidad de Exportación Inmediata <span class="req">*</span></span></div>
    <div class="field logi-right">
      <div class="cap-row">
        <div class="yn-line">
          <label class="yn"><input type="radio" name="capacidad_exportacion" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="capacidad_exportacion" value="no"><span data-i18n="regfull_no">No</span></label>
        </div>
        <label class="lbl plazo-lbl" data-i18n="regfull_estimated_term">Plazo estimado <span class="req">*</span></label>
        <input class="fld plazo" data-i18n-placeholder="regfull_months" placeholder="meses">
      </div>
    </div>
    <div class="label"><span data-i18n="regfull_logistics_infrastructure">Infraestructura Logística Disponible <span class="req">*</span></span></div>
    <div class="field">
      <input type="search" data-i18n-placeholder="regfull_logistics_placeholder" placeholder="ejemplo: frigoríficos, transporte propio, alianzas logísticas, etc.">
    </div>
    <div class="label"><span data-i18n="regfull_ports_airports">Puertos/Aeropuertos de Salida habituales o posibles <span class="req">*</span></span></div>
    <div class="field"><textarea class="ta" rows="4"></textarea></div>
  </div>
</div>
<script>
// Radio button logic is now handled by global functions
</script>
<!-- logistica -->

<!-- expectivas -->
<div class="datos">
  <div class="datos_header">
    <h1 data-i18n="regfull_section7_title">7. Necesidades y Expectativas</h1>
    <img src="img/icons/expectivas.png">
  </div>
  <div class="form" novalidate>
    <div class="needs-blk">
      <div class="label">
        <span data-i18n="regfull_export_needs">Principales Necesidades para mejorar capacidad exportadora <span class="req">*</span></span>
      </div>
      <div class="field">
        <div class="needs-grid">
          <label class="chk"><input type="checkbox"><span data-i18n="regfull_training">Capacitación</span></label>
          <label class="chk"><input type="checkbox"><span data-i18n="regfull_fair_access">Acceso a ferias</span></label>
          <label class="chk"><input type="checkbox"><span data-i18n="regfull_certifications_need">Certificaciones</span></label>
          <label class="chk"><input type="checkbox"><span data-i18n="regfull_financing">Financiamiento</span></label>
          <label class="chk"><input type="checkbox"><span data-i18n="regfull_commercial_partners">Socios comerciales</span></label>
          <div class="other">
            <label class="chk"><input type="checkbox" class="otros-cb"><span data-i18n="regfull_others">Otros</span></label>
            <input class="fld otros-inp" type="text" placeholder="" disabled>
          </div>
        </div>
      </div>
      <div class="label">
        <label data-i18n="regfull_interest_participate">Interés en Participar de Misiones Comerciales/Ferias Internacionales <span class="req">*</span></label>
      </div>
      <div class="field">
        <div class="yn-line">
          <label class="yn"><input type="radio" name="interes_participar" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="interes_participar" value="no"><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
      <div class="label">
        <label data-i18n="regfull_training_availability">Disponibilidad para Capacitaciones y Asistencia Técnica <span class="req">*</span></label>
      </div>
      <div class="field">
        <div class="yn-line">
          <label class="yn"><input type="radio" name="disponibilidad_capacitaciones" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="disponibilidad_capacitaciones" value="no"><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.needs-blk .other').forEach(box => {
    const cb = box.querySelector('.otros-cb');
    const inp = box.querySelector('.otros-inp');
    const sync = () => { inp.disabled = !cb.checked; if (!cb.checked) inp.value=''; };
    cb.addEventListener('change', sync);
    sync();
  });
});
</script>
<!-- expectivas -->

<!-- validacion -->
<div class="datos">
  <div class="datos_header">
    <h1 data-i18n="regfull_section8_title">8. Validación y Consentimiento</h1>
    <img src="img/icons/validacion.png">
  </div>
  <div class="form" novalidate>
    <div class="consent-blk">
      <div class="consent-row">
        <div class="consent-text" data-i18n="regfull_authorization_platform">
          Autorización para Difundir la Información Cargada en la Plataforma Provincial <span class="req">*</span>
        </div>
        <div class="yn-line">
          <label class="yn"><input type="radio" name="autorizacion_difundir" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="autorizacion_difundir" value="no"><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
      <div class="consent-row">
        <div class="consent-text" data-i18n="regfull_authorization_publication">
          Autorizo la Publicación de mi Información para Promoción Exportadora <span class="req">*</span>
        </div>
        <div class="yn-line">
          <label class="yn"><input type="radio" name="autorizacion_publicacion" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="autorizacion_publicacion" value="no"><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
      <div class="consent-row">
        <div class="consent-text" data-i18n="regfull_accept_contact">
          Acepto ser Contactado por Organismos de Promoción y Compradores Internacionales <span class="req">*</span>
        </div>
        <div class="yn-line">
          <label class="yn"><input type="radio" name="acepto_contacto" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="acepto_contacto" value="no"><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Submit Button -->
<div style="text-align: center; margin: 40px 0;">
  <button type="button" class="btn btn-save-register" id="btnSaveRegister" data-i18n="regfull_save_register">Guardar y registrarse</button>
</div>

<script>
// Radio button logic is now handled by global functions
</script>

<!-- Global function for custom dropdowns -->
<script>
function initCustomDropdown(dropdown, hiddenInput) {
  const selected = dropdown.querySelector('.dropdown-selected');
  const selectedText = dropdown.querySelector('.selected-text');
  const options = dropdown.querySelectorAll('.dropdown-option');
  
  // Handle dropdown toggle
  if (selected) {
    selected.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdown.classList.toggle('open');
    });
  }
  
  // Handle option selection
  options.forEach(option => {
    option.addEventListener('click', (e) => {
      e.stopPropagation();
      const value = option.dataset.value;
      const text = option.textContent;
      
      // Update selected text
      selectedText.textContent = text;
      
      // Update hidden input
      hiddenInput.value = value;
      hiddenInput.dispatchEvent(new Event('change'));
      
      // Update visual selection
      options.forEach(opt => opt.classList.remove('selected'));
      option.classList.add('selected');
      
      // Close dropdown
      dropdown.classList.remove('open');
    });
  });
  
  // Close dropdown when clicking outside
  document.addEventListener('click', (e) => {
    if (!dropdown.contains(e.target)) {
      dropdown.classList.remove('open');
    }
  });
}

// Global function to initialize radio button groups
function initRadioGroups() {
  // Get all radio button groups by name
  const radioGroups = {};
  document.querySelectorAll('input[type="radio"]').forEach(radio => {
    const name = radio.name;
    if (!radioGroups[name]) {
      radioGroups[name] = [];
    }
    radioGroups[name].push(radio);
  });
  
  // Add event listeners to each group
  Object.keys(radioGroups).forEach(groupName => {
    const radios = radioGroups[groupName];
    radios.forEach(radio => {
      radio.addEventListener('change', function() {
        if (this.checked) {
          // Uncheck all other radios in the same group
          radios.forEach(r => {
            if (r !== this) {
              r.checked = false;
            }
          });
        }
      });
    });
  });
}

// Initialize radio groups when DOM is loaded
document.addEventListener('DOMContentLoaded', initRadioGroups);
</script>
<script src="js/i18n.js?v=1.0.2"></script>
<script>
function toggleRegfullLangMenu() {
  const menu = document.getElementById('regfull_lang_menu');
  menu.classList.toggle('hidden');
}
document.addEventListener('DOMContentLoaded', () => {
  initLang('regfull');
  // Обновляем ID для current-lang на странице regfull
  const currentLangEl = document.getElementById('regfull-current-lang');
  if (currentLangEl) {
    const storedLang = localStorage.getItem('lang') || 'es';
    currentLangEl.textContent = storedLang.toUpperCase();
  }
});
// Закрытие меню по клику вне
document.addEventListener('click', function (e) {
  const langBox = document.querySelector('.regfull-lang');
  const menu = document.getElementById('regfull_lang_menu');
  if (langBox && menu && !langBox.contains(e.target)) {
    menu.classList.add('hidden');
  }
});
// Переопределяем setLang для обновления regfull-current-lang
const originalSetLang = window.setLang;
if (originalSetLang) {
  window.setLang = async function(page, lang) {
    await originalSetLang(page, lang);
    const regfullCurrentLang = document.getElementById('regfull-current-lang');
    if (regfullCurrentLang) {
      regfullCurrentLang.textContent = lang.toUpperCase();
    }
  };
}
</script>
<!-- validacion -->