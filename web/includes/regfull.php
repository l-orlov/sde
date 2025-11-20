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
    <div class="label"><label for="name" data-i18n="regfull_company_name">Nombre de la Empresa/Emprendimiento <span class="req">*</span></label></div>
    <div class="field"><input type="search" id="name" name="name" required></div>
    <!-- CUIT -->
    <div class="label"><label for="tax_id" data-i18n="regfull_cuit">CUIT / Identificación Fiscal <span class="req">*</span></label></div>
    <div class="field"><input type="search" id="tax_id" name="tax_id" data-i18n-placeholder="regfull_cuit_placeholder" placeholder="XX-XXXXXXXX-X" required></div>
    <!-- Razón social -->
    <div class="label"><label for="legal_name" data-i18n="regfull_razon_social">Razón social <span class="req">*</span></label></div>
    <div class="field"><input type="search" id="legal_name" name="legal_name" required></div>
    <!-- Fecha de inicio -->
    <div class="label"><label for="start_date" data-i18n="regfull_start_date">Fecha de Inicio de Actividad <span class="req">*</span></label></div>
    <div class="field"><input type="search" id="start_date" name="start_date" data-i18n-placeholder="regfull_date_placeholder" placeholder="dd/mm/aaaa" inputmode="numeric" required></div>
    <!-- Página web -->
    <div class="label"><label for="website" data-i18n="regfull_website">Página web (si aplica)</label></div>
    <div class="field"><input type="search" id="website" name="website" type="url" data-i18n-placeholder="regfull_website_placeholder" placeholder="http://…"></div>
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
          <input type="hidden" class="net" name="social_network_type[]" value="">
        </div>
        <input class="net-other" type="text" data-i18n-placeholder="regfull_social_enter_network" placeholder="Ingresa la red…" hidden>
        <input class="net-final" type="hidden" name="social_network_type[]">
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
        <input type="search" name="street_legal" class="span_right">
        <label class="label_span" data-i18n="regfull_number">Altura <span class="req">*</span></label>
        <input type="search" name="street_number_legal" class="">
        <label class="label_span" data-i18n="regfull_postal_code">Código Postal <span class="req">*</span></label>
        <input type="search" name="postal_code_legal" class="">
        <label class="label_span" data-i18n="regfull_floor">Piso</label>
        <input type="search" name="floor_legal" class="">
        <label class="label_span" data-i18n="regfull_apartment">Departamento</label>
        <input type="search" name="apartment_legal" class="">
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
            <input type="hidden" name="locality_legal" value="">
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
            <input type="hidden" name="department_legal" value="">
          </div>
        </div>
      </div>
    </div>
    <!-- Dirección administrativa -->
    <div class="address">
      <div class="label"><span data-i18n="regfull_admin_address">Dirección administrativa <span class="req">*</span></span></div>
      <div class="address_grid">
        <label class="label_span" data-i18n="regfull_street">Calle <span class="req">*</span></label>
        <input type="search" name="street_admin" class="span_right">
        <label class="label_span" data-i18n="regfull_number">Altura <span class="req">*</span></label>
        <input type="search" name="street_number_admin" class="">
        <label class="label_span" data-i18n="regfull_postal_code">Código Postal <span class="req">*</span></label>
        <input type="search" name="postal_code_admin" class="">
        <label class="label_span" data-i18n="regfull_floor">Piso</label>
        <input type="search" name="floor_admin" class="">
        <label class="label_span" data-i18n="regfull_apartment">Departamento</label>
        <input type="search" name="apartment_admin" class="">
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
            <input type="hidden" name="locality_admin" value="">
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
            <input type="hidden" name="department_admin" value="">
          </div>
        </div>
      </div>
    </div>
    <!-- Contacto -->
    <div class="contacto_datos">
      <div class="label"><span data-i18n="regfull_contact_person">Persona de Contacto <span class="req">*</span></span></div>
      <div class="contacto_grid">
        <input type="search" name="contact_person" class="span_all">
        <label class="label_span" data-i18n="regfull_contact_position">Cargo de Persona de contacto <span class="req">*</span></label>
        <input type="search" name="contact_position">
        <label class="label_span" data-i18n="regfull_email">E-mail <span class="req">*</span></label>
        <input type="email" name="contact_email">
        <label class="label_span" data-i18n="regfull_phone">Teléfono <span class="req">*</span></label>
        <div class="phone_inline">
          <input type="search" name="contact_area_code" class="area" data-i18n-placeholder="regfull_area_code" placeholder="Código de área">
          <input type="search" name="contact_phone" placeholder="">
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
        <input type="hidden" name="organization_type" value="">
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
        <input type="hidden" name="main_activity" value="">
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
      <input type="search" name="main_product" class="span_all">
      <label class="label_span" data-i18n="regfull_tariff_code">Código Arancelario <span class="req">*</span></label>
      <input type="search" name="tariff_code">
      <label class="label_span" data-i18n="regfull_description">Descripción <span class="req">*</span></label>
      <input type="search" name="product_description">
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
            <input type="hidden" name="volume_unit" value="">
          </div>
        </div>
        <input type="search" name="volume_amount">
      </div>
      <label class="label_span" data-i18n="regfull_annual_export">Exportación Anual (USD) <span class="req">*</span></label>
      <input type="search" name="annual_export">
      <label class="label_span" data-i18n="regfull_product_photo">Foto del Producto <span class="req">*</span></label>
      <input type="file" class="file-ph" name="product_photo" accept="image/jpeg,image/png,application/pdf" data-i18n-placeholder="regfull_upload_file" placeholder="subir archivo (JPG, PNG, PDF) ">
    </div>
    <div class="label"><span data-i18n="regfull_secondary_products">Lista de Productos/Servicios Secundarios</span></div>
    <div class="producto_sec">
      <div class="sec-list"></div>
      <button type="button" class="add_more sec-add" data-i18n="regfull_add_more">agregar más</button>
      <template class="sec-template">
        <div class="sec_item">
          <div class="producto_grid">
            <input type="search" name="secondary_products[]" class="span_all">
            <label class="label_span" data-i18n="regfull_tariff_code">Código Arancelario <span class="req">*</span></label>
            <input type="search" name="tariff_code_sec[]">
            <label class="label_span" data-i18n="regfull_description">Descripción <span class="req">*</span></label>
            <input type="search" name="product_description_sec[]">
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
                  <input type="hidden" name="volume_unit_sec[]" value="">
                </div>
              </div>
              <input type="search" name="volume_amount_sec[]">
            </div>
            <label class="label_span" data-i18n="regfull_annual_export">Exportación Anual (USD) <span class="req">*</span></label>
            <input type="search" name="annual_export_sec[]">
            <label class="label_span" data-i18n="regfull_product_photo">Foto del Producto <span class="req">*</span></label>
            <input type="file" class="file-ph" name="product_photo_sec[]" accept="image/jpeg,image/png,application/pdf" data-i18n-placeholder="regfull_upload_file" placeholder="subir archivo (JPG, PNG, PDF) ">
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
      <input type="search" name="certifications" data-i18n-placeholder="regfull_certifications_placeholder" placeholder="ejemplo: orgánico, comercio justo, ISO, halal, kosher, etc.">
    </div>
    <!-- Exportación Anual (USD) -->
    <div class="label"><span data-i18n="regfull_annual_export_title">Exportación Anual (USD)</span></div>
    <div class="field exp_anual">
      <div class="exp_anual_grid">
        <label class="label_span">2022 <span class="req">*</span></label>
        <input type="search" name="export_2022">
        <label class="label_span">2023 <span class="req">*</span></label>
        <input type="search" name="export_2023">
        <label class="label_span">2024 <span class="req">*</span></label>
        <input type="search" name="export_2024">
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
            <input type="hidden" name="current_markets[]" value="">
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
        <input type="hidden" name="target_markets" value="">
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
            <input type="search" name="other_differentiation" class="otros_inp" placeholder="" disabled>
          </div>
        </div>
      </div>
      <!-- 2) Historia -->
      <div class="label"><label data-i18n="regfull_company_history">Historia de la Empresa y del Producto <span class="req">*</span></label></div>
      <div class="field"><textarea name="company_history" class="ta" rows="4"></textarea></div>
      <!-- 3) Premios -->
      <div class="label"><label data-i18n="regfull_awards">Premios <span class="req">*</span></label></div>
      <div class="field">
        <div class="yesno_line with_input">
          <label class="yn"><input type="radio" name="awards" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="awards" value="no"><span data-i18n="regfull_no">No</span></label>
          <input type="search" name="awards_detail" class="detail">
        </div>
      </div>
      <!-- 4) Ferias -->
      <div class="label"><label data-i18n="regfull_fairs">Ferias <span class="req">*</span></label></div>
      <div class="field">
        <div class="yesno_line">
          <label class="yn"><input type="radio" name="fairs" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="fairs" value="no"><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
      <!-- 5) Rondas -->
      <div class="label"><label data-i18n="regfull_rounds">Rondas <span class="req">*</span></label></div>
      <div class="field">
        <div class="yesno_line">
          <label class="yn"><input type="radio" name="rounds" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="rounds" value="no"><span data-i18n="regfull_no">No</span></label>
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
          <input type="hidden" name="export_experience" value="">
        </div>
      </div>
      <!-- 7) Referencias comerciales -->
      <div class="label"><label data-i18n="regfull_commercial_references">Referencias comerciales <span class="req">*</span></label></div>
      <div class="field"><textarea name="commercial_references" class="ta" rows="4"></textarea></div>
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
      <div class="files-list" data-ph="subir archivo (JPG, PNG, PDF)" data-accept="image/jpeg,image/png,application/pdf" data-name="company_logo[]">
        <div class="file-item">
          <input type="file" class="file-ph" name="company_logo[]" accept="image/jpeg,image/png,application/pdf" data-i18n-placeholder="regfull_upload_file" placeholder="subir archivo (JPG, PNG, PDF)">
          <button type="button" class="remove" aria-label="Eliminar" data-i18n-aria-label="regfull_remove" hidden>&times;</button>
        </div>
      </div>
      <button type="button" class="add_more" data-i18n="regfull_add_more">agregar más</button>
    </div>
    <div class="label"><span data-i18n="regfull_process_photos">Adjuntar Fotos de los Procesos/Servicios <span class="req">*</span></span></div>
    <div class="field visual-row">
      <div class="files-list" data-ph="subir archivo (JPG, PNG, PDF)" data-accept="image/jpeg,image/png,application/pdf" data-name="process_photos[]">
        <div class="file-item">
          <input type="file" class="file-ph" name="process_photos[]" accept="image/jpeg,image/png,application/pdf" data-i18n-placeholder="regfull_upload_file" placeholder="subir archivo (JPG, PNG, PDF)">
          <button type="button" class="remove" aria-label="Eliminar" data-i18n-aria-label="regfull_remove" hidden>&times;</button>
        </div>
      </div>
      <button type="button" class="add_more" data-i18n="regfull_add_more">agregar más</button>
    </div>
    <div class="label"><span data-i18n="regfull_digital_catalog">Adjuntar Catálogo Digital (si existe)</span></div>
    <div class="field visual-row">
      <div class="files-list" data-ph="subir archivo (JPG, PNG, PDF)" data-accept="image/jpeg,image/png,application/pdf" data-name="digital_catalog[]">
        <div class="file-item">
          <input type="file" class="file-ph" name="digital_catalog[]" accept="image/jpeg,image/png,application/pdf" data-i18n-placeholder="regfull_upload_file" placeholder="subir archivo (JPG, PNG, PDF)">
          <button type="button" class="remove" aria-label="Eliminar" data-i18n-aria-label="regfull_remove" hidden>&times;</button>
        </div>
      </div>
      <button type="button" class="add_more" data-i18n="regfull_add_more">agregar más</button>
    </div>
    <div class="label"><span data-i18n="regfull_institutional_video">Adjuntar Video Institucional (si existe)</span></div>
    <div class="field visual-row">
      <div class="files-list" data-ph="subir archivo (MP4, MKV, AVI)" data-accept="video/mp4,video/x-matroska,video/x-msvideo" data-name="institutional_video">
        <div class="file-item">
          <input type="file" class="file-ph" name="institutional_video" accept="video/mp4,video/x-matroska,video/x-msvideo" data-i18n-placeholder="regfull_upload_video" placeholder="subir archivo (MP4, MKV, AVI)">
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
        input.type = 'file';
        input.className = 'file-ph';
        input.placeholder = ph;
        // Определяем accept в зависимости от типа файлов
        const acceptAttr = list.getAttribute('data-accept') || 'image/jpeg,image/png,application/pdf';
        input.accept = acceptAttr;
        // Определяем name в зависимости от родительского контейнера
        const nameAttr = list.getAttribute('data-name') || 'file[]';
        input.name = nameAttr;
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
          <label class="yn"><input type="radio" name="export_capacity" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="export_capacity" value="no"><span data-i18n="regfull_no">No</span></label>
        </div>
        <label class="lbl plazo-lbl" data-i18n="regfull_estimated_term">Plazo estimado <span class="req">*</span></label>
        <input name="estimated_term" class="fld plazo" data-i18n-placeholder="regfull_months" placeholder="meses">
      </div>
    </div>
    <div class="label"><span data-i18n="regfull_logistics_infrastructure">Infraestructura Logística Disponible <span class="req">*</span></span></div>
    <div class="field">
      <input type="search" name="logistics_infrastructure" data-i18n-placeholder="regfull_logistics_placeholder" placeholder="ejemplo: frigoríficos, transporte propio, alianzas logísticas, etc.">
    </div>
    <div class="label"><span data-i18n="regfull_ports_airports">Puertos/Aeropuertos de Salida habituales o posibles <span class="req">*</span></span></div>
    <div class="field"><textarea name="ports_airports" class="ta" rows="4"></textarea></div>
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
            <input name="other_needs" class="fld otros-inp" type="text" placeholder="" disabled>
          </div>
        </div>
      </div>
      <div class="label">
        <label data-i18n="regfull_interest_participate">Interés en Participar de Misiones Comerciales/Ferias Internacionales <span class="req">*</span></label>
      </div>
      <div class="field">
        <div class="yn-line">
          <label class="yn"><input type="radio" name="interest_participate" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="interest_participate" value="no"><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
      <div class="label">
        <label data-i18n="regfull_training_availability">Disponibilidad para Capacitaciones y Asistencia Técnica <span class="req">*</span></label>
      </div>
      <div class="field">
        <div class="yn-line">
          <label class="yn"><input type="radio" name="training_availability" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="training_availability" value="no"><span data-i18n="regfull_no">No</span></label>
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
          <label class="yn"><input type="radio" name="authorization_publish" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="authorization_publish" value="no"><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
      <div class="consent-row">
        <div class="consent-text" data-i18n="regfull_authorization_publication">
          Autorizo la Publicación de mi Información para Promoción Exportadora <span class="req">*</span>
        </div>
        <div class="yn-line">
          <label class="yn"><input type="radio" name="authorization_publication" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="authorization_publication" value="no"><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
      <div class="consent-row">
        <div class="consent-text" data-i18n="regfull_accept_contact">
          Acepto ser Contactado por Organismos de Promoción y Compradores Internacionales <span class="req">*</span>
        </div>
        <div class="yn-line">
          <label class="yn"><input type="radio" name="accept_contact" value="si"><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="accept_contact" value="no"><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Submit Button -->
<div style="text-align: center; margin: 40px 0;">
  <button type="button" class="btn btn-save-register" id="btnSaveRegister" data-i18n="regfull_save_register">Guardar y registrarse</button>
  <div id="regfull_message" style="margin-top: 15px; display: none;"></div>
  <div style="margin-top: 10px;">
    <button type="button" id="btnFillTestData" style="padding: 5px 10px; font-size: 12px; background: #2196F3; color: white; border: none; border-radius: 3px; cursor: pointer;">🧪 Заполнить тестовыми данными</button>
  </div>
</div>

<script>
// Обработчик отправки формы
document.addEventListener('DOMContentLoaded', () => {
  const btnSave = document.getElementById('btnSaveRegister');
  const msgEl = document.getElementById('regfull_message');
  
  if (btnSave) {
    btnSave.addEventListener('click', async function() {
      const STORAGE_KEY = 'regfull_form_data';
      const formData = {};
      
      document.querySelectorAll('input[type="text"], input[type="search"], input[type="email"], input[type="url"], textarea').forEach(field => {
        if (field.type !== 'file' && !field.hidden && field.name && field.value.trim()) {
          formData[field.name] = field.value.trim();
        }
      });
      
      document.querySelectorAll('input[type="hidden"]').forEach(field => {
        if (field.name) {
          formData[field.name] = field.value;
          const dropdown = field.closest('.custom-dropdown');
          if (dropdown) {
            const selectedText = dropdown.querySelector('.selected-text');
            if (selectedText && selectedText.textContent && selectedText.textContent !== '…') {
              formData[field.name + '_text'] = selectedText.textContent;
            }
          }
        }
      });
      
      document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        if (radio.name) {
          formData[radio.name] = radio.value;
        }
      });
      
      const checkboxValues = {};
      document.querySelectorAll('input[type="checkbox"]:checked').forEach(checkbox => {
        if (checkbox.name) {
          if (!checkboxValues[checkbox.name]) {
            checkboxValues[checkbox.name] = [];
          }
          checkboxValues[checkbox.name].push(checkbox.value || 'checked');
        }
      });
      Object.assign(formData, checkboxValues);
      
      const secList = document.querySelector('.sec-list');
      if (secList && secList.children.length > 0) {
        formData['_sec_items_count'] = secList.children.length;
        secList.querySelectorAll('.sec_item').forEach((item, idx) => {
          item.querySelectorAll('input[type="text"], input[type="search"]').forEach((input, inputIdx) => {
            if (input.value) {
              formData[`_sec_${idx}_input_${inputIdx}`] = input.value;
            }
          });
          const dropdown = item.querySelector('.custom-dropdown input[type="hidden"]');
          if (dropdown && dropdown.value) {
            formData[`_sec_${idx}_dropdown`] = dropdown.value;
          }
        });
      }
      
      const actList = document.querySelector('.act-list');
      if (actList && actList.children.length > 0) {
        formData['_act_items_count'] = actList.children.length;
        actList.querySelectorAll('.act-row').forEach((row, idx) => {
          const dropdown = row.querySelector('.custom-dropdown input[type="hidden"]');
          if (dropdown && dropdown.value) {
            formData[`_act_${idx}_value`] = dropdown.value;
          }
        });
      }
      
      const socialRows = document.querySelectorAll('.social_row');
      if (socialRows.length > 0) {
        formData['_social_rows_count'] = socialRows.length;
        socialRows.forEach((row, idx) => {
          const tipo = row.querySelector('input.net')?.value || '';
          const url = row.querySelector('input[name="social_url[]"]')?.value || '';
          const other = row.querySelector('input.net-other')?.value || '';
          formData[`_social_${idx}_tipo`] = tipo;
          formData[`_social_${idx}_url`] = url;
          formData[`_social_${idx}_other`] = other;
        });
      }
      
      localStorage.setItem(STORAGE_KEY, JSON.stringify(formData));
      
      const errors = [];
      
      const checkRequired = (name, label) => {
        const field = document.querySelector(`[name="${name}"]`);
        if (!field || !field.value || !field.value.trim()) {
          errors.push(label);
          if (field) {
            field.style.borderColor = '#f44336';
            field.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
          return false;
        }
        if (field) field.style.borderColor = '';
        return true;
      };
      
      const checkDropdown = (name, label) => {
        const field = document.querySelector(`[name="${name}"]`);
        if (!field || !field.value || field.value === '' || field.value === '…') {
          errors.push(label);
          const dropdown = field?.closest('.custom-dropdown');
          if (dropdown) {
            dropdown.style.boxShadow = '0 0 0 2px #f44336';
            dropdown.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
          return false;
        }
        const dropdown = field?.closest('.custom-dropdown');
        if (dropdown) dropdown.style.boxShadow = '';
        return true;
      };
      
      checkRequired('name', 'Nombre de la Empresa');
      checkRequired('tax_id', 'CUIT / Identificación Fiscal');
      checkRequired('legal_name', 'Razón social');
      
      const startDateField = document.querySelector('[name="start_date"]');
      if (!startDateField || !startDateField.value || !startDateField.value.trim()) {
        errors.push('Fecha de Inicio de Actividad');
        if (startDateField) {
          startDateField.style.borderColor = '#f44336';
          startDateField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      } else {
        const datePattern = /^\d{2}\/\d{2}\/\d{4}$/;
        if (!datePattern.test(startDateField.value.trim())) {
          errors.push('Fecha de Inicio de Actividad (formato: dd/mm/yyyy)');
          startDateField.style.borderColor = '#f44336';
          startDateField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
          startDateField.style.borderColor = '';
        }
      }
      
      checkRequired('street_legal', 'Calle (Domicilio Legal)');
      checkRequired('street_number_legal', 'Altura (Domicilio Legal)');
      checkRequired('postal_code_legal', 'Código Postal (Domicilio Legal)');
      checkDropdown('locality_legal', 'Localidad (Domicilio Legal)');
      checkDropdown('department_legal', 'Departamento (Domicilio Legal)');
      
      checkRequired('street_admin', 'Calle (Dirección administrativa)');
      checkRequired('street_number_admin', 'Altura (Dirección administrativa)');
      checkRequired('postal_code_admin', 'Código Postal (Dirección administrativa)');
      checkDropdown('locality_admin', 'Localidad (Dirección administrativa)');
      checkDropdown('department_admin', 'Departamento (Dirección administrativa)');
      
      checkRequired('contact_person', 'Persona de Contacto');
      checkRequired('contact_position', 'Cargo de Persona de contacto');
      
      const contactEmail = document.querySelector('[name="contact_email"]');
      if (!contactEmail || !contactEmail.value || !contactEmail.value.trim()) {
        errors.push('E-mail');
        if (contactEmail) {
          contactEmail.style.borderColor = '#f44336';
          contactEmail.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      } else {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(contactEmail.value.trim())) {
          errors.push('E-mail (formato inválido)');
          contactEmail.style.borderColor = '#f44336';
          contactEmail.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
          contactEmail.style.borderColor = '';
        }
      }
      
      const contactAreaCode = document.querySelector('[name="contact_area_code"]');
      const contactPhone = document.querySelector('[name="contact_phone"]');
      if (!contactAreaCode || !contactAreaCode.value || !contactAreaCode.value.trim() ||
          !contactPhone || !contactPhone.value || !contactPhone.value.trim()) {
        errors.push('Teléfono (código de área y número)');
        if (contactAreaCode) {
          contactAreaCode.style.borderColor = '#f44336';
        }
        if (contactPhone) {
          contactPhone.style.borderColor = '#f44336';
          contactPhone.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      } else {
        if (contactAreaCode) contactAreaCode.style.borderColor = '';
        if (contactPhone) contactPhone.style.borderColor = '';
      }
      
      checkDropdown('organization_type', 'Tipo de Organización');
      checkDropdown('main_activity', 'Actividad Principal');
      
      checkRequired('main_product', 'Producto o servicio principal');
      checkRequired('tariff_code', 'Código Arancelario');
      checkRequired('product_description', 'Descripción del producto');
      checkDropdown('volume_unit', 'Unidad de Volumen');
      checkRequired('volume_amount', 'Cantidad de Volumen');
      checkRequired('annual_export', 'Exportación Anual (USD)');
      
      const productPhoto = document.querySelector('input[name="product_photo"]');
      if (!productPhoto || !productPhoto.files || productPhoto.files.length === 0) {
        errors.push('Foto del Producto principal');
        if (productPhoto) {
          productPhoto.style.borderColor = '#f44336';
          productPhoto.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      } else if (productPhoto) {
        productPhoto.style.borderColor = '';
      }
      
      checkRequired('certifications', 'Certificaciones');
      checkRequired('export_2022', 'Exportación 2022');
      checkRequired('export_2023', 'Exportación 2023');
      checkRequired('export_2024', 'Exportación 2024');
      checkDropdown('target_markets', 'Mercados de Interés');
      
      checkRequired('company_history', 'Historia de la Empresa');
      const awards = document.querySelector('input[name="awards"]:checked');
      if (!awards) {
        errors.push('Premios');
      }
      const fairs = document.querySelector('input[name="fairs"]:checked');
      if (!fairs) {
        errors.push('Ferias');
      }
      const rounds = document.querySelector('input[name="rounds"]:checked');
      if (!rounds) {
        errors.push('Rondas');
      }
      checkDropdown('export_experience', 'Experiencia Exportadora');
      checkRequired('commercial_references', 'Referencias comerciales');
      
      const companyLogo = document.querySelector('input[name="company_logo[]"]');
      if (!companyLogo || !companyLogo.files || companyLogo.files.length === 0) {
        errors.push('Logo de la Empresa');
        if (companyLogo) {
          companyLogo.style.borderColor = '#f44336';
          companyLogo.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      } else if (companyLogo) {
        companyLogo.style.borderColor = '';
      }
      
      const processPhotos = document.querySelector('input[name="process_photos[]"]');
      if (!processPhotos || !processPhotos.files || processPhotos.files.length === 0) {
        errors.push('Fotos de los Procesos/Servicios');
        if (processPhotos) {
          processPhotos.style.borderColor = '#f44336';
          processPhotos.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      } else if (processPhotos) {
        processPhotos.style.borderColor = '';
      }
      
      const exportCapacity = document.querySelector('input[name="export_capacity"]:checked');
      if (!exportCapacity) {
        errors.push('Capacidad de Exportación Inmediata');
      } else if (exportCapacity.value === 'si') {
        checkRequired('estimated_term', 'Plazo estimado');
      }
      checkRequired('logistics_infrastructure', 'Infraestructura Logística');
      checkRequired('ports_airports', 'Puertos/Aeropuertos');
      
      const interestParticipate = document.querySelector('input[name="interest_participate"]:checked');
      if (!interestParticipate) {
        errors.push('Interés en Participar de Misiones Comerciales');
      }
      const trainingAvailability = document.querySelector('input[name="training_availability"]:checked');
      if (!trainingAvailability) {
        errors.push('Disponibilidad para Capacitaciones');
      }
      
      const authorizationPublish = document.querySelector('input[name="authorization_publish"]:checked');
      if (!authorizationPublish) {
        errors.push('Autorización para Difundir la Información');
      }
      const authorizationPublication = document.querySelector('input[name="authorization_publication"]:checked');
      if (!authorizationPublication) {
        errors.push('Autorización de Publicación');
      }
      const acceptContact = document.querySelector('input[name="accept_contact"]:checked');
      if (!acceptContact) {
        errors.push('Acepto ser Contactado');
      }
      
      if (errors.length > 0) {
        msgEl.className = 'err';
        msgEl.textContent = 'Por favor, complete los campos obligatorios';
        msgEl.style.display = 'block';
        msgEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
      
      document.querySelectorAll('input, textarea, select').forEach(field => {
        field.style.borderColor = '';
      });
      document.querySelectorAll('.custom-dropdown').forEach(dropdown => {
        dropdown.style.boxShadow = '';
      });
      
      let totalSize = 0;
      const maxSize = 50 * 1024 * 1024;
      const fileInputs = document.querySelectorAll('input[type="file"]');
      
      fileInputs.forEach(fileInput => {
        if (fileInput.files && fileInput.files.length > 0) {
          for (let i = 0; i < fileInput.files.length; i++) {
            totalSize += fileInput.files[i].size;
          }
        }
      });
      
      if (totalSize > maxSize) {
        msgEl.className = 'err';
        msgEl.textContent = `El tamaño total de los archivos es demasiado grande (${(totalSize / 1024 / 1024).toFixed(2)} MB). Máximo permitido: ${(maxSize / 1024 / 1024).toFixed(2)} MB`;
        msgEl.style.display = 'block';
        msgEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
      
      const compressImage = (file, maxWidth = 1920, maxHeight = 1080, quality = 0.8) => {
        return new Promise((resolve, reject) => {
          if (!file.type.startsWith('image/') || file.type === 'application/pdf') {
            resolve(file);
            return;
          }
          
          const reader = new FileReader();
          reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
              const canvas = document.createElement('canvas');
              let width = img.width;
              let height = img.height;
              
              if (width > maxWidth || height > maxHeight) {
                const ratio = Math.min(maxWidth / width, maxHeight / height);
                width = width * ratio;
                height = height * ratio;
              }
              
              canvas.width = width;
              canvas.height = height;
              
              const ctx = canvas.getContext('2d');
              ctx.drawImage(img, 0, 0, width, height);
              
              canvas.toBlob((blob) => {
                if (blob) {
                  const compressedFile = new File([blob], file.name, {
                    type: file.type,
                    lastModified: Date.now()
                  });
                  resolve(compressedFile);
                } else {
                  resolve(file);
                }
              }, file.type, quality);
            };
            img.onerror = () => resolve(file);
            img.src = e.target.result;
          };
          reader.onerror = () => resolve(file);
          reader.readAsDataURL(file);
        });
      };
      
      const processVideo = (file) => {
        return new Promise((resolve, reject) => {
          const maxVideoSize = 50 * 1024 * 1024;
          
          if (file.size > maxVideoSize) {
            const sizeMB = (file.size / 1024 / 1024).toFixed(2);
            const confirmMsg = `El video "${file.name}" es muy grande (${sizeMB} MB).\n\n` +
                             `Recomendamos comprimir el video antes de subirlo.\n\n` +
                             `¿Desea continuar de todos modos?`;
            
            if (!confirm(confirmMsg)) {
              reject(new Error(`Video ${file.name} rechazado por el usuario`));
              return;
            }
          }
          
          resolve(file);
        });
      };
      
      btnSave.disabled = true;
      btnSave.textContent = 'Comprimiendo archivos...';
      msgEl.style.display = 'none';
      msgEl.className = '';
      msgEl.textContent = '';
      
      try {
        const formDataToSend = new FormData();
        const fileInputs = document.querySelectorAll('input[type="file"]');
        const filePromises = [];
        
        for (const fileInput of fileInputs) {
          if (fileInput.files && fileInput.files.length > 0) {
            for (let i = 0; i < fileInput.files.length; i++) {
              const file = fileInput.files[i];
              
              if (file.type.startsWith('image/')) {
                filePromises.push(
                  compressImage(file).then(compressedFile => {
                    formDataToSend.append(fileInput.name, compressedFile);
                  })
                );
              } else if (file.type.startsWith('video/')) {
                filePromises.push(
                  processVideo(file).then(processedFile => {
                    formDataToSend.append(fileInput.name, processedFile);
                  }).catch(error => {
                    throw error;
                  })
                );
              } else {
                formDataToSend.append(fileInput.name, file);
              }
            }
          }
        }
        
        try {
          await Promise.all(filePromises);
        } catch (fileError) {
          btnSave.disabled = false;
          btnSave.textContent = btnSave.getAttribute('data-i18n') ? btnSave.textContent : 'Guardar y registrarse';
          msgEl.className = 'err';
          msgEl.textContent = 'Por favor, comprima los archivos de video grandes antes de subirlos.';
          msgEl.style.display = 'block';
          msgEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
          return;
        }
        
        btnSave.textContent = 'Guardando...';
        
        const appendToFormData = (name, value) => {
          formDataToSend.append(name, value);
        };
        
        document.querySelectorAll('input[type="text"], input[type="search"], input[type="email"], input[type="url"], textarea').forEach(field => {
          if (field.type !== 'file' && !field.hidden && field.name && field.value) {
            appendToFormData(field.name, field.value);
          }
        });
        
        document.querySelectorAll('input[type="hidden"]').forEach(field => {
          if (field.name && field.value) {
            appendToFormData(field.name, field.value);
          }
        });
        
        document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
          if (radio.name) {
            formDataToSend.append(radio.name, radio.value);
          }
        });
        
        document.querySelectorAll('input[type="checkbox"]:checked').forEach(checkbox => {
          if (checkbox.name) {
            const value = checkbox.value || 'checked';
            appendToFormData(checkbox.name, value);
          }
        });
        
        const response = await fetch('includes/regfull_js.php', {
          method: 'POST',
          body: formDataToSend
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        let result;
        let responseText = '';
        try {
          responseText = await response.text();
          
          if (!responseText) {
            throw new Error('Empty response from server');
          }
          
          if (responseText.trim().startsWith('<')) {
            throw new Error('Server returned HTML instead of JSON. Check server logs.');
          }
          
          result = JSON.parse(responseText);
        } catch (parseError) {
          throw new Error('Invalid server response. Please try again.');
        }
        
        if (result.ok === 1) {
          msgEl.className = 'success';
          msgEl.textContent = result.res || 'Datos guardados correctamente';
          msgEl.style.display = 'block';
          
          localStorage.removeItem('regfull_form_data');
          
          setTimeout(() => {
            window.location.href = '?page=home';
          }, 2000);
        } else {
          msgEl.className = 'err';
          msgEl.textContent = result.err || 'Error al guardar los datos';
          msgEl.style.display = 'block';
          btnSave.disabled = false;
          btnSave.textContent = btnSave.getAttribute('data-i18n') ? btnSave.textContent : 'Guardar y registrarse';
        }
      } catch (error) {
        msgEl.className = 'err';
        msgEl.textContent = 'Error de conexión. Intente de nuevo.';
        msgEl.style.display = 'block';
        btnSave.disabled = false;
        btnSave.textContent = btnSave.getAttribute('data-i18n') ? btnSave.textContent : 'Guardar y registrarse';
      }
    });
  }
});
</script>

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

<!-- Auto-save form data to localStorage -->
<script>
(function() {
  const STORAGE_KEY = 'regfull_form_data';
  
  function getFieldSelector(field) {
    const path = [];
    let element = field;
    while (element && element !== document.body) {
      let selector = element.tagName.toLowerCase();
      if (element.className) {
        const classes = element.className.split(' ').filter(c => c && !c.startsWith('span_'));
        if (classes.length > 0) {
          selector += '.' + classes[0];
        }
      }
      const siblings = Array.from(element.parentElement?.children || []);
      const index = siblings.indexOf(element);
      if (index > 0) {
        selector += `:nth-of-type(${index + 1})`;
      }
      path.unshift(selector);
      element = element.parentElement;
    }
    return path.join(' > ');
  }
  
  function getFieldKey(field) {
    if (field.name) return field.name;
    if (field.id) return field.id;
    
    const parent = field.closest('.address_grid, .contacto_grid, .producto_grid, .address, .contacto_datos');
    if (parent) {
      const index = Array.from(parent.querySelectorAll('input, textarea')).indexOf(field);
      const parentClass = parent.className.split(' ')[0];
      return `${parentClass}_${index}`;
    }
    
    return 'field_' + Math.random().toString(36).substr(2, 9);
  }
  
  function saveFormData() {
    const formData = {};
    const fieldMap = new Map();
    
    document.querySelectorAll('input[type="text"], input[type="search"], input[type="email"], input[type="url"], textarea').forEach((field, index) => {
      if (field.type !== 'file' && !field.hidden) {
        let key = field.name || field.id;
        
        if (!key) {
          const parent = field.closest('.address_grid, .contacto_grid, .producto_grid, .address, .contacto_datos, .form, .datos');
          if (parent) {
            const parentClass = parent.className.split(' ').find(c => c && !c.includes('datos'));
            const allFields = parent.querySelectorAll('input[type="text"], input[type="search"], input[type="email"], input[type="url"], textarea');
            const fieldIndex = Array.from(allFields).indexOf(field);
            key = `${parentClass || 'field'}_${fieldIndex}`;
          } else {
            key = `field_${index}`;
          }
        }
        
        let finalKey = key;
        let counter = 0;
        while (fieldMap.has(finalKey)) {
          finalKey = `${key}_${counter}`;
          counter++;
        }
        fieldMap.set(finalKey, field);
        
        formData[finalKey] = field.value;
        formData[finalKey + '_sel'] = getFieldSelector(field);
      }
    });
    
    document.querySelectorAll('input[type="hidden"]').forEach(field => {
      if (field.name) {
        formData[field.name] = field.value;
        const dropdown = field.closest('.custom-dropdown');
        if (dropdown) {
          const selectedText = dropdown.querySelector('.selected-text');
          if (selectedText) {
            formData[field.name + '_text'] = selectedText.textContent;
          }
        }
      }
    });
    
    document.querySelectorAll('input[type="checkbox"]').forEach((checkbox, idx) => {
      let key = checkbox.name || checkbox.id;
      if (!key) {
        const parent = checkbox.closest('.factors_grid, .needs-grid, .compet_blk, .needs-blk');
        if (parent) {
          const allCheckboxes = parent.querySelectorAll('input[type="checkbox"]');
          const index = Array.from(allCheckboxes).indexOf(checkbox);
          key = `checkbox_${parent.className.split(' ')[0]}_${index}`;
        } else {
          key = `checkbox_${idx}`;
        }
      }
      
      if (checkbox.name) {
        if (!formData[checkbox.name]) {
          formData[checkbox.name] = [];
        }
        if (checkbox.checked) {
          formData[checkbox.name].push(checkbox.value || 'checked');
        }
      } else {
        formData[key] = checkbox.checked;
        formData[key + '_sel'] = getFieldSelector(checkbox);
      }
    });
    
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
      if (radio.name && radio.checked) {
        formData[radio.name] = radio.value;
      }
    });
    
    document.querySelectorAll('input[type="file"]').forEach((fileInput, idx) => {
      if (fileInput.files && fileInput.files.length > 0) {
        const fileNames = Array.from(fileInput.files).map(f => f.name);
        const key = fileInput.name || fileInput.id || `file_${idx}`;
        formData[key] = fileNames;
        formData[key + '_sel'] = getFieldSelector(fileInput);
      }
    });
    
    const secList = document.querySelector('.sec-list');
    if (secList && secList.children.length > 0) {
      formData['_sec_items_count'] = secList.children.length;
      secList.querySelectorAll('.sec_item').forEach((item, idx) => {
        item.querySelectorAll('input[type="text"], input[type="search"]').forEach((input, inputIdx) => {
          if (input.value) {
            formData[`_sec_${idx}_input_${inputIdx}`] = input.value;
          }
        });
        const dropdown = item.querySelector('.custom-dropdown input[type="hidden"]');
        if (dropdown && dropdown.value) {
          formData[`_sec_${idx}_dropdown`] = dropdown.value;
        }
      });
    }
    
    const actList = document.querySelector('.act-list');
    if (actList && actList.children.length > 0) {
      formData['_act_items_count'] = actList.children.length;
      actList.querySelectorAll('.act-row').forEach((row, idx) => {
        const dropdown = row.querySelector('.custom-dropdown input[type="hidden"]');
        if (dropdown && dropdown.value) {
          formData[`_act_${idx}_value`] = dropdown.value;
        }
      });
    }
    
    const socialRows = document.querySelectorAll('.social_row');
    if (socialRows.length > 0) {
      formData['_social_rows_count'] = socialRows.length;
      socialRows.forEach((row, idx) => {
        const tipo = row.querySelector('input.net')?.value || '';
        const url = row.querySelector('input[name="social_url[]"]')?.value || '';
        const other = row.querySelector('input.net-other')?.value || '';
        formData[`_social_${idx}_tipo`] = tipo;
        formData[`_social_${idx}_url`] = url;
        formData[`_social_${idx}_other`] = other;
      });
    }
    
    localStorage.setItem(STORAGE_KEY, JSON.stringify(formData));
  }
  
  function restoreFormData() {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (!saved) {
      return;
    }
    
    try {
      const formData = JSON.parse(saved);
      let restoredCount = 0;
      
      Object.keys(formData).forEach(key => {
        if (key.startsWith('_') || key.endsWith('_sel') || key.endsWith('_text')) return;
        
        let field = null;
        if (key.includes('[') || key.includes(']')) {
          const fields = document.querySelectorAll(`[name="${key}"]`);
          if (fields.length > 0) {
            field = fields[0];
          }
        } else {
          field = document.querySelector(`[name="${key}"], #${key}`);
        }
        
        if (field && field.type !== 'file' && field.type !== 'checkbox' && field.type !== 'radio' && !field.hidden) {
          const savedValue = formData[key];
          if (savedValue !== undefined && savedValue !== null && savedValue !== '') {
            field.value = savedValue;
            restoredCount++;
          }
        }
      });
      
      document.querySelectorAll('input[type="hidden"]').forEach(field => {
        if (field.name && formData[field.name]) {
          const savedValue = formData[field.name];
          if (savedValue && savedValue !== '' && savedValue !== '…') {
            field.value = savedValue;
          const dropdown = field.closest('.custom-dropdown');
          if (dropdown) {
            const selectedText = dropdown.querySelector('.selected-text');
              if (selectedText) {
            if (formData[field.name + '_text']) {
              selectedText.textContent = formData[field.name + '_text'];
            } else {
                  const option = dropdown.querySelector(`[data-value="${savedValue}"]`);
                  if (option) {
                selectedText.textContent = option.textContent;
                option.classList.add('selected');
              }
            }
              }
              setTimeout(() => {
            field.dispatchEvent(new Event('change', { bubbles: true }));
              }, 100);
          }
          restoredCount++;
          }
        }
      });
      
      document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        if (checkbox.name) {
          const savedValues = formData[checkbox.name];
          if (Array.isArray(savedValues)) {
            const shouldBeChecked = savedValues.includes(checkbox.value || 'checked');
            checkbox.checked = shouldBeChecked;
            if (shouldBeChecked) restoredCount++;
          }
        } else {
          let key = checkbox.id;
          if (!key) {
            const parent = checkbox.closest('.factors_grid, .needs-grid');
            if (parent) {
              const allCheckboxes = parent.querySelectorAll('input[type="checkbox"]');
              const index = Array.from(allCheckboxes).indexOf(checkbox);
              key = `checkbox_${parent.className.split(' ')[0]}_${index}`;
            }
          }
          if (key && formData[key] !== undefined) {
            checkbox.checked = formData[key];
            restoredCount++;
          }
        }
      });
      
      document.querySelectorAll('input[type="radio"]').forEach(radio => {
        if (radio.name && formData[radio.name] === radio.value) {
          radio.checked = true;
          restoredCount++;
        }
      });
      
      document.querySelectorAll('input[type="file"]').forEach((fileInput, idx) => {
        const key = fileInput.name || fileInput.id || `file_${idx}`;
        if (formData[key] && Array.isArray(formData[key])) {
          const fileNames = formData[key].join(', ');
          if (fileNames) {
            const oldHint = fileInput.parentNode.querySelector('small[data-file-hint]');
            if (oldHint) oldHint.remove();
            
            const hint = document.createElement('small');
            hint.setAttribute('data-file-hint', 'true');
            hint.style.display = 'block';
            hint.style.marginTop = '4px';
            hint.style.color = '#666';
            hint.textContent = `Guardado: ${fileNames}`;
            fileInput.parentNode.insertBefore(hint, fileInput.nextSibling);
            restoredCount++;
          }
        }
      });
      
      setTimeout(() => {
        if (formData['_social_rows_count'] && formData['_social_rows_count'] > 1) {
          const wrapper = document.getElementById('social-wrapper');
          const addBtn = document.getElementById('add-social');
          if (wrapper && addBtn) {
            const currentRows = wrapper.querySelectorAll('.social_row').length;
            for (let i = currentRows; i < formData['_social_rows_count']; i++) {
              addBtn.click();
            }
            setTimeout(() => {
              document.querySelectorAll('.social_row').forEach((row, idx) => {
                const tipoKey = `_social_${idx}_tipo`;
                const urlKey = `_social_${idx}_url`;
                const otherKey = `_social_${idx}_other`;
                
                if (formData[tipoKey]) {
                  const hiddenInput = row.querySelector('input.net');
                  if (hiddenInput) {
                    hiddenInput.value = formData[tipoKey];
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                  }
                }
                if (formData[urlKey]) {
                  const urlInput = row.querySelector('input[name="social_url[]"]');
                  if (urlInput) {
                    urlInput.value = formData[urlKey];
                  }
                }
                if (formData[otherKey]) {
                  const otherInput = row.querySelector('input.net-other');
                  if (otherInput) {
                    otherInput.value = formData[otherKey];
                    otherInput.hidden = false;
                  }
                }
              });
            }, 200);
          }
        }
        
        if (formData['_sec_items_count'] && formData['_sec_items_count'] > 0) {
          const secList = document.querySelector('.sec-list');
          const secAdd = document.querySelector('.sec-add');
          if (secList && secAdd) {
            const currentItems = secList.children.length;
            for (let i = currentItems; i < formData['_sec_items_count']; i++) {
              secAdd.click();
            }
            setTimeout(() => {
              secList.querySelectorAll('.sec_item').forEach((item, idx) => {
                item.querySelectorAll('input[type="text"], input[type="search"]').forEach((input, inputIdx) => {
                  const key = `_sec_${idx}_input_${inputIdx}`;
                  if (formData[key]) {
                    input.value = formData[key];
                  }
                });
                const dropdownKey = `_sec_${idx}_dropdown`;
                if (formData[dropdownKey]) {
                  const dropdown = item.querySelector('.custom-dropdown input[type="hidden"]');
                  if (dropdown) {
                    dropdown.value = formData[dropdownKey];
                    const selectedText = dropdown.closest('.custom-dropdown').querySelector('.selected-text');
                    const option = dropdown.closest('.custom-dropdown').querySelector(`[data-value="${formData[dropdownKey]}"]`);
                    if (selectedText && option) {
                      selectedText.textContent = option.textContent;
                      option.classList.add('selected');
                    }
                    dropdown.dispatchEvent(new Event('change', { bubbles: true }));
                  }
                }
              });
            }, 200);
          }
        }
        
        if (formData['_act_items_count'] && formData['_act_items_count'] > 0) {
          const actList = document.querySelector('.act-list');
          const actAdd = document.querySelector('.act-add');
          if (actList && actAdd) {
            const currentItems = actList.children.length;
            for (let i = currentItems; i < formData['_act_items_count']; i++) {
              actAdd.click();
            }
            setTimeout(() => {
              actList.querySelectorAll('.act-row').forEach((row, idx) => {
                const valueKey = `_act_${idx}_value`;
                if (formData[valueKey]) {
                  const dropdown = row.querySelector('.custom-dropdown input[type="hidden"]');
                  if (dropdown) {
                    dropdown.value = formData[valueKey];
                    const selectedText = dropdown.closest('.custom-dropdown').querySelector('.selected-text');
                    const option = dropdown.closest('.custom-dropdown').querySelector(`[data-value="${formData[valueKey]}"]`);
                    if (selectedText && option) {
                      selectedText.textContent = option.textContent;
                      option.classList.add('selected');
                    }
                    dropdown.dispatchEvent(new Event('change', { bubbles: true }));
                  }
                }
              });
            }, 200);
          }
        }
      }, 800);
      
      // Повторная попытка восстановления через 1.5 секунды (на случай, если скрипты еще не загрузились)
      setTimeout(() => {
        // Повторно восстанавливаем dropdown'ы, которые могли не восстановиться
        document.querySelectorAll('input[type="hidden"]').forEach(field => {
          if (field.name && formData[field.name] && !field.value) {
            field.value = formData[field.name];
            const dropdown = field.closest('.custom-dropdown');
            if (dropdown) {
              const selectedText = dropdown.querySelector('.selected-text');
              const option = dropdown.querySelector(`[data-value="${formData[field.name]}"]`);
              if (selectedText && option) {
                selectedText.textContent = option.textContent;
                option.classList.add('selected');
              }
              field.dispatchEvent(new Event('change', { bubbles: true }));
            }
          }
        });
      }, 1500);
      
    } catch (e) {
      // Ошибка восстановления данных
    }
  }
  
  function quickSave() {
    const formData = {};
    
    document.querySelectorAll('input[type="text"], input[type="search"], input[type="email"], input[type="url"], textarea').forEach(field => {
      if (field.type !== 'file' && !field.hidden && field.name && field.value.trim()) {
        formData[field.name] = field.value.trim();
      }
    });
    
    document.querySelectorAll('input[type="hidden"]').forEach(field => {
      if (field.name) {
        formData[field.name] = field.value;
        const dropdown = field.closest('.custom-dropdown');
        if (dropdown) {
          const selectedText = dropdown.querySelector('.selected-text');
          if (selectedText && selectedText.textContent && selectedText.textContent !== '…') {
            formData[field.name + '_text'] = selectedText.textContent;
          }
        }
      }
    });
    
    document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
      if (radio.name) {
        formData[radio.name] = radio.value;
      }
    });
    
    const checkboxValues = {};
    document.querySelectorAll('input[type="checkbox"]:checked').forEach(checkbox => {
      if (checkbox.name) {
        if (!checkboxValues[checkbox.name]) {
          checkboxValues[checkbox.name] = [];
        }
        checkboxValues[checkbox.name].push(checkbox.value || 'checked');
      }
    });
    Object.assign(formData, checkboxValues);
    
    localStorage.setItem(STORAGE_KEY, JSON.stringify(formData));
  }
  
  function fillTestData() {
    const textFields = {
      'name': 'a',
      'tax_id': '1',
      'legal_name': 'a',
      'start_date': '01/01/2001',
      'website': 'http://a',
      'street_legal': 'a',
      'street_number_legal': '1',
      'postal_code_legal': '1',
      'floor_legal': '1',
      'apartment_legal': 'a',
      'street_admin': 'a',
      'street_number_admin': '1',
      'postal_code_admin': '1',
      'floor_admin': '1',
      'apartment_admin': 'a',
      'contact_person': 'a',
      'contact_position': 'a',
      'contact_email': 'a@gmail.com',
      'contact_area_code': '1',
      'contact_phone': '1',
      'main_product': 'a',
      'tariff_code': '1',
      'product_description': 'a',
      'volume_amount': '1',
      'annual_export': '1',
      'certifications': 'a',
      'export_2022': '1',
      'export_2023': '1',
      'export_2024': '1',
      'company_history': 'a',
      'awards_detail': 'a',
      'commercial_references': 'a',
      'estimated_term': '1',
      'logistics_infrastructure': 'a',
      'ports_airports': 'a',
      'other_differentiation': 'a',
      'other_needs': 'a'
    };
    
    Object.keys(textFields).forEach(name => {
      const field = document.querySelector(`[name="${name}"]`);
      if (field && field.type !== 'file') {
        field.value = textFields[name];
        field.dispatchEvent(new Event('input', { bubbles: true }));
      }
    });
    
    document.querySelectorAll('.custom-dropdown').forEach(dropdown => {
      const hiddenInput = dropdown.querySelector('input[type="hidden"]');
      if (hiddenInput && hiddenInput.name) {
        const options = dropdown.querySelectorAll('.dropdown-option');
        for (let option of options) {
          const value = option.dataset.value;
          if (value && value !== '' && value !== '…') {
            hiddenInput.value = value;
            const selectedText = dropdown.querySelector('.selected-text');
            if (selectedText) {
              selectedText.textContent = option.textContent;
            }
            option.classList.add('selected');
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            break;
          }
        }
      }
    });
    
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
      const name = radio.name;
      if (name) {
        const group = document.querySelectorAll(`input[type="radio"][name="${name}"]`);
        let targetRadio = Array.from(group).find(r => r.value === 'si');
        if (!targetRadio) {
          targetRadio = group[0];
        }
        if (targetRadio) {
          targetRadio.checked = true;
          targetRadio.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    });
    
    document.querySelectorAll('input[type="checkbox"]').forEach((checkbox, idx) => {
      if (idx % 2 === 0) {
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
    
    const socialWrapper = document.getElementById('social-wrapper');
    if (socialWrapper) {
      const firstRow = socialWrapper.querySelector('.social_row');
      if (firstRow) {
        const urlInput = firstRow.querySelector('input[name="social_url[]"]');
        if (urlInput) {
          urlInput.value = 'http://a';
        }
        const hiddenInput = firstRow.querySelector('input.net');
        if (hiddenInput) {
          const dropdown = hiddenInput.closest('.custom-dropdown');
          if (dropdown) {
            const options = dropdown.querySelectorAll('.dropdown-option');
            for (let option of options) {
              const value = option.dataset.value;
              if (value && value !== '' && value !== '…') {
                hiddenInput.value = value;
                const selectedText = dropdown.querySelector('.selected-text');
                if (selectedText) {
                  selectedText.textContent = option.textContent;
                }
                option.classList.add('selected');
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                break;
              }
            }
          }
        }
      }
    }
    
    document.querySelectorAll('input[name*="[]"]').forEach(field => {
      if (field.type !== 'file' && !field.hidden) {
        if (field.name.includes('social_url')) {
          field.value = 'http://a';
        } else if (field.name.includes('secondary_products') || field.name.includes('tariff_code_sec') || field.name.includes('product_description_sec')) {
          field.value = 'a';
        } else if (field.name.includes('volume_amount_sec') || field.name.includes('annual_export_sec')) {
          field.value = '1';
        } else {
          field.value = 'a';
        }
        field.dispatchEvent(new Event('input', { bubbles: true }));
      }
    });
    
    quickSave();
  }
  
  document.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem(STORAGE_KEY);
    
    const btnFillTest = document.getElementById('btnFillTestData');
    if (btnFillTest) {
      btnFillTest.addEventListener('click', () => {
        fillTestData();
      });
    }
    
    setTimeout(() => {
      if (saved) {
      restoreFormData();
      } else {
        fillTestData();
      }
    }, 1000);
    
    const form = document.querySelector('.form') || document;
    
    form.addEventListener('input', (e) => {
      if (e.target.type !== 'file' && !e.target.hidden && e.target.name) {
        quickSave();
      }
    });
    
    form.addEventListener('change', (e) => {
      if (e.target.type === 'hidden' || e.target.type === 'radio' || e.target.type === 'checkbox') {
        quickSave();
      }
    });
    
    window.addEventListener('beforeunload', () => {
      quickSave();
    });
  });
  
  window.clearRegfullFormData = function() {
    localStorage.removeItem(STORAGE_KEY);
  };
})();
</script>

<script src="js/i18n.js?v=1.0.2"></script>
<script>
function toggleRegfullLangMenu() {
  const menu = document.getElementById('regfull_lang_menu');
  menu.classList.toggle('hidden');
}
document.addEventListener('DOMContentLoaded', () => {
  initLang('regfull');
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