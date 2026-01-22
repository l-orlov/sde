<?
// Загрузка данных компании из БД
$companyData = null;
$companyAddresses = ['legal' => null, 'admin' => null];
$companyContacts = null;
$companySocialNetworks = [];
$companyDataJson = null;

if (isset($_SESSION['uid'])) {
    $userId = intval($_SESSION['uid']);
    $companyId = null;
    $userData = null;
    
    // Загрузка данных пользователя (для company_name, tax_id и email)
    $query = "SELECT company_name, tax_id, email FROM users WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($link, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $userData = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
    
    // Загрузка основных данных компании
    $query = "SELECT id, name, tax_id, legal_name, start_date, website, organization_type, main_activity 
              FROM companies WHERE user_id = ? LIMIT 1";
    $stmt = mysqli_prepare($link, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $companyData = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($companyData) {
            $companyId = intval($companyData['id']);
            
            // Конвертация start_date из timestamp в формат d/m/Y
            if ($companyData['start_date']) {
                $timestamp = intval($companyData['start_date']);
                if ($timestamp > 0) {
                    $dateObj = new DateTime();
                    $dateObj->setTimestamp($timestamp);
                    $companyData['start_date'] = $dateObj->format('d/m/Y');
                } else {
                    $companyData['start_date'] = '';
                }
            }
        }
    }
    
    // Используем COALESCE: сначала companies, потом users
    $displayName = $companyData['name'] ?? $userData['company_name'] ?? '';
    $displayTaxId = $companyData['tax_id'] ?? $userData['tax_id'] ?? '';
    
    // Загрузка адресов
    if ($companyId) {
        $query = "SELECT type, street, street_number, postal_code, floor, apartment, locality, department 
                  FROM company_addresses WHERE company_id = ?";
        $stmt = mysqli_prepare($link, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $companyId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $companyAddresses[$row['type']] = $row;
            }
            mysqli_stmt_close($stmt);
        }
        
        // Загрузка контактов
        $query = "SELECT contact_person, position, email, area_code, phone 
                  FROM company_contacts WHERE company_id = ? LIMIT 1";
        $stmt = mysqli_prepare($link, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $companyId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $companyContacts = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
        }
        
        // Если email нет в company_contacts, берем из users
        if (empty($companyContacts['email']) && isset($userData['email'])) {
            if (!$companyContacts) {
                $companyContacts = [];
            }
            $companyContacts['email'] = $userData['email'];
        }
        
        // Загрузка социальных сетей
        $query = "SELECT network_type, url 
                  FROM company_social_networks WHERE company_id = ? 
                  ORDER BY id ASC";
        $stmt = mysqli_prepare($link, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $companyId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $companySocialNetworks[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
        
        // Загрузка данных из company_data
        $query = "SELECT current_markets, target_markets, differentiation_factors, needs, 
                         competitiveness, logistics, expectations, consents 
                  FROM company_data WHERE company_id = ? LIMIT 1";
        $stmt = mysqli_prepare($link, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $companyId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $companyDataRow = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if ($companyDataRow) {
                // Распарсить JSON поля
                $companyDataJson = [
                    'current_markets' => !empty($companyDataRow['current_markets']) ? json_decode($companyDataRow['current_markets'], true) : '',
                    'target_markets' => !empty($companyDataRow['target_markets']) ? json_decode($companyDataRow['target_markets'], true) : [],
                    'differentiation_factors' => !empty($companyDataRow['differentiation_factors']) ? json_decode($companyDataRow['differentiation_factors'], true) : [],
                    'needs' => !empty($companyDataRow['needs']) ? json_decode($companyDataRow['needs'], true) : [],
                    'competitiveness' => !empty($companyDataRow['competitiveness']) ? json_decode($companyDataRow['competitiveness'], true) : [],
                    'logistics' => !empty($companyDataRow['logistics']) ? json_decode($companyDataRow['logistics'], true) : null,
                    'expectations' => !empty($companyDataRow['expectations']) ? json_decode($companyDataRow['expectations'], true) : null,
                    'consents' => !empty($companyDataRow['consents']) ? json_decode($companyDataRow['consents'], true) : null
                ];
                
                // Нормализация данных (если current_markets это строка, а не JSON)
                if (is_string($companyDataJson['current_markets']) && !empty($companyDataJson['current_markets'])) {
                    $decoded = json_decode($companyDataJson['current_markets'], true);
                    if ($decoded !== null) {
                        $companyDataJson['current_markets'] = $decoded;
                    }
                }
                
                // Если competitiveness, logistics, expectations, consents не массивы, сделать пустыми массивами
                if (!is_array($companyDataJson['competitiveness'])) $companyDataJson['competitiveness'] = [];
                if (!is_array($companyDataJson['logistics'])) {
                    // Если logistics это null или не массив, делаем пустым массивом
                    $companyDataJson['logistics'] = [];
                }
                if (!is_array($companyDataJson['expectations'])) $companyDataJson['expectations'] = [];
                if (!is_array($companyDataJson['consents'])) $companyDataJson['consents'] = [];
            } else {
                $companyDataJson = [
                    'current_markets' => '',
                    'target_markets' => [],
                    'differentiation_factors' => [],
                    'needs' => [],
                    'competitiveness' => [],
                    'logistics' => [],
                    'expectations' => [],
                    'consents' => []
                ];
            }
        } else {
            $companyDataJson = [
                'current_markets' => '',
                'target_markets' => [],
                'differentiation_factors' => [],
                'needs' => [],
                'competitiveness' => [],
                'logistics' => [],
                'expectations' => [],
                'consents' => []
            ];
        }
    } else {
        $companyDataJson = [
            'current_markets' => '',
            'target_markets' => [],
            'differentiation_factors' => [],
            'needs' => [],
            'competitiveness' => [],
            'logistics' => [],
            'expectations' => [],
            'consents' => []
        ];
    }
    
    // Проверка типа продуктов/услуг в БД
    $hasProducts = false;
    $hasServices = false;
    $defaultType = null;
    
    if ($userId) {
        $query = "SELECT type FROM products WHERE user_id = ? LIMIT 1";
        $stmt = mysqli_prepare($link, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $productRow = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if ($productRow) {
                if ($productRow['type'] === 'product') {
                    $hasProducts = true;
                    $defaultType = 'product';
                } else if ($productRow['type'] === 'service') {
                    $hasServices = true;
                    $defaultType = 'service';
                }
            }
        }
    }
    
    $showSelector = !$hasProducts && !$hasServices;
} else {
    $hasProducts = false;
    $hasServices = false;
    $defaultType = null;
    $showSelector = true;
}

// Функция для безопасного вывода значений
function esc_attr($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!-- EMPRESA -->
<div class="regfull-lang" onclick="toggleRegfullLangMenu()">
  <img src="img/icons/lang.png" alt="Language">
  <span id="regfull-current-lang">Es</span>
  <ul id="regfull_lang_menu" class="regfull_lang_menu hidden">
    <li onclick="setLang('regfull', 'es')">Español</li>
    <li onclick="setLang('regfull', 'en')">English</li>
  </ul>
</div>
<div class="datos">
  <div class="datos_header">
    <h1 data-i18n="regfull_section1_title">1. Datos de la empresa</h1>
    <img src="img/icons/regfull_datos.svg">
  </div>
  <div class="form" novalidate>
    <!-- Nombre -->
    <div class="label"><label for="name" data-i18n="regfull_company_name">Nombre de la Empresa/Emprendimiento <span class="req">*</span></label></div>
    <div class="field"><input type="search" id="name" name="name" value="<?= esc_attr($displayName) ?>" required></div>
    <!-- CUIT -->
    <div class="label"><label for="tax_id" data-i18n="regfull_cuit">CUIT / Identificación Fiscal <span class="req">*</span></label></div>
    <div class="field"><input type="search" id="tax_id" name="tax_id" value="<?= esc_attr($displayTaxId) ?>" data-i18n-placeholder="regfull_cuit_placeholder" placeholder="XX-XXXXXXXX-X" required></div>
    <!-- Razón social -->
    <div class="label"><label for="legal_name" data-i18n="regfull_razon_social">Razón social <span class="req">*</span></label></div>
    <div class="field"><input type="search" id="legal_name" name="legal_name" value="<?= esc_attr($companyData['legal_name'] ?? '') ?>" required></div>
    <!-- Fecha de inicio -->
    <div class="label"><label for="start_date" data-i18n="regfull_start_date">Fecha de Inicio de Actividad <span class="req">*</span></label></div>
    <div class="field"><input type="search" id="start_date" name="start_date" value="<?= esc_attr($companyData['start_date'] ?? '') ?>" data-i18n-placeholder="regfull_date_placeholder" placeholder="dd/mm/aaaa" inputmode="numeric" required></div>
    <!-- Página web -->
    <div class="label"><label for="website" data-i18n="regfull_website">Página web (si aplica)</label></div>
    <div class="field"><input type="search" id="website" name="website" value="<?= esc_attr($companyData['website'] ?? '') ?>" type="url" data-i18n-placeholder="regfull_website_placeholder" placeholder="http://…"></div>
    <!-- Redes sociales -->
    <div class="label"><span data-i18n="regfull_social_networks">Redes sociales:</span></div>
    <div class="field" id="social-wrapper">
      <div class="social_row">
        <div class="custom-dropdown">
          <div class="dropdown-selected">
            <span class="selected-text" data-i18n="regfull_dropdown_placeholder">…</span>
            <span class="dropdown-arrow">▼</span>
          </div>
          <div class="dropdown-options">
            <div class="dropdown-option" data-value="" data-i18n="regfull_dropdown_placeholder">…</div>
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
        <input type="search" name="street_legal" class="span_right" value="<?= esc_attr($companyAddresses['legal']['street'] ?? '') ?>">
        <label class="label_span" data-i18n="regfull_number">Altura <span class="req">*</span></label>
        <input type="search" name="street_number_legal" class="" value="<?= esc_attr($companyAddresses['legal']['street_number'] ?? '') ?>">
        <label class="label_span" data-i18n="regfull_postal_code">Código Postal <span class="req">*</span></label>
        <input type="search" name="postal_code_legal" class="" value="<?= esc_attr($companyAddresses['legal']['postal_code'] ?? '') ?>">
        <label class="label_span" data-i18n="regfull_floor">Piso</label>
        <input type="search" name="floor_legal" class="" value="<?= esc_attr($companyAddresses['legal']['floor'] ?? '') ?>">
        <label class="label_span" data-i18n="regfull_apartment">Departamento</label>
        <input type="search" name="apartment_legal" class="" value="<?= esc_attr($companyAddresses['legal']['apartment'] ?? '') ?>">
        <label class="label_span" data-i18n="regfull_locality">Localidad <span class="req">*</span></label>
        <div class="span_right">
          <div class="custom-dropdown">
            <div class="dropdown-selected">
              <span class="selected-text" data-i18n="regfull_dropdown_placeholder">…</span>
              <span class="dropdown-arrow">▼</span>
            </div>
            <div class="dropdown-options">
              <div class="dropdown-option" data-value="" data-i18n="regfull_dropdown_placeholder">…</div>
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
            <input type="hidden" name="locality_legal" value="<?= esc_attr($companyAddresses['legal']['locality'] ?? '') ?>">
          </div>
        </div>
        <label class="label_span" data-i18n="regfull_department">Departamento <span class="req">*</span></label>
        <div class="span_right">
          <div class="custom-dropdown">
            <div class="dropdown-selected">
              <span class="selected-text" data-i18n="regfull_dropdown_placeholder">…</span>
              <span class="dropdown-arrow">▼</span>
            </div>
            <div class="dropdown-options">
              <div class="dropdown-option" data-value="" data-i18n="regfull_dropdown_placeholder">…</div>
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
            <input type="hidden" name="department_legal" value="<?= esc_attr($companyAddresses['legal']['department'] ?? '') ?>">
          </div>
        </div>
      </div>
    </div>
    <!-- Dirección administrativa -->
    <div class="address">
      <div class="label"><span data-i18n="regfull_admin_address">Dirección administrativa <span class="req">*</span></span></div>
      <div class="address_grid">
        <label class="label_span" data-i18n="regfull_street">Calle <span class="req">*</span></label>
        <input type="search" name="street_admin" class="span_right" value="<?= esc_attr($companyAddresses['admin']['street'] ?? '') ?>">
        <label class="label_span" data-i18n="regfull_number">Altura <span class="req">*</span></label>
        <input type="search" name="street_number_admin" class="" value="<?= esc_attr($companyAddresses['admin']['street_number'] ?? '') ?>">
        <label class="label_span" data-i18n="regfull_postal_code">Código Postal <span class="req">*</span></label>
        <input type="search" name="postal_code_admin" class="" value="<?= esc_attr($companyAddresses['admin']['postal_code'] ?? '') ?>">
        <label class="label_span" data-i18n="regfull_floor">Piso</label>
        <input type="search" name="floor_admin" class="" value="<?= esc_attr($companyAddresses['admin']['floor'] ?? '') ?>">
        <label class="label_span" data-i18n="regfull_apartment">Departamento</label>
        <input type="search" name="apartment_admin" class="" value="<?= esc_attr($companyAddresses['admin']['apartment'] ?? '') ?>">
        <label class="label_span" data-i18n="regfull_locality">Localidad <span class="req">*</span></label>
        <div class="span_right">
          <div class="custom-dropdown">
            <div class="dropdown-selected">
              <span class="selected-text" data-i18n="regfull_dropdown_placeholder">…</span>
              <span class="dropdown-arrow">▼</span>
            </div>
            <div class="dropdown-options">
              <div class="dropdown-option" data-value="" data-i18n="regfull_dropdown_placeholder">…</div>
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
            <input type="hidden" name="locality_admin" value="<?= esc_attr($companyAddresses['admin']['locality'] ?? '') ?>">
          </div>
        </div>
        <label class="label_span" data-i18n="regfull_department">Departamento <span class="req">*</span></label>
        <div class="span_right">
          <div class="custom-dropdown">
            <div class="dropdown-selected">
              <span class="selected-text" data-i18n="regfull_dropdown_placeholder">…</span>
              <span class="dropdown-arrow">▼</span>
            </div>
            <div class="dropdown-options">
              <div class="dropdown-option" data-value="" data-i18n="regfull_dropdown_placeholder">…</div>
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
            <input type="hidden" name="department_admin" value="<?= esc_attr($companyAddresses['admin']['department'] ?? '') ?>">
          </div>
        </div>
      </div>
    </div>
    <!-- Contacto -->
    <div class="contacto_datos">
      <div class="label"><span data-i18n="regfull_contact_person">Persona de Contacto <span class="req">*</span></span></div>
      <div class="contacto_grid">
        <input type="search" name="contact_person" class="span_all" value="<?= esc_attr($companyContacts['contact_person'] ?? '') ?>">
        <label class="label_span" data-i18n="regfull_contact_position">Cargo de Persona de contacto <span class="req">*</span></label>
        <input type="search" name="contact_position" value="<?= esc_attr($companyContacts['position'] ?? '') ?>">
        <label class="label_span" data-i18n="regfull_email">E-mail <span class="req">*</span></label>
        <input type="email" name="contact_email" value="<?= esc_attr($companyContacts['email'] ?? '') ?>">
        <label class="label_span" data-i18n="regfull_phone">Teléfono <span class="req">*</span></label>
        <div class="phone_inline">
          <input type="search" name="contact_area_code" class="area" value="<?= esc_attr($companyContacts['area_code'] ?? '') ?>" data-i18n-placeholder="regfull_area_code" placeholder="Código de área">
          <input type="search" name="contact_phone" value="<?= esc_attr($companyContacts['phone'] ?? '') ?>" placeholder="">
        </div>
      </div>
    </div>
  </div>
</div>
<div class="datos">
  <div class="datos_header">
    <h1 data-i18n="regfull_section2_title">2. Clasificación de la Empresa</h1>
    <img src="img/icons/regfull_clasificacion.svg">
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
      // Инициализируем dropdown (значение будет установлено через fillDropdownsFromDB для main_activity)
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
    <img src="img/icons/regfull_sobre_productos.svg">
  </div>
  <div class="form" novalidate>
    <!-- Общая кнопка "agregar más" (всегда видна) -->
    <div class="add-more-wrapper">
      <button type="button" class="btn-add-more type-pill" id="btn-add-more">
        <img class="type-pill__icon" src="img/icons/regfull_add_plus.png" alt="">
        <span class="type-pill__text" data-i18n="regfull_add_more">agregar más</span>
      </button>
      <!-- Inline меню выбора (скрыто по умолчанию) -->
      <div class="add-more-menu hidden">
        <button type="button" class="menu-option type-pill" data-type="product">
          <span class="type-pill__text" data-i18n="regfull_add_products">Agregar productos</span>
          <img class="type-pill__icon" src="img/icons/regfull_add_product.svg" alt="">
        </button>
        <button type="button" class="menu-option type-pill" data-type="service">
          <span class="type-pill__text" data-i18n="regfull_add_services">Agregar servicios</span>
          <img class="type-pill__icon" src="img/icons/regfull_add_service.svg" alt="">
        </button>
      </div>
    </div>
    
    <!-- Единый список для продуктов и услуг -->
    <div class="items-list">
      <!-- Продукты и услуги будут добавлены через JavaScript -->
    </div>
    
    <!-- Шаблон для продукта -->
    <template class="product-item-tpl">
      <div class="product-item">
        <div class="item-badge item-badge-product">Producto</div>
        <div class="producto_grid">
          <label class="label_span" data-i18n="regfull_main_product">Producto <span class="req">*</span></label>
          <input type="search" name="product_name[]" class="span_right">
          <label class="label_span" data-i18n="regfull_description">Descripción <span class="req">*</span></label>
          <input type="search" name="product_description[]">
          <label class="label_span" data-i18n="regfull_annual_export">Exportación Anual (USD)</label>
          <input type="search" name="annual_export[]">
          <label class="label_span" data-i18n="regfull_product_photo">Foto del Producto</label>
          <div class="file-ph-wrapper">
            <input type="file" class="file-ph" name="product_photo[]" accept="image/jpeg,image/png,application/pdf" data-i18n-placeholder="regfull_upload_file" placeholder="subir archivo (JPG, PNG, PDF)">
            <div class="file-ph-display">
              <img class="file-preview-img" src="" alt="" style="display: none;">
              <span class="file-ph-placeholder" data-i18n="regfull_upload_file">subir archivo (JPG, PNG, PDF)</span>
              <button type="button" class="file-ph-remove" style="display: none;" aria-label="Eliminar">&times;</button>
            </div>
          </div>
          <!-- Certificaciones (внутри продукта) -->
          <label class="label_span" data-i18n="regfull_certifications">Certificaciones</label>
          <input type="search" name="product_certifications[]" data-i18n-placeholder="regfull_certifications_placeholder" placeholder="ejemplo: orgánico, comercio justo, ISO, halal, kosher, etc.">
          <!-- Mercados Actuales (внутри продукта) -->
          <label class="label_span" data-i18n="regfull_current_markets">Mercados Actuales (Continente) <span class="req">*</span></label>
          <div class="span_right">
            <div class="custom-dropdown">
              <div class="dropdown-selected">
                <span class="selected-text" data-i18n="regfull_dropdown_placeholder">…</span>
                <span class="dropdown-arrow">▼</span>
              </div>
              <div class="dropdown-options">
                <div class="dropdown-option" data-value="" data-i18n="regfull_dropdown_placeholder">…</div>
                <div class="dropdown-option" data-value="América del Norte">América del Norte</div>
                <div class="dropdown-option" data-value="América del Sur">América del Sur</div>
                <div class="dropdown-option" data-value="Europa">Europa</div>
                <div class="dropdown-option" data-value="Asia">Asia</div>
                <div class="dropdown-option" data-value="África">África</div>
                <div class="dropdown-option" data-value="Oceanía">Oceanía</div>
              </div>
              <input type="hidden" name="product_current_markets[]" value="">
            </div>
          </div>
          <!-- Mercados de Interés (внутри продукта) -->
          <label class="label_span" data-i18n="regfull_interest_markets">
            <span>Mercados de Interés (Continente)</span>
            <div class="sub" data-i18n="regfull_interest_markets_sub">(a donde le gustaría exportar)</div>
          </label>
          <div class="span_right">
            <div class="mercados_act product-markets">
              <div class="act-list"></div>
              <button type="button" class="add_more act-add" data-i18n="regfull_add_more">agregar más</button>
              <template class="act-item-tpl">
                <div class="act-row">
                  <div class="custom-dropdown">
                    <div class="dropdown-selected">
                      <span class="selected-text" data-i18n="regfull_dropdown_placeholder">…</span>
                      <span class="dropdown-arrow">▼</span>
                    </div>
                    <div class="dropdown-options">
                      <div class="dropdown-option" data-value="" data-i18n="regfull_dropdown_placeholder">…</div>
                      <div class="dropdown-option" data-value="América del Norte">América del Norte</div>
                      <div class="dropdown-option" data-value="América del Sur">América del Sur</div>
                      <div class="dropdown-option" data-value="Europa">Europa</div>
                      <div class="dropdown-option" data-value="Asia">Asia</div>
                      <div class="dropdown-option" data-value="África">África</div>
                      <div class="dropdown-option" data-value="Oceanía">Oceanía</div>
                    </div>
                    <input type="hidden" name="product_target_markets[]" value="">
                  </div>
                  <button type="button" class="remove" aria-label="Eliminar" data-i18n-aria-label="regfull_remove">&times;</button>
                </div>
              </template>
            </div>
          </div>
          <input type="hidden" name="product_type[]" value="product">
          <button type="button" class="remove remove-product" aria-label="Eliminar" data-i18n-aria-label="regfull_remove">&times;</button>
        </div>
      </div>
    </template>
    
    <!-- Шаблон для услуги -->
    <template class="service-item-tpl">
      <div class="service-item">
        <div class="item-badge item-badge-service">Servicio</div>
        <!-- Остальные поля в servicio_grid (как producto_grid для продуктов) -->
        <div class="servicio_grid">
          <!-- Actividad * - в одной строке с label -->
          <label class="label_span" data-i18n="regfull_activity">Actividad <span class="req">*</span></label>
          <div class="span_right">
            <div class="custom-dropdown">
              <div class="dropdown-selected">
                <span class="selected-text" data-i18n="regfull_dropdown_placeholder">…</span>
                <span class="dropdown-arrow">▼</span>
              </div>
              <div class="dropdown-options">
                <div class="dropdown-option" data-value="" data-i18n="regfull_dropdown_placeholder">…</div>
                <div class="dropdown-option" data-value="Staff augmentation / provisión de perfiles especializados">Staff augmentation / provisión de perfiles especializados</div>
                <div class="dropdown-option" data-value="Implementadores de soluciones">Implementadores de soluciones</div>
                <div class="dropdown-option" data-value="Ciencia de datos">Ciencia de datos</div>
                <div class="dropdown-option" data-value="Análisis de datos y scraping">Análisis de datos y scraping</div>
                <div class="dropdown-option" data-value="Blockchain">Blockchain</div>
                <div class="dropdown-option" data-value="Biotecnología (servicios, prótesis)">Biotecnología (servicios, prótesis)</div>
                <div class="dropdown-option" data-value="Turismo (servicios tecnológicos asociados)">Turismo (servicios tecnológicos asociados)</div>
                <div class="dropdown-option" data-value="Marketing Digital">Marketing Digital</div>
                <div class="dropdown-option" data-value="Servicios de mantenimiento aeronáutico">Servicios de mantenimiento aeronáutico</div>
                <div class="dropdown-option" data-value="IA – servicios de desarrollo (bots de lenguaje natural, soluciones a medida)">IA – servicios de desarrollo (bots de lenguaje natural, soluciones a medida)</div>
                <div class="dropdown-option" data-value="e-Government (soluciones para Estado provincial y municipios)">e-Government (soluciones para Estado provincial y municipios)</div>
                <div class="dropdown-option" data-value="Consultoría de procesos y transformación digital">Consultoría de procesos y transformación digital</div>
                <div class="dropdown-option" data-value="Diseño mecánico">Diseño mecánico</div>
                <div class="dropdown-option" data-value="Diseño 3D">Diseño 3D</div>
                <div class="dropdown-option" data-value="Diseño multimedia">Diseño multimedia</div>
                <div class="dropdown-option" data-value="Diseño de hardware">Diseño de hardware</div>
                <div class="dropdown-option" data-value="Fintech">Fintech</div>
                <div class="dropdown-option" data-value="Growth Marketing">Growth Marketing</div>
                <div class="dropdown-option" data-value="Economía del Conocimiento – Productos orientados a Salud">Economía del Conocimiento – Productos orientados a Salud</div>
                <div class="dropdown-option" data-value="Sistemas de facturación">Sistemas de facturación</div>
              </div>
              <input type="hidden" name="service_activity[]" value="">
            </div>
          </div>

          <!-- Servicio * - в одной строке с label -->
          <label class="label_span" data-i18n="regfull_service">Servicio <span class="req">*</span></label>
          <input type="search" name="service_name[]" class="span_right">
          <label class="label_span" data-i18n="regfull_description">Descripción <span class="req">*</span></label>
          <input type="search" name="service_description[]">
          <label class="label_span" data-i18n="regfull_annual_export">Exportación Anual (USD)</label>
          <input type="search" name="annual_export[]">
          <label class="label_span" data-i18n="regfull_service_photo">Foto del Servicio</label>
          <div class="file-ph-wrapper">
            <input type="file" class="file-ph" name="service_photo[]" accept="image/jpeg,image/png,application/pdf" data-i18n-placeholder="regfull_upload_file" placeholder="subir archivo (JPG, PNG, PDF)">
            <div class="file-ph-display">
              <img class="file-preview-img" src="" alt="" style="display: none;">
              <span class="file-ph-placeholder" data-i18n="regfull_upload_file">subir archivo (JPG, PNG, PDF)</span>
              <button type="button" class="file-ph-remove" style="display: none;" aria-label="Eliminar">&times;</button>
            </div>
          </div>
          <!-- Certificaciones (внутри услуги) -->
          <label class="label_span" data-i18n="regfull_certifications">Certificaciones</label>
          <input type="search" name="service_certifications[]" data-i18n-placeholder="regfull_certifications_placeholder" placeholder="ejemplo: orgánico, comercio justo, ISO, halal, kosher, etc.">
          <!-- Mercados Actuales (внутри услуги) -->
          <label class="label_span" data-i18n="regfull_current_markets">Mercados Actuales (Continente) <span class="req">*</span></label>
          <div class="span_right">
            <div class="custom-dropdown">
              <div class="dropdown-selected">
                <span class="selected-text" data-i18n="regfull_dropdown_placeholder">…</span>
                <span class="dropdown-arrow">▼</span>
              </div>
              <div class="dropdown-options">
                <div class="dropdown-option" data-value="" data-i18n="regfull_dropdown_placeholder">…</div>
                <div class="dropdown-option" data-value="América del Norte">América del Norte</div>
                <div class="dropdown-option" data-value="América del Sur">América del Sur</div>
                <div class="dropdown-option" data-value="Europa">Europa</div>
                <div class="dropdown-option" data-value="Asia">Asia</div>
                <div class="dropdown-option" data-value="África">África</div>
                <div class="dropdown-option" data-value="Oceanía">Oceanía</div>
              </div>
              <input type="hidden" name="service_current_markets[]" value="">
            </div>
          </div>
          <!-- Mercados de Interés (внутри услуги) -->
          <label class="label_span" data-i18n="regfull_interest_markets">
            <span>Mercados de Interés (Continente)</span>
            <div class="sub" data-i18n="regfull_interest_markets_sub">(a donde le gustaría exportar)</div>
          </label>
          <div class="span_right">
            <div class="mercados_act service-markets">
              <div class="act-list"></div>
              <button type="button" class="add_more act-add" data-i18n="regfull_add_more">agregar más</button>
              <template class="act-item-tpl">
                <div class="act-row">
                  <div class="custom-dropdown">
                    <div class="dropdown-selected">
                      <span class="selected-text" data-i18n="regfull_dropdown_placeholder">…</span>
                      <span class="dropdown-arrow">▼</span>
                    </div>
                    <div class="dropdown-options">
                      <div class="dropdown-option" data-value="" data-i18n="regfull_dropdown_placeholder">…</div>
                      <div class="dropdown-option" data-value="América del Norte">América del Norte</div>
                      <div class="dropdown-option" data-value="América del Sur">América del Sur</div>
                      <div class="dropdown-option" data-value="Europa">Europa</div>
                      <div class="dropdown-option" data-value="Asia">Asia</div>
                      <div class="dropdown-option" data-value="África">África</div>
                      <div class="dropdown-option" data-value="Oceanía">Oceanía</div>
                    </div>
                    <input type="hidden" name="service_target_markets[]" value="">
                  </div>
                  <button type="button" class="remove" aria-label="Eliminar" data-i18n-aria-label="regfull_remove">&times;</button>
                </div>
              </template>
            </div>
          </div>
          <input type="hidden" name="service_type[]" value="service">
          <button type="button" class="remove remove-service" aria-label="Eliminar" data-i18n-aria-label="regfull_remove">&times;</button>
        </div>
      </div>
    </template>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
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
<script>
// Управление продуктами и услугами: добавление и удаление
document.addEventListener('DOMContentLoaded', () => {
  const btnAddMore = document.getElementById('btn-add-more');
  const addMoreMenu = document.querySelector('.add-more-menu');
  const itemsList = document.querySelector('.items-list');
  const productTemplate = document.querySelector('.product-item-tpl');
  const serviceTemplate = document.querySelector('.service-item-tpl');
  
  // Общая функция для обновления отображения файла
  function updateFileDisplay(input, url, name) {
    const wrapper = input.closest('.file-ph-wrapper');
    if (!wrapper) return;
    
    const display = wrapper.querySelector('.file-ph-display');
    const img = display.querySelector('img.file-preview-img');
    const video = display.querySelector('video.file-preview-img');
    const placeholder = display.querySelector('.file-ph-placeholder');
    const removeBtn = display.querySelector('.file-ph-remove');
    
    if (url && name) {
      const isVideo = name.toLowerCase().match(/\.(mp4|mkv|avi|mov|webm)$/i) || url.toLowerCase().match(/\.(mp4|mkv|avi|mov|webm)$/i);
      
      if (isVideo && video) {
        video.src = url;
        video.style.display = 'block';
        if (img) img.style.display = 'none';
      } else if (img) {
        img.src = url;
        img.alt = name;
        img.style.display = 'block';
        if (video) video.style.display = 'none';
      }
      
      placeholder.style.display = 'none';
      removeBtn.style.display = 'flex';
      display.classList.add('has-image');
    } else {
      if (img) {
        img.style.display = 'none';
        img.src = '';
      }
      if (video) {
        video.style.display = 'none';
        video.src = '';
      }
      placeholder.style.display = 'block';
      removeBtn.style.display = 'none';
      display.classList.remove('has-image');
    }
  }
  
  // Общая функция для привязки обработчиков файлов
  function bindFileInputEvents(item) {
    const fileInput = item.querySelector('input.file-ph');
    const fileDisplay = item.querySelector('.file-ph-display');
    const fileRemove = item.querySelector('.file-ph-remove');
    
    if (!fileInput || !fileDisplay) return;
    
    if (!fileDisplay._bound) {
      fileDisplay.addEventListener('click', (e) => {
        if (e.target === fileRemove || e.target.closest('.file-ph-remove')) {
          return;
        }
        fileInput.click();
      });
      fileDisplay._bound = true;
    }
    
    if (!fileInput._bound) {
      fileInput.addEventListener('change', (e) => {
        if (e.target.files && e.target.files.length > 0) {
          const file = e.target.files[0];
          if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (event) => {
              updateFileDisplay(fileInput, event.target.result, file.name);
            };
            reader.readAsDataURL(file);
          } else {
            updateFileDisplay(fileInput, null, file.name);
          }
        }
      });
      fileInput._bound = true;
    }
    
    if (fileRemove && !fileRemove._bound) {
      fileRemove.addEventListener('click', (e) => {
        e.stopPropagation();
        fileInput.value = '';
        updateFileDisplay(fileInput, null, null);
      });
      fileRemove._bound = true;
    }
  }
  
  // Обновление кнопок удаления для всех элементов
  function updateRemoveButtons() {
    if (!itemsList) return;
    const allItems = itemsList.querySelectorAll('.product-item, .service-item');
    allItems.forEach((item, index) => {
      const removeBtn = item.querySelector('.remove-product, .remove-service');
      if (removeBtn) {
        // Всегда показываем кнопку удаления, можно удалять все элементы
        removeBtn.hidden = false;
      }
    });
  }
  
  // Инициализация Mercados de Interés для элемента
  function initMercadosAct(item) {
    const mercadosBox = item.querySelector('.mercados_act');
    if (!mercadosBox) return;
    
    const list = mercadosBox.querySelector('.act-list');
    const tpl = mercadosBox.querySelector('.act-item-tpl');
    const add = mercadosBox.querySelector('.act-add');
    
    if (!list || !tpl || !add) return;
    
    function updateRemoves() {
      const rows = list.querySelectorAll('.act-row');
      rows.forEach((row, i) => {
        const btn = row.querySelector('.remove');
        if (!btn) return;
        btn.hidden = (rows.length === 1 && i === 0);
      });
    }
    
    function addRow() {
      const node = tpl.content.firstElementChild.cloneNode(true);
      node.querySelector('.remove').addEventListener('click', () => {
        node.remove();
        updateRemoves();
      });
      
      const dropdown = node.querySelector('.custom-dropdown');
      if (dropdown) {
        const hiddenInput = dropdown.querySelector('input[type="hidden"]');
        if (hiddenInput && typeof initCustomDropdown === 'function') {
          initCustomDropdown(dropdown, hiddenInput);
        }
      }
      
      list.appendChild(node);
      updateRemoves();
    }
    
    if (!add._bound) {
      add.addEventListener('click', addRow);
      add._bound = true;
    }
    
    if (!list.children.length) addRow();
  }
  
  // Функция для удаления продукта/услуги
  async function deleteProductOrService(item, isService = false) {
    // Проверяем наличие ID (если элемент уже сохранен в БД)
    const hiddenInput = item.querySelector(isService ? 'input[type="hidden"][name="service_id[]"]' : 'input[type="hidden"][name="product_id[]"]');
    const productId = hiddenInput ? parseInt(hiddenInput.value, 10) : null;
    
    // Если элемент новый (нет ID), просто удаляем из DOM
    if (!productId || isNaN(productId)) {
      item.remove();
      updateRemoveButtons();
      return;
    }
    
    // Если элемент сохранен в БД, показываем подтверждение
    const itemType = isService ? 'servicio' : 'producto';
    const itemName = item.querySelector(isService ? 'input[name="service_name[]"]' : 'input[name="product_name[]"]')?.value || itemType;
    
    if (!confirm(`¿Está seguro de que desea eliminar el ${itemType} "${itemName}"?\n\nEsta acción no se puede deshacer.`)) {
      return;
    }
    
    // Отправляем запрос на удаление
    try {
      const response = await fetch('includes/regfull_delete_product_js.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ product_id: productId })
      });
      
      const result = await response.json();
      
      if (result.ok === 1) {
        // Показываем сообщение об успехе
        alert(result.res || `${itemType} eliminado correctamente.`);
        
        // Удаляем элемент из DOM
        item.remove();
        updateRemoveButtons();
      } else {
        // Показываем сообщение об ошибке и оставляем элемент
        alert('Error: ' + (result.err || 'No se pudo eliminar el ' + itemType + '.'));
      }
    } catch (error) {
      console.error('Error deleting product/service:', error);
      alert('Error de conexión. No se pudo eliminar el ' + itemType + '.');
    }
  }
  
  // Привязка обработчиков для продукта
  function bindProductItemEvents(item) {
    bindFileInputEvents(item);
    
    // Инициализация dropdown для Mercados Actuales
    const currentMarketsDropdown = item.querySelector('input[name="product_current_markets[]"]');
    if (currentMarketsDropdown) {
      const dropdown = currentMarketsDropdown.closest('.custom-dropdown');
      if (dropdown && typeof initCustomDropdown === 'function') {
        initCustomDropdown(dropdown, currentMarketsDropdown);
      }
    }
    
    // Инициализация Mercados de Interés
    initMercadosAct(item);
    
    const removeBtn = item.querySelector('.remove-product');
    if (removeBtn && !removeBtn._bound) {
      removeBtn.addEventListener('click', async () => {
        await deleteProductOrService(item, false);
      });
      removeBtn._bound = true;
    }
  }
  
  // Привязка обработчиков для услуги
  function bindServiceItemEvents(item) {
    bindFileInputEvents(item);
    
    // Инициализация dropdown для Actividad
    const activityDropdown = item.querySelector('input[name="service_activity[]"]');
    if (activityDropdown) {
      const dropdown = activityDropdown.closest('.custom-dropdown');
      if (dropdown && typeof initCustomDropdown === 'function') {
        initCustomDropdown(dropdown, activityDropdown);
      }
    }
    
    // Инициализация dropdown для Mercados Actuales
    const currentMarketsDropdown = item.querySelector('input[name="service_current_markets[]"]');
    if (currentMarketsDropdown) {
      const dropdown = currentMarketsDropdown.closest('.custom-dropdown');
      if (dropdown && typeof initCustomDropdown === 'function') {
        initCustomDropdown(dropdown, currentMarketsDropdown);
      }
    }
    
    // Инициализация Mercados de Interés
    initMercadosAct(item);
    
    const removeBtn = item.querySelector('.remove-service');
    if (removeBtn && !removeBtn._bound) {
      removeBtn.addEventListener('click', async () => {
        await deleteProductOrService(item, true);
      });
      removeBtn._bound = true;
    }
  }
  
  // Функция для сброса значений элемента
  function resetItemValues(item, isService = false) {
    // Сбросить все input поля
    item.querySelectorAll('input').forEach(input => {
      if (input.type === 'file') {
        input.value = '';
      } else if (input.type !== 'hidden' || input.name.includes('type')) {
        input.value = '';
      }
    });
    
    // Сбросить все dropdown
    item.querySelectorAll('.custom-dropdown').forEach(dropdown => {
      const selectedText = dropdown.querySelector('.selected-text');
      if (selectedText) selectedText.textContent = '…';
      const hiddenInput = dropdown.querySelector('input[type="hidden"]');
      if (hiddenInput && !hiddenInput.name.includes('type')) {
        hiddenInput.value = '';
      }
      dropdown.querySelectorAll('.dropdown-option').forEach(opt => {
        opt.classList.remove('selected');
      });
    });
    
    // Сбросить отображение файла
    const fileDisplay = item.querySelector('.file-ph-display');
    if (fileDisplay) {
      const img = fileDisplay.querySelector('.file-preview-img');
      const placeholder = fileDisplay.querySelector('.file-ph-placeholder');
      const removeBtn = fileDisplay.querySelector('.file-ph-remove');
      if (img) img.style.display = 'none';
      if (placeholder) placeholder.style.display = 'block';
      if (removeBtn) removeBtn.style.display = 'none';
      fileDisplay.classList.remove('has-image');
    }
    
    // Очистить Mercados de Interés
    const mercadosList = item.querySelector('.act-list');
    if (mercadosList) {
      const rows = mercadosList.querySelectorAll('.act-row');
      for (let i = 1; i < rows.length; i++) {
        rows[i].remove();
      }
      if (rows[0]) {
        const firstDropdown = rows[0].querySelector('.custom-dropdown');
        if (firstDropdown) {
          const selectedText = firstDropdown.querySelector('.selected-text');
          if (selectedText) selectedText.textContent = '…';
          const hiddenInput = firstDropdown.querySelector('input[type="hidden"]');
          if (hiddenInput) hiddenInput.value = '';
          firstDropdown.querySelectorAll('.dropdown-option').forEach(opt => {
            opt.classList.remove('selected');
          });
        }
      }
    }
  }
  
  // Обработка кнопки "agregar más"
  if (btnAddMore && !btnAddMore._bound) {
    btnAddMore.addEventListener('click', (e) => {
      e.stopPropagation();
      addMoreMenu.classList.toggle('hidden');
    });
    btnAddMore._bound = true;
  }
  
  // Закрытие меню при клике вне его
  document.addEventListener('click', (e) => {
    if (addMoreMenu && !addMoreMenu.contains(e.target) && !btnAddMore.contains(e.target)) {
      addMoreMenu.classList.add('hidden');
    }
  });
  
  // Обработка выбора типа из меню
  if (addMoreMenu) {
    const menuOptions = addMoreMenu.querySelectorAll('.menu-option');
    menuOptions.forEach(option => {
      option.addEventListener('click', (e) => {
        e.stopPropagation();
        const type = option.dataset.type;
        
        // Закрыть меню
        addMoreMenu.classList.add('hidden');
        
        if (type === 'product' && productTemplate) {
          const newProduct = productTemplate.content.firstElementChild.cloneNode(true);
          resetItemValues(newProduct, false);
          
          if (itemsList) {
            itemsList.insertBefore(newProduct, itemsList.firstChild);
            bindProductItemEvents(newProduct);
            updateRemoveButtons();
          }
        } else if (type === 'service' && serviceTemplate) {
          const newService = serviceTemplate.content.firstElementChild.cloneNode(true);
          resetItemValues(newService, true);
          
          if (itemsList) {
            itemsList.insertBefore(newService, itemsList.firstChild);
            bindServiceItemEvents(newService);
            updateRemoveButtons();
          }
        }
      });
    });
  }
  
  // Привязать обработчики к существующим элементам
  if (itemsList) {
    itemsList.querySelectorAll('.product-item').forEach(item => {
      bindProductItemEvents(item);
    });
    itemsList.querySelectorAll('.service-item').forEach(item => {
      bindServiceItemEvents(item);
    });
    updateRemoveButtons();
  }
  
  // Экспортировать функции
  window.updateFileDisplay = updateFileDisplay;
  window.updateRemoveButtons = updateRemoveButtons;
  window.bindProductItemEvents = bindProductItemEvents;
  window.bindServiceItemEvents = bindServiceItemEvents;
});
</script>
<!-- PRODUCTOS -->

<!-- competitividad -->
<div class="datos">
  <div class="datos_header">
    <h1 data-i18n="regfull_section4_title">4. Competitividad y Diferenciación</h1>
    <img src="img/icons/regfull_competitividad.svg">
  </div>
  <div class="form" novalidate>
    <div class="compet_blk">
      <div class="label">
        <span data-i18n="regfull_differentiation_factors">Factores de Diferenciación</span>
        <div class="sub" data-i18n="regfull_select_multiple">(puede seleccionar varias opciones)</div>
      </div>
      <div class="field">
        <div class="factors_grid">
          <?php 
          $diffFactors = $companyDataJson['differentiation_factors'] ?? [];
          $otherDiff = $companyDataJson['competitiveness']['other_differentiation'] ?? '';
          ?>
          <label class="chk"><input type="checkbox" name="differentiation_factors[]" value="Calidad" <?= in_array('Calidad', $diffFactors) ? 'checked' : '' ?>><span data-i18n="regfull_quality">Calidad</span></label>
          <label class="chk"><input type="checkbox" name="differentiation_factors[]" value="Innovación" <?= in_array('Innovación', $diffFactors) ? 'checked' : '' ?>><span data-i18n="regfull_innovation">Innovación</span></label>
          <label class="chk"><input type="checkbox" name="differentiation_factors[]" value="Origen territorial" <?= in_array('Origen territorial', $diffFactors) ? 'checked' : '' ?>><span data-i18n="regfull_territorial_origin">Origen territorial</span></label>
          <label class="chk"><input type="checkbox" name="differentiation_factors[]" value="Trazabilidad" <?= in_array('Trazabilidad', $diffFactors) ? 'checked' : '' ?>><span data-i18n="regfull_traceability">Trazabilidad</span></label>
          <label class="chk"><input type="checkbox" name="differentiation_factors[]" value="Precio competitivo" <?= in_array('Precio competitivo', $diffFactors) ? 'checked' : '' ?>><span data-i18n="regfull_competitive_price">Precio competitivo</span></label>
          <div class="other">
            <label class="chk"><input type="checkbox" name="differentiation_factors[]" value="Otros" class="otros_cb" <?= in_array('Otros', $diffFactors) ? 'checked' : '' ?>><span data-i18n="regfull_others">Otros</span></label>
            <input type="search" name="other_differentiation" class="otros_inp" placeholder="" value="<?= esc_attr($otherDiff) ?>" <?= in_array('Otros', $diffFactors) ? '' : 'disabled' ?>>
          </div>
        </div>
      </div>
      <!-- 2) Historia -->
      <div class="label"><label data-i18n="regfull_company_history">Historia de la Empresa y del Producto <span class="req">*</span></label></div>
      <div class="field"><textarea name="company_history" class="ta" rows="4"><?= esc_attr($companyDataJson['competitiveness']['company_history'] ?? '') ?></textarea></div>
      <!-- 3) Premios -->
      <div class="label"><label data-i18n="regfull_awards">Premios <span class="req">*</span></label></div>
      <div class="field">
        <div class="yesno_line with_input">
          <?php $awards = $companyDataJson['competitiveness']['awards'] ?? ''; ?>
          <label class="yn"><input type="radio" name="awards" value="si" <?= $awards === 'si' ? 'checked' : '' ?>><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="awards" value="no" <?= $awards === 'no' ? 'checked' : '' ?>><span data-i18n="regfull_no">No</span></label>
          <input type="search" name="awards_detail" class="detail" value="<?= esc_attr($companyDataJson['competitiveness']['awards_detail'] ?? '') ?>">
        </div>
      </div>
      <!-- 4) Ferias -->
      <div class="label"><label data-i18n="regfull_fairs">Ferias <span class="req">*</span></label></div>
      <div class="field">
        <div class="yesno_line">
          <?php $fairs = $companyDataJson['competitiveness']['fairs'] ?? ''; ?>
          <label class="yn"><input type="radio" name="fairs" value="si" <?= $fairs === 'si' ? 'checked' : '' ?>><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="fairs" value="no" <?= $fairs === 'no' ? 'checked' : '' ?>><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
      <!-- 5) Rondas -->
      <div class="label"><label data-i18n="regfull_rounds">Rondas <span class="req">*</span></label></div>
      <div class="field">
        <div class="yesno_line">
          <?php $rounds = $companyDataJson['competitiveness']['rounds'] ?? ''; ?>
          <label class="yn"><input type="radio" name="rounds" value="si" <?= $rounds === 'si' ? 'checked' : '' ?>><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="rounds" value="no" <?= $rounds === 'no' ? 'checked' : '' ?>><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
      <!-- 6) Experiencia Exportadora previa -->
      <div class="label"><label data-i18n="regfull_export_experience">Experiencia Exportadora previa <span class="req">*</span></label></div>
      <div class="field">
        <div class="custom-dropdown">
          <div class="dropdown-selected">
            <span class="selected-text" data-i18n="regfull_dropdown_placeholder">…</span>
            <span class="dropdown-arrow">▼</span>
          </div>
          <div class="dropdown-options">
            <div class="dropdown-option" data-value="" data-i18n="regfull_dropdown_placeholder">…</div>
            <div class="dropdown-option" data-value="Sí, ya exportamos regularmente">Sí, ya exportamos regularmente</div>
            <div class="dropdown-option" data-value="Hemos exportado ocasionalmente">Hemos exportado ocasionalmente</div>
            <div class="dropdown-option" data-value="Nunca exportamos">Nunca exportamos</div>
          </div>
          <input type="hidden" name="export_experience" value="<?= esc_attr($companyDataJson['competitiveness']['export_experience'] ?? '') ?>">
        </div>
      </div>
      <!-- 7) Referencias comerciales -->
      <div class="label"><label data-i18n="regfull_commercial_references">Referencias comerciales <span class="req">*</span></label></div>
      <div class="field"><textarea name="commercial_references" class="ta" rows="4"><?= esc_attr($companyDataJson['competitiveness']['commercial_references'] ?? '') ?></textarea></div>
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
    <img src="img/icons/regfull_visual.svg">
  </div>
  <div class="form" novalidate>
    <div class="label"><span data-i18n="regfull_company_logo">Adjuntar Logo de la Empresa <span class="req">*</span></span></div>
    <div class="field visual-row">
      <div class="files-list" data-ph="subir archivo (JPG, PNG, PDF)" data-accept="image/jpeg,image/png,application/pdf" data-name="company_logo[]">
        <div class="file-item">
          <div class="file-ph-wrapper">
          <input type="file" class="file-ph" name="company_logo[]" accept="image/jpeg,image/png,application/pdf" data-i18n-placeholder="regfull_upload_file" placeholder="subir archivo (JPG, PNG, PDF)">
            <div class="file-ph-display">
              <img class="file-preview-img" src="" alt="" style="display: none;">
              <span class="file-ph-placeholder" data-i18n="regfull_upload_file">subir archivo (JPG, PNG, PDF)</span>
              <button type="button" class="file-ph-remove" style="display: none;" aria-label="Eliminar">&times;</button>
            </div>
          </div>
          <button type="button" class="remove" aria-label="Eliminar" data-i18n-aria-label="regfull_remove" hidden>&times;</button>
        </div>
      </div>
      <button type="button" class="add_more" data-i18n="regfull_add_more">agregar más</button>
    </div>
    <div class="label"><span data-i18n="regfull_process_photos">Adjuntar Fotos de los Procesos/Servicios <span class="req">*</span></span></div>
    <div class="field visual-row">
      <div class="files-list" data-ph="subir archivo (JPG, PNG, PDF)" data-accept="image/jpeg,image/png,application/pdf" data-name="process_photos[]">
        <div class="file-item">
          <div class="file-ph-wrapper">
          <input type="file" class="file-ph" name="process_photos[]" accept="image/jpeg,image/png,application/pdf" data-i18n-placeholder="regfull_upload_file" placeholder="subir archivo (JPG, PNG, PDF)">
            <div class="file-ph-display">
              <img class="file-preview-img" src="" alt="" style="display: none;">
              <span class="file-ph-placeholder" data-i18n="regfull_upload_file">subir archivo (JPG, PNG, PDF)</span>
              <button type="button" class="file-ph-remove" style="display: none;" aria-label="Eliminar">&times;</button>
            </div>
          </div>
          <button type="button" class="remove" aria-label="Eliminar" data-i18n-aria-label="regfull_remove" hidden>&times;</button>
        </div>
      </div>
      <button type="button" class="add_more" data-i18n="regfull_add_more">agregar más</button>
    </div>
    <div class="label"><span data-i18n="regfull_digital_catalog">Adjuntar Catálogo Digital (si existe)</span></div>
    <div class="field visual-row">
      <div class="files-list" data-ph="subir archivo (JPG, PNG, PDF)" data-accept="image/jpeg,image/png,application/pdf" data-name="digital_catalog[]">
        <div class="file-item">
          <div class="file-ph-wrapper">
          <input type="file" class="file-ph" name="digital_catalog[]" accept="image/jpeg,image/png,application/pdf" data-i18n-placeholder="regfull_upload_file" placeholder="subir archivo (JPG, PNG, PDF)">
            <div class="file-ph-display">
              <img class="file-preview-img" src="" alt="" style="display: none;">
              <span class="file-ph-placeholder" data-i18n="regfull_upload_file">subir archivo (JPG, PNG, PDF)</span>
              <button type="button" class="file-ph-remove" style="display: none;" aria-label="Eliminar">&times;</button>
            </div>
          </div>
          <button type="button" class="remove" aria-label="Eliminar" data-i18n-aria-label="regfull_remove" hidden>&times;</button>
        </div>
      </div>
      <button type="button" class="add_more" data-i18n="regfull_add_more">agregar más</button>
    </div>
    <div class="label"><span data-i18n="regfull_institutional_video">Adjuntar Video Institucional (si existe)</span></div>
    <div class="field visual-row">
      <div class="files-list" data-ph="subir archivo (MP4, MKV, AVI)" data-accept="video/mp4,video/x-matroska,video/x-msvideo" data-name="institutional_video">
        <div class="file-item">
          <div class="file-ph-wrapper">
          <input type="file" class="file-ph" name="institutional_video" accept="video/mp4,video/x-matroska,video/x-msvideo" data-i18n-placeholder="regfull_upload_video" placeholder="subir archivo (MP4, MKV, AVI)">
            <div class="file-ph-display">
              <video class="file-preview-img" src="" style="display: none;" controls preload="metadata"></video>
              <img class="file-preview-img" src="" alt="" style="display: none;">
              <span class="file-ph-placeholder" data-i18n="regfull_upload_video">subir archivo (MP4, MKV, AVI)</span>
              <button type="button" class="file-ph-remove" style="display: none;" aria-label="Eliminar">&times;</button>
            </div>
          </div>
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
    const acceptAttr = list.getAttribute('data-accept') || 'image/jpeg,image/png,application/pdf';
    const nameAttr = list.getAttribute('data-name') || 'file[]';
    const isVideo = nameAttr === 'institutional_video';
    
    function updateRemoveButtons(){
      const items = list.querySelectorAll('.file-item');
      items.forEach((it, i) => {
        const rm = it.querySelector('.remove');
        if (!rm) return;
        rm.hidden = (i === 0);
      });
    }
    
    function updateAddButtons(){
      const items = list.querySelectorAll('.file-item');
      if (addBtn) {
        // Показывать кнопку только у последнего элемента (кроме видео - там только один)
        addBtn.hidden = (items.length === 0 || (isVideo && items.length > 0));
        if (!isVideo && items.length > 0) {
          // Переместить кнопку после последнего элемента
          const lastItem = items[items.length - 1];
          if (lastItem.nextSibling !== addBtn) {
            lastItem.after(addBtn);
          }
        }
      }
    }
    
    function bindFileInputEvents(item) {
      const fileInput = item.querySelector('input.file-ph');
      const fileDisplay = item.querySelector('.file-ph-display');
      const fileRemove = item.querySelector('.file-ph-remove');
      
      if (!fileInput || !fileDisplay) return;
      
      // Клик по display открывает выбор файла
      if (!fileDisplay._bound) {
        fileDisplay.addEventListener('click', (e) => {
          if (e.target === fileRemove || e.target.closest('.file-ph-remove')) {
            return;
          }
          fileInput.click();
        });
        fileDisplay._bound = true;
      }
      
      // Обработка выбора файла
      if (!fileInput._bound) {
        fileInput.addEventListener('change', (e) => {
          if (e.target.files && e.target.files.length > 0) {
            const file = e.target.files[0];
            if (file.type.startsWith('image/')) {
              const reader = new FileReader();
              reader.onload = (event) => {
                if (window.updateFileDisplay) {
                  window.updateFileDisplay(fileInput, event.target.result, file.name);
                }
              };
              reader.readAsDataURL(file);
            } else if (file.type.startsWith('video/')) {
              const reader = new FileReader();
              reader.onload = (event) => {
                if (window.updateFileDisplay) {
                  window.updateFileDisplay(fileInput, event.target.result, file.name);
                }
              };
              reader.readAsDataURL(file);
            } else {
              if (window.updateFileDisplay) {
                window.updateFileDisplay(fileInput, null, file.name);
              }
            }
          }
        });
        fileInput._bound = true;
      }
      
      // Обработка удаления файла
      if (fileRemove && !fileRemove._bound) {
        fileRemove.addEventListener('click', (e) => {
          e.stopPropagation();
          fileInput.value = '';
          if (window.updateFileDisplay) {
            window.updateFileDisplay(fileInput, null, null);
          }
        });
        fileRemove._bound = true;
      }
    }
    
    function bindRemove(btn, item){
      if (btn._bound) return;
      btn.addEventListener('click', () => {
        item.remove();
        updateRemoveButtons();
        updateAddButtons();
      });
      btn._bound = true;
    }
    
    if (addBtn && !addBtn._bound){
      addBtn.addEventListener('click', () => {
        const item = document.createElement('div');
        item.className = 'file-item';
        
        const wrapper = document.createElement('div');
        wrapper.className = 'file-ph-wrapper';
        
        const input = document.createElement('input');
        input.type = 'file';
        input.className = 'file-ph';
        input.accept = acceptAttr;
        input.name = nameAttr;
        
        const display = document.createElement('div');
        display.className = 'file-ph-display';
        
        if (isVideo) {
          const video = document.createElement('video');
          video.className = 'file-preview-img';
          video.style.display = 'none';
          video.controls = true;
          video.preload = 'metadata';
          display.appendChild(video);
        }
        
        const img = document.createElement('img');
        img.className = 'file-preview-img';
        img.style.display = 'none';
        img.alt = '';
        display.appendChild(img);
        
        const placeholder = document.createElement('span');
        placeholder.className = 'file-ph-placeholder';
        placeholder.textContent = ph;
        display.appendChild(placeholder);
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'file-ph-remove';
        removeBtn.style.display = 'none';
        removeBtn.setAttribute('aria-label', 'Eliminar');
        removeBtn.textContent = '×';
        display.appendChild(removeBtn);
        
        wrapper.appendChild(input);
        wrapper.appendChild(display);
        item.appendChild(wrapper);
        
        const rm = document.createElement('button');
        rm.type = 'button';
        rm.className = 'remove';
        rm.setAttribute('aria-label', 'Eliminar');
        rm.textContent = '×';
        item.appendChild(rm);
        
        list.appendChild(item);
        bindFileInputEvents(item);
        bindRemove(rm, item);
        updateRemoveButtons();
        updateAddButtons();
      });
      addBtn._bound = true;
    }
    
    // Привязать обработчики к существующим элементам
    list.querySelectorAll('.file-item').forEach((item) => {
      bindFileInputEvents(item);
      const rm = item.querySelector('.remove');
      if (rm) bindRemove(rm, item);
    });
    
    updateRemoveButtons();
    updateAddButtons();
  });
});
</script>
<!-- visual -->

<!-- logistica -->
<div class="datos">
  <div class="datos_header">
    <h1 data-i18n="regfull_section6_title">6. Logística y Distribución</h1>
    <img src="img/icons/regfull_logistica.svg">
  </div>
  <div class="form" novalidate>
    <div class="label"><span data-i18n="regfull_export_capacity">Capacidad de Exportación Inmediata <span class="req">*</span></span></div>
    <div class="field logi-right">
      <div class="cap-row">
        <div class="yn-line">
          <?php 
          $exportCapacity = '';
          if (isset($companyDataJson['logistics']) && is_array($companyDataJson['logistics']) && isset($companyDataJson['logistics']['export_capacity'])) {
              $exportCapacity = $companyDataJson['logistics']['export_capacity'];
          }
          ?>
          <label class="yn"><input type="radio" name="export_capacity" value="si" <?= $exportCapacity === 'si' ? 'checked' : '' ?>><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="export_capacity" value="no" <?= $exportCapacity === 'no' ? 'checked' : '' ?>><span data-i18n="regfull_no">No</span></label>
        </div>
        <label class="lbl plazo-lbl" data-i18n="regfull_estimated_term">Plazo estimado <span class="req">*</span></label>
        <?php 
        $estimatedTerm = '';
        if (isset($companyDataJson['logistics']) && is_array($companyDataJson['logistics']) && isset($companyDataJson['logistics']['estimated_term'])) {
            $estimatedTerm = $companyDataJson['logistics']['estimated_term'];
        }
        ?>
        <input type="text" name="estimated_term" class="fld plazo" data-i18n-placeholder="regfull_months" placeholder="meses" value="<?= esc_attr($estimatedTerm) ?>">
      </div>
    </div>
    <div class="label"><span data-i18n="regfull_logistics_infrastructure">Infraestructura Logística Disponible <span class="req">*</span></span></div>
    <div class="field">
      <input type="search" name="logistics_infrastructure" data-i18n-placeholder="regfull_logistics_placeholder" placeholder="ejemplo: frigoríficos, transporte propio, alianzas logísticas, etc." value="<?= esc_attr($companyDataJson['logistics']['logistics_infrastructure'] ?? '') ?>">
    </div>
    <div class="label"><span data-i18n="regfull_ports_airports">Puertos/Aeropuertos de Salida habituales o posibles <span class="req">*</span></span></div>
    <div class="field"><textarea name="ports_airports" class="ta" rows="4"><?= esc_attr($companyDataJson['logistics']['ports_airports'] ?? '') ?></textarea></div>
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
    <img src="img/icons/regfull_expectivas.svg">
  </div>
  <div class="form" novalidate>
    <div class="needs-blk">
      <div class="label">
        <span data-i18n="regfull_export_needs">Principales Necesidades para mejorar capacidad exportadora <span class="req">*</span></span>
      </div>
      <div class="field">
        <div class="needs-grid">
          <?php 
          $needs = $companyDataJson['needs'] ?? [];
          $otherNeeds = $companyDataJson['expectations']['other_needs'] ?? '';
          ?>
          <label class="chk"><input type="checkbox" name="needs[]" value="Capacitación" <?= in_array('Capacitación', $needs) ? 'checked' : '' ?>><span data-i18n="regfull_training">Capacitación</span></label>
          <label class="chk"><input type="checkbox" name="needs[]" value="Acceso a ferias" <?= in_array('Acceso a ferias', $needs) ? 'checked' : '' ?>><span data-i18n="regfull_fair_access">Acceso a ferias</span></label>
          <label class="chk"><input type="checkbox" name="needs[]" value="Certificaciones" <?= in_array('Certificaciones', $needs) ? 'checked' : '' ?>><span data-i18n="regfull_certifications_need">Certificaciones</span></label>
          <label class="chk"><input type="checkbox" name="needs[]" value="Financiamiento" <?= in_array('Financiamiento', $needs) ? 'checked' : '' ?>><span data-i18n="regfull_financing">Financiamiento</span></label>
          <label class="chk"><input type="checkbox" name="needs[]" value="Socios comerciales" <?= in_array('Socios comerciales', $needs) ? 'checked' : '' ?>><span data-i18n="regfull_commercial_partners">Socios comerciales</span></label>
          <div class="other">
            <label class="chk"><input type="checkbox" name="needs[]" value="Otros" class="otros-cb" <?= in_array('Otros', $needs) ? 'checked' : '' ?>><span data-i18n="regfull_others">Otros</span></label>
            <input name="other_needs" class="fld otros-inp" type="text" placeholder="" value="<?= esc_attr($otherNeeds) ?>" <?= in_array('Otros', $needs) ? '' : 'disabled' ?>>
          </div>
        </div>
      </div>
      <div class="label">
        <label data-i18n="regfull_interest_participate">Interés en Participar de Misiones Comerciales/Ferias Internacionales <span class="req">*</span></label>
      </div>
      <div class="field">
        <div class="yn-line">
          <?php $interestParticipate = $companyDataJson['expectations']['interest_participate'] ?? ''; ?>
          <label class="yn"><input type="radio" name="interest_participate" value="si" <?= $interestParticipate === 'si' ? 'checked' : '' ?>><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="interest_participate" value="no" <?= $interestParticipate === 'no' ? 'checked' : '' ?>><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
      <div class="label">
        <label data-i18n="regfull_training_availability">Disponibilidad para Capacitaciones y Asistencia Técnica <span class="req">*</span></label>
      </div>
      <div class="field">
        <div class="yn-line">
          <?php $trainingAvailability = $companyDataJson['expectations']['training_availability'] ?? ''; ?>
          <label class="yn"><input type="radio" name="training_availability" value="si" <?= $trainingAvailability === 'si' ? 'checked' : '' ?>><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="training_availability" value="no" <?= $trainingAvailability === 'no' ? 'checked' : '' ?>><span data-i18n="regfull_no">No</span></label>
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
    <img src="img/icons/regfull_validacion.svg">
  </div>
  <div class="form" novalidate>
    <div class="consent-blk">
      <div class="consent-row">
        <div class="consent-text" data-i18n="regfull_authorization_platform">
          Autorización para Difundir la Información Cargada en la Plataforma Provincial <span class="req">*</span>
        </div>
        <div class="yn-line">
          <?php $authorizationPublish = $companyDataJson['consents']['authorization_publish'] ?? ''; ?>
          <label class="yn"><input type="radio" name="authorization_publish" value="si" <?= $authorizationPublish === 'si' ? 'checked' : '' ?>><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="authorization_publish" value="no" <?= $authorizationPublish === 'no' ? 'checked' : '' ?>><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
      <div class="consent-row">
        <div class="consent-text" data-i18n="regfull_authorization_publication">
          Autorizo la Publicación de mi Información para Promoción Exportadora <span class="req">*</span>
        </div>
        <div class="yn-line">
          <?php $authorizationPublication = $companyDataJson['consents']['authorization_publication'] ?? ''; ?>
          <label class="yn"><input type="radio" name="authorization_publication" value="si" <?= $authorizationPublication === 'si' ? 'checked' : '' ?>><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="authorization_publication" value="no" <?= $authorizationPublication === 'no' ? 'checked' : '' ?>><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
      <div class="consent-row">
        <div class="consent-text" data-i18n="regfull_accept_contact">
          Acepto ser Contactado por Organismos de Promoción y Compradores Internacionales <span class="req">*</span>
        </div>
        <div class="yn-line">
          <?php $acceptContact = $companyDataJson['consents']['accept_contact'] ?? ''; ?>
          <label class="yn"><input type="radio" name="accept_contact" value="si" <?= $acceptContact === 'si' ? 'checked' : '' ?>><span data-i18n="regfull_yes">Si</span></label>
          <label class="yn"><input type="radio" name="accept_contact" value="no" <?= $acceptContact === 'no' ? 'checked' : '' ?>><span data-i18n="regfull_no">No</span></label>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Submit Button -->
<div style="text-align: center; margin: 40px 0;">
  <button type="button" class="btn btn-save-register" id="btnSaveRegister" data-i18n="regfull_save_register">Guardar</button>
  <div id="regfull_message" style="margin-top: 15px; display: none;"></div>
</div>

<script>
// Глобальные функции для работы с localStorage
// Привязываем ключ к ID пользователя
const userId = window.currentUserId || 0;
const STORAGE_KEY = userId > 0 ? `regfull_form_data_${userId}` : 'regfull_form_data';

function quickSave() {
  const userId = window.currentUserId || 0;
  const STORAGE_KEY = userId > 0 ? `regfull_form_data_${userId}` : 'regfull_form_data';
  const formData = {};
  
  // Текстовые поля (включая массивы)
  document.querySelectorAll('input[type="text"], input[type="search"], input:not([type]), input[type="email"], input[type="url"], textarea').forEach(field => {
    if (field.type !== 'file' && !field.hidden && field.name) {
      const value = field.value.trim();
      if (value) {
        if (field.name.includes('[]')) {
          // Массивы
          const key = field.name;
          if (!formData[key]) {
            formData[key] = [];
          }
          formData[key].push(value);
        } else {
          formData[field.name] = value;
        }
      }
    }
  });
  
  // Скрытые поля (dropdown'ы)
  document.querySelectorAll('input[type="hidden"]').forEach(field => {
    if (field.name) {
      if (field.name.includes('[]')) {
        const key = field.name;
        if (!formData[key]) {
          formData[key] = [];
        }
        if (field.value && field.value !== '…') {
          formData[key].push(field.value);
        }
      } else {
      formData[field.name] = field.value;
      }
      
      const dropdown = field.closest('.custom-dropdown');
      if (dropdown) {
        const selectedText = dropdown.querySelector('.selected-text');
        if (selectedText && selectedText.textContent && selectedText.textContent !== '…') {
          formData[field.name + '_text'] = selectedText.textContent;
        }
      }
    }
  });
  
  // Радио-кнопки
  document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
    if (radio.name) {
      formData[radio.name] = radio.value;
    }
  });
  
  // Чекбоксы
  const checkboxValues = {};
  document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
    if (checkbox.name) {
      if (!checkboxValues[checkbox.name]) {
        checkboxValues[checkbox.name] = [];
      }
      if (checkbox.checked) {
      checkboxValues[checkbox.name].push(checkbox.value || 'checked');
      }
    }
  });
  Object.assign(formData, checkboxValues);
  
  // Специальные поля
  document.querySelectorAll('input[name="estimated_term"]').forEach(field => {
    if (field.value || field.value === '0') {
      formData['estimated_term'] = field.value;
    }
  });
  
  document.querySelectorAll('input[name="other_differentiation"]').forEach(field => {
    if (field.value && field.value.trim()) {
      formData['other_differentiation'] = field.value.trim();
    }
  });
  
  document.querySelectorAll('input[name="other_needs"]').forEach(field => {
    if (field.value && field.value.trim()) {
      formData['other_needs'] = field.value.trim();
    }
  });
  
  // Количество продуктов
  const productItems = document.querySelectorAll('.product-item');
  if (productItems.length > 0) {
    formData['_products_count'] = productItems.length;
    productItems.forEach((item, index) => {
      const nameInput = item.querySelector('input[name="product_name[]"]');
      const descInput = item.querySelector('input[name="product_description[]"]');
      const exportInput = item.querySelector('input[name="annual_export[]"]');
      if (nameInput) formData[`_product_${index}_name`] = nameInput.value.trim();
      if (descInput) formData[`_product_${index}_description`] = descInput.value.trim();
      if (exportInput) formData[`_product_${index}_export`] = exportInput.value.trim();
    });
  }
  
  // Количество социальных сетей
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
  
  // Количество рынков интереса
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
  
  localStorage.setItem(STORAGE_KEY, JSON.stringify(formData));
}

// Обработчик отправки формы
document.addEventListener('DOMContentLoaded', () => {
  const btnSave = document.getElementById('btnSaveRegister');
  const msgEl = document.getElementById('regfull_message');
  
  if (btnSave) {
    btnSave.addEventListener('click', async function() {
      quickSave();
      
      // Используем тот же ключ, что и в основном скрипте
      const userId = window.currentUserId || 0;
      const STORAGE_KEY = userId > 0 ? `regfull_form_data_${userId}` : 'regfull_form_data';
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
      
      const checkFile = (inputName, fileType, label) => {
        const input = document.querySelector(`input[name="${inputName}"]`);
        const fileState = window.getFileState ? window.getFileState() : { existingFiles: {}, newFiles: {} };
        
        let hasFile = false;
        
        if (input && input.files && input.files.length > 0) {
          hasFile = true;
        }
        
        if (!hasFile) {
          const existingFiles = fileState.existingFiles[fileType];
          if (existingFiles) {
            if (Array.isArray(existingFiles) && existingFiles.length > 0) {
              hasFile = true;
            } else if (typeof existingFiles === 'object' && !Array.isArray(existingFiles)) {
              const hasAnyFile = Object.keys(existingFiles).some(key => {
                const files = existingFiles[key];
                return Array.isArray(files) && files.length > 0;
              });
              if (hasAnyFile) hasFile = true;
            }
          }
        }
        
        if (!hasFile) {
          const newFiles = fileState.newFiles;
          for (const key in newFiles) {
            if (key === fileType || key.startsWith(fileType + '_')) {
              hasFile = true;
              break;
            }
          }
        }
        
        if (!hasFile && input) {
          const container = input.closest('.file-item') || input.closest('.producto_grid') || input.parentElement;
          const preview = container?.querySelector('.file-preview');
          if (preview) {
            hasFile = true;
          }
        }
        
        if (!hasFile) {
          errors.push(label);
          if (input) {
            input.style.borderColor = '#f44336';
            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
          return false;
        }
        
        if (input) {
          input.style.borderColor = '';
        }
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
      
      // Валидация продуктов и услуг (только если они добавлены)
      const allItems = document.querySelectorAll('.product-item, .service-item');
      
      if (allItems.length > 0) {
        // Валидация продуктов
        const productItems = document.querySelectorAll('.product-item');
        productItems.forEach((item, index) => {
        const productName = item.querySelector('input[name="product_name[]"]');
        const productDesc = item.querySelector('input[name="product_description[]"]');
        const productPhoto = item.querySelector('input[name="product_photo[]"]');
        
        if (!productName || !productName.value || !productName.value.trim()) {
          errors.push(`Producto ${index + 1}: Nombre es obligatorio`);
          if (productName) {
            productName.style.borderColor = '#f44336';
            productName.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        } else {
          if (productName) productName.style.borderColor = '';
        }
        
        if (!productDesc || !productDesc.value || !productDesc.value.trim()) {
          errors.push(`Producto ${index + 1}: Descripción es obligatoria`);
          if (productDesc) {
            productDesc.style.borderColor = '#f44336';
            productDesc.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        } else {
          if (productDesc) productDesc.style.borderColor = '';
        }
        
        // Проверка файла продукта
        const fileState = window.getFileState ? window.getFileState() : { existingFiles: {}, newFiles: {} };
        let hasFile = false;
        
        // Проверка выбранного файла в input
        if (productPhoto && productPhoto.files && productPhoto.files.length > 0) {
          hasFile = true;
        }
        
        // Проверка preview в новой структуре (.file-ph-display)
        if (!hasFile) {
          const fileDisplay = item.querySelector('.file-ph-display');
          if (fileDisplay) {
            const img = fileDisplay.querySelector('img.file-preview-img');
            const video = fileDisplay.querySelector('video.file-preview-img');
            if (img && img.src && img.style.display !== 'none') {
              hasFile = true;
            } else if (video && video.src && video.style.display !== 'none') {
              hasFile = true;
            } else if (fileDisplay.classList.contains('has-image')) {
              hasFile = true;
            }
          }
        }
        
        // Проверка старой структуры preview (для обратной совместимости)
        if (!hasFile) {
          const preview = item.querySelector('.file-preview');
          if (preview && preview.style.display !== 'none') {
            hasFile = true;
          }
        }
        
        // Проверка существующих файлов
        if (!hasFile) {
          // Проверяем разные форматы ключей
          const fileKey1 = index === 0 ? 'product_photo' : `product_photo_index_${index}`;
          const fileKey2 = `index_${index}`;
          
          // Проверка по основному ключу
          let existingFiles = fileState.existingFiles[fileKey1];
          if (!existingFiles && fileState.existingFiles['product_photo']) {
            const productPhotoData = fileState.existingFiles['product_photo'];
            // Если это объект с индексами
            if (typeof productPhotoData === 'object' && !Array.isArray(productPhotoData)) {
              existingFiles = productPhotoData[fileKey2] || productPhotoData[index] || productPhotoData[String(index)];
            } else if (Array.isArray(productPhotoData) && index < productPhotoData.length) {
              existingFiles = productPhotoData[index];
            } else if (index === 0 && productPhotoData) {
              existingFiles = Array.isArray(productPhotoData) ? productPhotoData[0] : productPhotoData;
            }
          }
          
          if (existingFiles) {
            if (Array.isArray(existingFiles) && existingFiles.length > 0) {
              hasFile = true;
            } else if (existingFiles && existingFiles.id) {
              hasFile = true;
            } else if (existingFiles && existingFiles.url) {
              hasFile = true;
            }
          }
        }
        
        // Проверка новых файлов
        if (!hasFile) {
          const newFiles = fileState.newFiles;
          for (const key in newFiles) {
            if (key === 'product_photo' && index === 0) {
              hasFile = true;
              break;
            } else if (key === `product_photo_index_${index}`) {
              hasFile = true;
              break;
            } else if (key.startsWith('product_photo_index_') && key.endsWith(`_${index}`)) {
              hasFile = true;
              break;
            }
          }
        }
        
        if (!hasFile && productPhoto) {
          errors.push(`Producto ${index + 1}: Foto del Producto es obligatoria`);
          const fileDisplay = item.querySelector('.file-ph-display');
          if (fileDisplay) {
            fileDisplay.style.borderColor = '#f44336';
            fileDisplay.scrollIntoView({ behavior: 'smooth', block: 'center' });
          } else {
            productPhoto.style.borderColor = '#f44336';
            productPhoto.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        } else {
          if (productPhoto) productPhoto.style.borderColor = '';
          const fileDisplay = item.querySelector('.file-ph-display');
          if (fileDisplay) fileDisplay.style.borderColor = '';
        }
        
        // Проверка Mercados Actuales для продукта
        const productCurrentMarkets = item.querySelector('input[name="product_current_markets[]"]');
        if (productCurrentMarkets) {
          const dropdown = productCurrentMarkets.closest('.custom-dropdown');
          if (!productCurrentMarkets.value || productCurrentMarkets.value === '' || productCurrentMarkets.value === '…') {
            errors.push(`Producto ${index + 1}: Mercados Actuales es obligatorio`);
            if (dropdown) {
              dropdown.style.boxShadow = '0 0 0 2px #f44336';
              dropdown.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
          } else {
            if (dropdown) dropdown.style.boxShadow = '';
          }
        }
      });
      
      // Валидация услуг
      const serviceItems = document.querySelectorAll('.service-item');
      serviceItems.forEach((item, index) => {
        const serviceActivity = item.querySelector('input[name="service_activity[]"]');
        const serviceName = item.querySelector('input[name="service_name[]"]');
        const serviceDesc = item.querySelector('input[name="service_description[]"]');
        const servicePhoto = item.querySelector('input[name="service_photo[]"]');
        
        if (!serviceActivity || !serviceActivity.value || serviceActivity.value === '' || serviceActivity.value === '…') {
          errors.push(`Servicio ${index + 1}: Actividad es obligatoria`);
          const dropdown = serviceActivity?.closest('.custom-dropdown');
          if (dropdown) {
            dropdown.style.boxShadow = '0 0 0 2px #f44336';
            dropdown.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        } else {
          const dropdown = serviceActivity.closest('.custom-dropdown');
          if (dropdown) dropdown.style.boxShadow = '';
        }
        
        if (!serviceName || !serviceName.value || !serviceName.value.trim()) {
          errors.push(`Servicio ${index + 1}: Nombre es obligatorio`);
          if (serviceName) {
            serviceName.style.borderColor = '#f44336';
            serviceName.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        } else {
          if (serviceName) serviceName.style.borderColor = '';
        }
        
        if (!serviceDesc || !serviceDesc.value || !serviceDesc.value.trim()) {
          errors.push(`Servicio ${index + 1}: Descripción es obligatoria`);
          if (serviceDesc) {
            serviceDesc.style.borderColor = '#f44336';
            serviceDesc.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        } else {
          if (serviceDesc) serviceDesc.style.borderColor = '';
        }
        
        // Проверка файла услуги (аналогично продукту)
        const fileState = window.getFileState ? window.getFileState() : { existingFiles: {}, newFiles: {} };
        let hasFile = false;
        
        if (servicePhoto && servicePhoto.files && servicePhoto.files.length > 0) {
          hasFile = true;
        }
        
        if (!hasFile) {
          const fileDisplay = item.querySelector('.file-ph-display');
          if (fileDisplay) {
            const img = fileDisplay.querySelector('img.file-preview-img');
            const video = fileDisplay.querySelector('video.file-preview-img');
            if (img && img.src && img.style.display !== 'none') {
              hasFile = true;
            } else if (video && video.src && video.style.display !== 'none') {
              hasFile = true;
            } else if (fileDisplay.classList.contains('has-image')) {
              hasFile = true;
            }
          }
        }
        
        if (!hasFile) {
          const fileKey = index === 0 ? 'service_photo' : `service_photo_index_${index}`;
          let existingFiles = fileState.existingFiles[fileKey];
          if (!existingFiles && fileState.existingFiles['service_photo']) {
            const servicePhotoData = fileState.existingFiles['service_photo'];
            if (typeof servicePhotoData === 'object' && !Array.isArray(servicePhotoData)) {
              existingFiles = servicePhotoData[`index_${index}`] || servicePhotoData[index] || servicePhotoData[String(index)];
            } else if (Array.isArray(servicePhotoData) && index < servicePhotoData.length) {
              existingFiles = servicePhotoData[index];
            } else if (index === 0 && servicePhotoData) {
              existingFiles = Array.isArray(servicePhotoData) ? servicePhotoData[0] : servicePhotoData;
            }
          }
          
          if (existingFiles) {
            if (Array.isArray(existingFiles) && existingFiles.length > 0) {
              hasFile = true;
            } else if (existingFiles && existingFiles.id) {
              hasFile = true;
            } else if (existingFiles && existingFiles.url) {
              hasFile = true;
            }
          }
        }
        
        if (!hasFile) {
          const newFiles = fileState.newFiles;
          for (const key in newFiles) {
            if (key === 'service_photo' && index === 0) {
              hasFile = true;
              break;
            } else if (key === `service_photo_index_${index}`) {
              hasFile = true;
              break;
            }
          }
        }
        
        if (!hasFile && servicePhoto) {
          errors.push(`Servicio ${index + 1}: Foto del Servicio es obligatoria`);
          const fileDisplay = item.querySelector('.file-ph-display');
          if (fileDisplay) {
            fileDisplay.style.borderColor = '#f44336';
            fileDisplay.scrollIntoView({ behavior: 'smooth', block: 'center' });
          } else {
            servicePhoto.style.borderColor = '#f44336';
            servicePhoto.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        } else {
          if (servicePhoto) servicePhoto.style.borderColor = '';
          const fileDisplay = item.querySelector('.file-ph-display');
          if (fileDisplay) fileDisplay.style.borderColor = '';
        }
        
        // Проверка Mercados Actuales для услуги
        const serviceCurrentMarkets = item.querySelector('input[name="service_current_markets[]"]');
        if (serviceCurrentMarkets) {
          const dropdown = serviceCurrentMarkets.closest('.custom-dropdown');
          if (!serviceCurrentMarkets.value || serviceCurrentMarkets.value === '' || serviceCurrentMarkets.value === '…') {
            errors.push(`Servicio ${index + 1}: Mercados Actuales es obligatorio`);
            if (dropdown) {
              dropdown.style.boxShadow = '0 0 0 2px #f44336';
              dropdown.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
          } else {
            if (dropdown) dropdown.style.boxShadow = '';
          }
        }
      });
      }
      
      // Удалена проверка общего поля current_markets, так как теперь оно внутри каждого продукта/услуги
      
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
      
      checkFile('company_logo[]', 'logo', 'Logo de la Empresa');
      
      checkFile('process_photos[]', 'process_photo', 'Fotos de los Procesos/Servicios');
      
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
          btnSave.textContent = btnSave.getAttribute('data-i18n') ? btnSave.textContent : 'Guardar';
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
        
        document.querySelectorAll('input[type="text"], input[type="search"], input[type="email"], input[type="url"], input:not([type]), textarea').forEach(field => {
          if (field.type !== 'file' && !field.hidden && field.name) {
            // Для estimated_term отправляем даже если значение пустое
            if (field.name === 'estimated_term') {
              appendToFormData(field.name, field.value || '');
            } else if (field.value) {
            appendToFormData(field.name, field.value);
            }
          }
        });
        
        // Собираем target_markets для каждого продукта отдельно
        document.querySelectorAll('.product-item').forEach((item, productIndex) => {
          const targetMarketsInputs = item.querySelectorAll('input[name="product_target_markets[]"]');
          targetMarketsInputs.forEach(input => {
            if (input.value && input.value.trim() && input.value.trim() !== '…') {
              // Отправляем с индексом продукта для правильной обработки на сервере
              appendToFormData(`product_target_markets[${productIndex}][]`, input.value);
            }
          });
        });
        
        // Собираем target_markets для каждой услуги отдельно
        document.querySelectorAll('.service-item').forEach((item, serviceIndex) => {
          const targetMarketsInputs = item.querySelectorAll('input[name="service_target_markets[]"]');
          targetMarketsInputs.forEach(input => {
            if (input.value && input.value.trim() && input.value.trim() !== '…') {
              // Отправляем с индексом услуги для правильной обработки на сервере
              appendToFormData(`service_target_markets[${serviceIndex}][]`, input.value);
            }
          });
        });
        
        document.querySelectorAll('input[type="hidden"]').forEach(field => {
          if (field.name) {
            // Пропускаем product_target_markets[] и service_target_markets[] - они уже обработаны выше
            if (field.name === 'product_target_markets[]' || field.name === 'service_target_markets[]') {
              return;
            }
            
            // Для main_activity отправляем только валидные значения
            if (field.name === 'main_activity') {
              // Проверяем и value, и атрибут value, и также проверяем визуальное отображение
              let value = (field.value || field.getAttribute('value') || '').trim();
              
              // Если значение пустое, проверяем визуальное отображение dropdown
              if (!value || value === '') {
                const dropdown = field.closest('.custom-dropdown');
                if (dropdown) {
                  const selectedOption = dropdown.querySelector('.dropdown-option.selected');
                  if (selectedOption && selectedOption.dataset.value) {
                    value = selectedOption.dataset.value.trim();
                  }
                }
              }
              
              // Всегда отправляем значение, даже если оно пустое (но не "0" или "…")
              if (value !== '0' && value !== '…') {
                appendToFormData(field.name, value);
                // Отладка
                console.log('Sending main_activity:', value, 'from field.value:', field.value, 'from attribute:', field.getAttribute('value'));
              } else {
                console.log('Skipping main_activity (invalid value):', value);
              }
            } else if (field.value) {
              appendToFormData(field.name, field.value);
            }
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
        
        const fileState = window.getFileState ? window.getFileState() : { existingFiles: {}, newFiles: {} };
        
        Object.keys(fileState.newFiles).forEach(fileKey => {
          const newFile = fileState.newFiles[fileKey];
          
          // Отправляем новый файл
          appendToFormData('new_file_' + fileKey, newFile.temp_id);
          
          // Отправляем product_id если есть
          if (newFile.product_id) {
            appendToFormData('new_file_product_id_' + fileKey, newFile.product_id);
          }
          
          // Отправляем product_index если есть (для новых вторичных продуктов)
          if (newFile.product_index !== null && newFile.product_index !== undefined) {
            appendToFormData('new_file_product_index_' + fileKey, newFile.product_index);
          }
          
          // Отправляем старый файл для удаления (если есть)
          const existingFileKey = 'existing_file_' + fileKey;
          
          if (newFile.product_id) {
            // Для вторичных продуктов
            if (fileState.existingFiles['product_photo_sec'] && fileState.existingFiles['product_photo_sec'][newFile.product_id]) {
              const oldFiles = fileState.existingFiles['product_photo_sec'][newFile.product_id];
              if (Array.isArray(oldFiles) && oldFiles.length > 0) {
                oldFiles.forEach(file => {
                  appendToFormData(existingFileKey + '[]', file.id);
                });
              } else if (oldFiles && oldFiles.id) {
                appendToFormData(existingFileKey, oldFiles.id);
              }
            }
          } else {
            // Для остальных типов файлов (product_photo, logo, etc.)
            const existing = fileState.existingFiles[fileKey];
            if (existing) {
              if (Array.isArray(existing) && existing.length > 0) {
                existing.forEach(file => {
                  appendToFormData(existingFileKey + '[]', file.id);
                });
              } else if (existing.id) {
                appendToFormData(existingFileKey, existing.id);
              }
            }
          }
        });
        
        Object.keys(fileState.existingFiles).forEach(fileType => {
          const existing = fileState.existingFiles[fileType];
          
          if (fileType === 'product_photo_sec' && typeof existing === 'object' && !Array.isArray(existing)) {
            // Обработка вторичных продуктов
            Object.keys(existing).forEach(productId => {
              const fileKey = fileType + '_' + productId;
              // Отправляем существующий файл только если нет нового файла для этого продукта
              if (!fileState.newFiles[fileKey]) {
                const files = existing[productId];
                if (files) {
                  if (Array.isArray(files) && files.length > 0) {
                    files.forEach(file => {
                      appendToFormData('existing_file_' + fileKey + '[]', file.id);
                    });
                  } else if (files && files.id) {
                    appendToFormData('existing_file_' + fileKey, files.id);
                  }
                }
              }
            });
          } else if (fileType === 'product_photo' && typeof existing === 'object' && !Array.isArray(existing)) {
            // Обработка product_photo с индексами продуктов
            Object.keys(existing).forEach(key => {
              let index = null;
              if (key.startsWith('index_')) {
                index = parseInt(key.replace('index_', ''), 10);
              } else {
                index = parseInt(key, 10);
                if (isNaN(index)) return;
              }
              
              const fileKey = `product_photo_index_${index}`;
              // Отправляем существующий файл только если нет нового файла
              if (!fileState.newFiles[fileKey] && !fileState.newFiles['product_photo']) {
                const fileData = existing[key];
                const file = Array.isArray(fileData) ? fileData[0] : fileData;
                if (file && file.id) {
                  appendToFormData('existing_file_' + fileKey, file.id);
                }
              }
            });
          } else if (existing && (Array.isArray(existing) ? existing.length > 0 : existing)) {
            // Обработка остальных типов файлов (logo, process_photo, etc.)
            const fileKey = fileType;
            // Отправляем существующий файл только если нет нового файла
            if (!fileState.newFiles[fileKey]) {
              if (Array.isArray(existing)) {
                existing.forEach((file, index) => {
                  appendToFormData('existing_file_' + fileKey + '[]', file.id);
                });
              } else if (existing.id) {
                appendToFormData('existing_file_' + fileKey, existing.id);
              }
            }
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
          
          // Обновляем localStorage актуальными данными перед перезагрузкой
          try {
            quickSave();
          } catch (e) {
            console.error('Error saving to localStorage:', e);
          }
          
          // Перенаправляем на страницу home после успешного сохранения
          setTimeout(() => {
            window.location.href = '?page=home';
          }, 1500);
        } else {
          msgEl.className = 'err';
          msgEl.textContent = result.err || 'Error al guardar los datos';
          msgEl.style.display = 'block';
          btnSave.disabled = false;
          btnSave.textContent = btnSave.getAttribute('data-i18n') ? btnSave.textContent : 'Guardar';
        }
      } catch (error) {
        msgEl.className = 'err';
        msgEl.textContent = 'Error de conexión. Intente de nuevo.';
        msgEl.style.display = 'block';
        btnSave.disabled = false;
        btnSave.textContent = btnSave.getAttribute('data-i18n') ? btnSave.textContent : 'Guardar';
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
  
  // Если у поля уже есть значение из БД (например, main_activity), обновляем визуальное отображение
  const currentValue = hiddenInput.value ? hiddenInput.value.trim() : '';
  // Проверяем, что значение не пустое и не "0" (которое может быть ошибкой)
  if (currentValue && currentValue !== '' && currentValue !== '…' && currentValue !== '0') {
    // Удаляем selected у всех опций
    options.forEach(opt => opt.classList.remove('selected'));
    
    // Ищем соответствующую опцию
    let found = false;
    for (let option of options) {
      if (option.dataset.value === currentValue) {
        if (selectedText) {
          selectedText.textContent = option.textContent;
        }
        option.classList.add('selected');
        found = true;
        break;
      }
    }
    
    // Если опция не найдена, но значение есть, оставляем значение в скрытом поле
    if (!found && selectedText) {
      selectedText.textContent = currentValue;
    }
  }
  
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
      
      // Игнорируем пустое значение и "…"
      if (!value || value === '' || value === '…') {
        dropdown.classList.remove('open');
        return;
      }
      
      // Update selected text
      if (selectedText) {
      selectedText.textContent = text;
      }
      
      // Update hidden input value
      if (hiddenInput) {
      hiddenInput.value = value;
        // Принудительно обновляем атрибут value для надежности
        hiddenInput.setAttribute('value', value);
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
      }
      
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
  // Привязываем ключ localStorage к ID пользователя
  const userId = window.currentUserId || 0;
  const STORAGE_KEY = userId > 0 ? `regfull_form_data_${userId}` : 'regfull_form_data';
  const hasCompanyDataInDB = window.hasCompanyDataInDB || false;
  
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
  
  // Функция для заполнения пустых полей из localStorage (приоритет у данных из БД)
  function mergeDBAndLocalStorage() {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (!saved) {
      return;
    }
    
    try {
      const formData = JSON.parse(saved);
      let mergedCount = 0;
      
      // Обработка обычных текстовых полей
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
          const dbValue = (field.value || '').trim();
          const localValue = formData[key];
          
          // Заполняем только если поле пустое в БД, но есть значение в localStorage
          if (!dbValue && localValue !== undefined && localValue !== null && localValue !== '') {
            field.value = localValue;
            mergedCount++;
          }
        }
      });
      
      // Обработка скрытых полей (dropdown'ы)
      document.querySelectorAll('input[type="hidden"]').forEach(field => {
        if (field.name && !field.name.startsWith('product_id')) {
          const dbValue = (field.value || '').trim();
          const localValue = formData[field.name];
          
          // Заполняем только если поле пустое в БД
          if (!dbValue && localValue && localValue !== '' && localValue !== '…') {
            field.value = localValue;
            const dropdown = field.closest('.custom-dropdown');
            if (dropdown) {
              const selectedText = dropdown.querySelector('.selected-text');
              if (selectedText) {
                if (formData[field.name + '_text']) {
                  selectedText.textContent = formData[field.name + '_text'];
                } else {
                  const option = dropdown.querySelector(`[data-value="${localValue}"]`);
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
            mergedCount++;
          }
        }
      });
      
      // Обработка чекбоксов (только если не выбраны в БД)
      document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        if (checkbox.name) {
          const savedValues = formData[checkbox.name];
          if (Array.isArray(savedValues)) {
            // Проверяем, не выбран ли уже в БД
            if (!checkbox.checked) {
              const shouldBeChecked = savedValues.includes(checkbox.value || 'checked');
              if (shouldBeChecked) {
                checkbox.checked = true;
                mergedCount++;
              }
            }
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
          if (key && !checkbox.checked && formData[key] !== undefined && formData[key]) {
            checkbox.checked = true;
            mergedCount++;
          }
        }
      });
      
      // Обработка радио-кнопок (только если не выбраны в БД)
      document.querySelectorAll('input[type="radio"]').forEach(radio => {
        if (radio.name && !document.querySelector(`input[name="${radio.name}"]:checked`)) {
          if (formData[radio.name] === radio.value) {
            radio.checked = true;
            mergedCount++;
          }
        }
      });
      
      // Обработка продуктов (только если их нет в БД)
      setTimeout(() => {
        const productItems = document.querySelectorAll('.product-item');
        const dbProductsCount = productItems.length;
        const localProductsCount = formData['_products_count'] || 0;
        
        if (localProductsCount > dbProductsCount) {
          const container = document.querySelector('.products-container');
          const template = document.querySelector('.product-item-tpl');
          if (container && template) {
            for (let i = dbProductsCount; i < localProductsCount; i++) {
              const addBtn = document.querySelector('.add-product');
              if (addBtn) {
                addBtn.click();
              }
            }
            setTimeout(() => {
              container.querySelectorAll('.product-item').forEach((item, idx) => {
                const nameKey = `_product_${idx}_name`;
                const descKey = `_product_${idx}_description`;
                const exportKey = `_product_${idx}_export`;
                
                // Заполняем только если поле пустое
                if (formData[nameKey]) {
                  const nameInput = item.querySelector('input[name="product_name[]"]');
                  if (nameInput && !nameInput.value.trim()) {
                    nameInput.value = formData[nameKey];
                  }
                }
                if (formData[descKey]) {
                  const descInput = item.querySelector('input[name="product_description[]"]');
                  if (descInput && !descInput.value.trim()) {
                    descInput.value = formData[descKey];
                  }
                }
                if (formData[exportKey]) {
                  const exportInput = item.querySelector('input[name="annual_export[]"]');
                  if (exportInput && !exportInput.value.trim()) {
                    exportInput.value = formData[exportKey];
                  }
                }
              });
              if (window.updateRemoveButtons) window.updateRemoveButtons();
              if (window.updateAddButtons) window.updateAddButtons();
            }, 200);
          }
        }
        
        // Обработка социальных сетей (только если их нет в БД)
        const socialRows = document.querySelectorAll('.social_row');
        const dbSocialCount = socialRows.length;
        const localSocialCount = formData['_social_rows_count'] || 0;
        
        if (localSocialCount > dbSocialCount) {
          const wrapper = document.getElementById('social-wrapper');
          const addBtn = document.getElementById('add-social');
          if (wrapper && addBtn) {
            for (let i = dbSocialCount; i < localSocialCount; i++) {
              addBtn.click();
            }
            setTimeout(() => {
              document.querySelectorAll('.social_row').forEach((row, idx) => {
                const tipoKey = `_social_${idx}_tipo`;
                const urlKey = `_social_${idx}_url`;
                const otherKey = `_social_${idx}_other`;
                
                if (formData[tipoKey]) {
                  const hiddenInput = row.querySelector('input.net');
                  if (hiddenInput && !hiddenInput.value) {
                    hiddenInput.value = formData[tipoKey];
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                  }
                }
                if (formData[urlKey]) {
                  const urlInput = row.querySelector('input[name="social_url[]"]');
                  if (urlInput && !urlInput.value.trim()) {
                    urlInput.value = formData[urlKey];
                  }
                }
                if (formData[otherKey]) {
                  const otherInput = row.querySelector('input.net-other');
                  if (otherInput && !otherInput.value.trim()) {
                    otherInput.value = formData[otherKey];
                    otherInput.hidden = false;
                  }
                }
              });
            }, 200);
          }
        }
        
        // Обработка рынков интереса (только если их нет в БД)
        const actList = document.querySelector('.act-list');
        const dbActCount = actList ? actList.children.length : 0;
        const localActCount = formData['_act_items_count'] || 0;
        
        if (localActCount > dbActCount && actList) {
          const actAdd = document.querySelector('.act-add');
          if (actAdd) {
            for (let i = dbActCount; i < localActCount; i++) {
              actAdd.click();
            }
            setTimeout(() => {
              actList.querySelectorAll('.act-row').forEach((row, idx) => {
                const valueKey = `_act_${idx}_value`;
                if (formData[valueKey]) {
                  const dropdown = row.querySelector('.custom-dropdown input[type="hidden"]');
                  if (dropdown && !dropdown.value) {
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
      
    } catch (e) {
      console.error('Error merging DB and localStorage:', e);
    }
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
        // Восстановление продуктов
        if (formData['_products_count'] && formData['_products_count'] > 1) {
          const container = document.querySelector('.products-container');
          const template = document.querySelector('.product-item-tpl');
          if (container && template) {
            const currentItems = container.querySelectorAll('.product-item').length;
            for (let i = currentItems; i < formData['_products_count']; i++) {
              const addBtn = document.querySelector('.add-product');
              if (addBtn) {
                addBtn.click();
              }
            }
            setTimeout(() => {
              container.querySelectorAll('.product-item').forEach((item, idx) => {
                const nameKey = `_product_${idx}_name`;
                const descKey = `_product_${idx}_description`;
                const exportKey = `_product_${idx}_export`;
                
                if (formData[nameKey]) {
                  const nameInput = item.querySelector('input[name="product_name[]"]');
                  if (nameInput) nameInput.value = formData[nameKey];
                }
                if (formData[descKey]) {
                  const descInput = item.querySelector('input[name="product_description[]"]');
                  if (descInput) descInput.value = formData[descKey];
                }
                if (formData[exportKey]) {
                  const exportInput = item.querySelector('input[name="annual_export[]"]');
                  if (exportInput) exportInput.value = formData[exportKey];
                }
              });
              if (window.updateRemoveButtons) window.updateRemoveButtons();
              if (window.updateAddButtons) window.updateAddButtons();
            }, 200);
          }
        }
        
        // Восстановление социальных сетей
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
        
        // Восстановление рынков интереса
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
  
  
  // Данные компании из БД для заполнения формы
  window.companyDataFromDB = {
    organization_type: <?= json_encode($companyData['organization_type'] ?? '') ?>,
    main_activity: <?= json_encode(($companyData['main_activity'] ?? '') !== '0' ? ($companyData['main_activity'] ?? '') : '') ?>,
    social_networks: <?= json_encode($companySocialNetworks) ?>,
    locality_legal: <?= json_encode($companyAddresses['legal']['locality'] ?? '') ?>,
    department_legal: <?= json_encode($companyAddresses['legal']['department'] ?? '') ?>,
    locality_admin: <?= json_encode($companyAddresses['admin']['locality'] ?? '') ?>,
    department_admin: <?= json_encode($companyAddresses['admin']['department'] ?? '') ?>,
    // current_markets и target_markets теперь индивидуальные для каждого продукта/услуги
    // и загружаются через loadExistingProducts()
    export_experience: <?= json_encode($companyDataJson['competitiveness']['export_experience'] ?? '') ?>,
  };
  
  // ID пользователя для привязки localStorage
  window.currentUserId = <?= isset($_SESSION['uid']) ? intval($_SESSION['uid']) : 0 ?>;
  window.hasCompanyDataInDB = <?= !empty($companyData) ? 'true' : 'false' ?>;
  
  function fillCompanyDataFromDB() {
    const data = window.companyDataFromDB;
    
    // Примечание: current_markets и target_markets теперь индивидуальные для каждого продукта/услуги
    // и загружаются через loadExistingProducts()
    
    // Заполнение export_experience dropdown
    if (data.export_experience) {
      const exportExperienceInput = document.querySelector('input[name="export_experience"]');
      if (exportExperienceInput) {
        exportExperienceInput.value = data.export_experience;
        const dropdown = exportExperienceInput.closest('.custom-dropdown');
        if (dropdown) {
          const options = dropdown.querySelectorAll('.dropdown-option');
          for (let option of options) {
            if (option.dataset.value === data.export_experience) {
              const selectedText = dropdown.querySelector('.selected-text');
              if (selectedText) {
                selectedText.textContent = option.textContent;
              }
              option.classList.add('selected');
              break;
            }
          }
        }
      }
    }
    
    // Примечание: target_markets теперь индивидуальные для каждого продукта/услуги
    // и загружаются через loadExistingProducts()
  }
  
  function fillDropdownsFromDB() {
    const data = window.companyDataFromDB;
    
    // Заполнение organization_type
    if (data.organization_type) {
      const orgTypeDropdown = document.querySelector('input[name="organization_type"]');
      if (orgTypeDropdown) {
        // Всегда устанавливаем значение из БД
        orgTypeDropdown.value = data.organization_type;
        const dropdown = orgTypeDropdown.closest('.custom-dropdown');
        if (dropdown) {
          const options = dropdown.querySelectorAll('.dropdown-option');
          for (let option of options) {
            if (option.dataset.value === data.organization_type) {
              const selectedText = dropdown.querySelector('.selected-text');
              if (selectedText) {
                selectedText.textContent = option.textContent;
              }
              option.classList.add('selected');
              // Удаляем selected у других опций
              options.forEach(opt => {
                if (opt !== option) {
                  opt.classList.remove('selected');
                }
              });
              break;
            }
          }
          // Триггерим событие change для обновления dropdown
          orgTypeDropdown.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    }
    
    // Заполнение main_activity (аналогично organization_type)
    if (data.main_activity) {
      const mainActivityDropdown = document.querySelector('input[name="main_activity"]');
      if (mainActivityDropdown) {
        // Всегда устанавливаем значение из БД
        mainActivityDropdown.value = data.main_activity;
        const dropdown = mainActivityDropdown.closest('.custom-dropdown');
        if (dropdown) {
          const options = dropdown.querySelectorAll('.dropdown-option');
          for (let option of options) {
            if (option.dataset.value === data.main_activity) {
              const selectedText = dropdown.querySelector('.selected-text');
              if (selectedText) {
                selectedText.textContent = option.textContent;
              }
              option.classList.add('selected');
              // Удаляем selected у других опций
              options.forEach(opt => {
                if (opt !== option) {
                  opt.classList.remove('selected');
                }
              });
              break;
            }
          }
          // Триггерим событие change для обновления dropdown
          mainActivityDropdown.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    }
    
    
    // Заполнение locality_legal
    if (data.locality_legal) {
      const localityLegalDropdown = document.querySelector('input[name="locality_legal"]');
      if (localityLegalDropdown) {
        localityLegalDropdown.value = data.locality_legal;
        const dropdown = localityLegalDropdown.closest('.custom-dropdown');
        if (dropdown) {
          const options = dropdown.querySelectorAll('.dropdown-option');
          for (let option of options) {
            if (option.dataset.value === data.locality_legal) {
              const selectedText = dropdown.querySelector('.selected-text');
              if (selectedText) {
                selectedText.textContent = option.textContent;
              }
              option.classList.add('selected');
              break;
            }
          }
        }
      }
    }
    
    // Заполнение department_legal
    if (data.department_legal) {
      const departmentLegalDropdown = document.querySelector('input[name="department_legal"]');
      if (departmentLegalDropdown) {
        departmentLegalDropdown.value = data.department_legal;
        const dropdown = departmentLegalDropdown.closest('.custom-dropdown');
        if (dropdown) {
          const options = dropdown.querySelectorAll('.dropdown-option');
          for (let option of options) {
            if (option.dataset.value === data.department_legal) {
              const selectedText = dropdown.querySelector('.selected-text');
              if (selectedText) {
                selectedText.textContent = option.textContent;
              }
              option.classList.add('selected');
              break;
            }
          }
        }
      }
    }
    
    // Заполнение locality_admin
    if (data.locality_admin) {
      const localityAdminDropdown = document.querySelector('input[name="locality_admin"]');
      if (localityAdminDropdown) {
        localityAdminDropdown.value = data.locality_admin;
        const dropdown = localityAdminDropdown.closest('.custom-dropdown');
        if (dropdown) {
          const options = dropdown.querySelectorAll('.dropdown-option');
          for (let option of options) {
            if (option.dataset.value === data.locality_admin) {
              const selectedText = dropdown.querySelector('.selected-text');
              if (selectedText) {
                selectedText.textContent = option.textContent;
              }
              option.classList.add('selected');
              break;
            }
          }
        }
      }
    }
    
    // Заполнение department_admin
    if (data.department_admin) {
      const departmentAdminDropdown = document.querySelector('input[name="department_admin"]');
      if (departmentAdminDropdown) {
        departmentAdminDropdown.value = data.department_admin;
        const dropdown = departmentAdminDropdown.closest('.custom-dropdown');
        if (dropdown) {
          const options = dropdown.querySelectorAll('.dropdown-option');
          for (let option of options) {
            if (option.dataset.value === data.department_admin) {
              const selectedText = dropdown.querySelector('.selected-text');
              if (selectedText) {
                selectedText.textContent = option.textContent;
              }
              option.classList.add('selected');
              break;
            }
          }
        }
      }
    }
    
    // Заполнение социальных сетей
    if (data.social_networks && data.social_networks.length > 0) {
      const socialWrapper = document.getElementById('social-wrapper');
      if (socialWrapper) {
        // Очистить существующие строки, кроме первой
        const existingRows = socialWrapper.querySelectorAll('.social_row');
        for (let i = 1; i < existingRows.length; i++) {
          existingRows[i].remove();
        }
        
        data.social_networks.forEach((social, index) => {
          let row;
          if (index === 0) {
            row = socialWrapper.querySelector('.social_row');
          } else {
            // Клонировать первую строку для новых
            const firstRow = socialWrapper.querySelector('.social_row');
            if (firstRow) {
              row = firstRow.cloneNode(true);
              const removeBtn = row.querySelector('.remove');
              if (removeBtn) {
                removeBtn.hidden = false;
              }
              socialWrapper.appendChild(row);
            }
          }
          
          if (row) {
            const urlInput = row.querySelector('input[name="social_url[]"]');
            const hiddenInput = row.querySelector('input.net');
            const finalInput = row.querySelector('input.net-final');
            
            if (urlInput && social.url) {
              urlInput.value = social.url;
            }
            
            if (hiddenInput && social.network_type) {
              hiddenInput.value = social.network_type;
              const dropdown = hiddenInput.closest('.custom-dropdown');
              if (dropdown) {
                const options = dropdown.querySelectorAll('.dropdown-option');
                for (let option of options) {
                  if (option.dataset.value === social.network_type) {
                    const selectedText = dropdown.querySelector('.selected-text');
                    if (selectedText) {
                      selectedText.textContent = option.textContent;
                    }
                    option.classList.add('selected');
                    break;
                  }
                }
              }
            }
            
            if (finalInput && social.network_type) {
              finalInput.value = social.network_type;
            }
          }
        });
      }
    }
  }
  
  document.addEventListener('DOMContentLoaded', () => {
    // Заполнить dropdown'ы и социальные сети из БД
    setTimeout(() => {
      fillDropdownsFromDB();
      fillCompanyDataFromDB();
    }, 500);
    
    setTimeout(async () => {
      // Загружаем данные только из БД
      if (hasCompanyDataInDB) {
        // Данные уже загружены из БД в PHP (через value атрибуты полей)
        // Загружаем продукты и файлы из БД
        if (typeof loadExistingProducts === 'function') {
          await loadExistingProducts();
        }
        setTimeout(async () => {
          if (typeof loadExistingFiles === 'function') {
            await loadExistingFiles();
          }
        }, 300);
      }
      // Если данных в БД нет, поля остаются пустыми (не загружаем из localStorage)
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
  
})();
</script>

<script>
// Управление файлами: загрузка при выборе и отображение существующих
(function() {
  const fileState = {
    existingFiles: {},
    newFiles: {}
  };
  
  function getFileTypeFromInput(input) {
    const name = input.name;
    if (name === 'product_photo[]') return 'product_photo';
    if (name === 'product_photo') return 'product_photo'; // для обратной совместимости
    if (name === 'service_photo[]') return 'service_photo';
    if (name === 'service_photo') return 'service_photo'; // для обратной совместимости
    if (name === 'product_photo_sec[]') return 'product_photo_sec';
    if (name === 'company_logo[]') return 'logo';
    if (name === 'process_photos[]') return 'process_photo';
    if (name === 'digital_catalog[]') return 'digital_catalog';
    if (name === 'institutional_video') return 'institutional_video';
    return null;
  }
  
  function getProductIdFromInput(input) {
    // Проверяем product-item (новые продукты)
    const productItem = input.closest('.product-item');
    if (productItem) {
      const hiddenInput = productItem.querySelector('input[type="hidden"][name="product_id[]"]');
      if (hiddenInput && hiddenInput.value) {
        return hiddenInput.value;
      }
    }
    
    // Проверяем service-item (новые услуги)
    const serviceItem = input.closest('.service-item');
    if (serviceItem) {
      const hiddenInput = serviceItem.querySelector('input[type="hidden"][name="service_id[]"]');
      if (hiddenInput && hiddenInput.value) {
        return hiddenInput.value;
      }
    }
    
    // Проверяем sec_item (старые вторичные продукты)
    const secItem = input.closest('.sec_item');
    if (secItem) {
      const hiddenInput = secItem.querySelector('input[type="hidden"][name="product_id_sec[]"]');
      return hiddenInput ? hiddenInput.value : null;
    }
    return null;
  }
  
  function getProductIndexFromInput(input) {
    // Для новых продуктов (product-item)
    const productItem = input.closest('.product-item');
    if (productItem) {
      const allProductItems = Array.from(document.querySelectorAll('.product-item'));
      const index = allProductItems.indexOf(productItem);
      return index >= 0 ? index : null;
    }
    
    // Для новых услуг (service-item)
    const serviceItem = input.closest('.service-item');
    if (serviceItem) {
      const allServiceItems = Array.from(document.querySelectorAll('.service-item'));
      const index = allServiceItems.indexOf(serviceItem);
      return index >= 0 ? index : null;
    }
    
    // Для старых вторичных продуктов (sec_item)
    const secItem = input.closest('.sec_item');
    if (secItem) {
      const allSecItems = Array.from(document.querySelectorAll('.sec_item'));
      const index = allSecItems.indexOf(secItem);
      return index >= 0 ? index : null;
    }
    return null;
  }
  
  async function uploadFile(file, input) {
    const fileType = getFileTypeFromInput(input);
    if (!fileType) {
      console.log('uploadFile: fileType not found for input:', input.name);
      return;
    }
    
    console.log('uploadFile: starting upload, fileType:', fileType, 'fileName:', file.name);
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('file_type', fileType);
    
    const productId = getProductIdFromInput(input);
    let productIndex = null;
    
    // Определяем индекс продукта/услуги для product_photo и service_photo
    if (fileType === 'product_photo' || fileType === 'service_photo') {
      productIndex = getProductIndexFromInput(input);
    } else if (fileType === 'product_photo_sec') {
      productIndex = getProductIndexFromInput(input);
    }
    
    console.log('uploadFile: productId:', productId, 'productIndex:', productIndex);
    
    if (productId && productId.trim() !== '') {
      formData.append('product_id', productId);
    }
    
    if (productIndex !== null) {
      formData.append('product_index', productIndex);
    }
    
    try {
      const response = await fetch('includes/regfull_upload_file_js.php', {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      console.log('uploadFile: server response:', result);
      
      if (result.ok === 1) {
        let fileKey;
        if (productId && productId.trim() !== '') {
          fileKey = `${fileType}_${productId}`;
        } else if (productIndex !== null && fileType === 'product_photo') {
          fileKey = `product_photo_index_${productIndex}`;
        } else if (productIndex !== null && fileType === 'service_photo') {
          fileKey = `service_photo_index_${productIndex}`;
        } else if (productIndex !== null && fileType === 'product_photo_sec') {
          fileKey = `${fileType}_index_${productIndex}`;
        } else {
          fileKey = fileType;
        }
        
        console.log('uploadFile: fileKey:', fileKey);
        
        fileState.newFiles[fileKey] = {
          temp_id: result.file_id,
          url: result.url,
          name: result.name,
          product_id: productId && productId.trim() !== '' ? productId : null,
          product_index: productIndex !== null ? productIndex : null
        };
        
        setTimeout(() => {
          // Для product_photo, service_photo и файлов секции 5 используем updateFileDisplay
          if ((fileType === 'product_photo' || fileType === 'service_photo' || fileType === 'logo' || fileType === 'process_photo' || 
               fileType === 'digital_catalog' || fileType === 'institutional_video') && window.updateFileDisplay) {
            window.updateFileDisplay(input, result.url, result.name);
          } else {
          displayFilePreview(input, result.url, result.name, true);
          }
        }, 100);
        hideExistingFile(input, fileType, productId);
      } else {
        console.error('uploadFile: error from server:', result.err);
        alert('Error al cargar el archivo: ' + (result.err || 'Error desconocido'));
      }
    } catch (error) {
      console.error('uploadFile: exception:', error);
      alert('Error al cargar el archivo: ' + error.message);
    }
  }
  
  function displayFilePreview(input, url, name, isNew) {
    console.log('displayFilePreview: input:', input.name, 'url:', url, 'name:', name, 'isNew:', isNew);
    
    // Для product_photo, service_photo и файлов секции 5 используем новую функцию updateFileDisplay
    if (input.name === 'product_photo[]' || input.name === 'product_photo' ||
        input.name === 'service_photo[]' || input.name === 'service_photo' ||
        input.name === 'company_logo[]' || input.name === 'process_photos[]' ||
        input.name === 'digital_catalog[]' || input.name === 'institutional_video') {
      if (window.updateFileDisplay) {
        window.updateFileDisplay(input, url, name);
      } else {
        // Fallback: используем старую логику если функция не доступна
        updateFileDisplayFallback(input, url, name);
      }
      return;
    }
    
    // Для остальных типов файлов используем старую логику
    let container = input.closest('.file-item');
    if (!container) {
      container = input.closest('.producto_grid');
    }
    if (!container) {
      container = input.closest('.sec_item');
    }
    if (!container) {
      container = input.parentElement;
    }
    
    if (!container) {
      console.error('displayFilePreview: No container found for input:', input.name);
      return;
    }
    
    console.log('displayFilePreview: container:', container.className);
    
    const preview = document.createElement('div');
    preview.className = 'file-preview' + (isNew ? ' new-file' : ' existing-file');
    
    const isVideo = name.toLowerCase().match(/\.(mp4|mkv|avi|mov|webm)$/i) || url.toLowerCase().match(/\.(mp4|mkv|avi|mov|webm)$/i);
    
    let previewContent = '';
    if (isVideo) {
      previewContent = `
        <video src="${url}" controls preload="metadata" style="max-width: 200px; max-height: 150px; margin-right: 10px; display: block;">
          Tu navegador no soporta la reproducción de video.
        </video>
        <span style="display: block; margin-top: 5px;">${name}</span>
      `;
    } else {
      previewContent = `
        <img src="${url}" alt="${name}" style="max-width: 100px; max-height: 100px; margin-right: 10px; display: block;">
        <span style="display: block; margin-top: 5px;">${name}</span>
      `;
    }
    
    preview.innerHTML = previewContent;
    preview.style.marginTop = '10px';
    preview.style.marginBottom = '10px';
    preview.style.display = 'block';
    preview.style.width = '100%';
    preview.style.clear = 'both';
    
    const existingPreview = container.querySelector('.file-preview');
    if (existingPreview) {
      existingPreview.remove();
    }
    
    if (container.classList.contains('producto_grid')) {
      console.log('displayFilePreview: Appending to producto_grid');
      container.appendChild(preview);
    } else if (container.classList.contains('sec_item')) {
      console.log('displayFilePreview: Found sec_item, looking for producto_grid');
      const productoGrid = container.querySelector('.producto_grid');
      if (productoGrid) {
        console.log('displayFilePreview: Appending to producto_grid within sec_item');
        productoGrid.appendChild(preview);
      } else {
        console.warn('displayFilePreview: No producto_grid found in sec_item, appending to sec_item');
        container.appendChild(preview);
      }
    } else {
      const secItem = input.closest('.sec_item');
      if (secItem) {
        console.log('displayFilePreview: Input is within sec_item, looking for producto_grid');
        const productoGrid = secItem.querySelector('.producto_grid');
        if (productoGrid) {
          console.log('displayFilePreview: Appending to producto_grid within sec_item (found via closest)');
          productoGrid.appendChild(preview);
        } else {
          console.warn('displayFilePreview: No producto_grid found, appending to container');
          if (input.nextSibling) {
            container.insertBefore(preview, input.nextSibling);
          } else {
            container.appendChild(preview);
          }
        }
      } else {
        if (input.nextSibling) {
          container.insertBefore(preview, input.nextSibling);
        } else {
          container.appendChild(preview);
        }
      }
    }
    
    console.log('displayFilePreview: Preview element created and appended');
  }
  
  function updateFileDisplayFallback(input, url, name) {
    const wrapper = input.closest('.file-ph-wrapper');
    if (!wrapper) return;
    
    const display = wrapper.querySelector('.file-ph-display');
    const img = display.querySelector('img.file-preview-img');
    const video = display.querySelector('video.file-preview-img');
    const placeholder = display.querySelector('.file-ph-placeholder');
    const removeBtn = display.querySelector('.file-ph-remove');
    
    if (url && name) {
      const isVideo = name.toLowerCase().match(/\.(mp4|mkv|avi|mov|webm)$/i) || url.toLowerCase().match(/\.(mp4|mkv|avi|mov|webm)$/i);
      
      if (isVideo && video) {
        video.src = url;
        video.style.display = 'block';
        if (img) img.style.display = 'none';
      } else if (img) {
        img.src = url;
        img.alt = name;
        img.style.display = 'block';
        if (video) video.style.display = 'none';
      }
      
      placeholder.style.display = 'none';
      removeBtn.style.display = 'flex';
      display.classList.add('has-image');
    } else {
      if (img) {
        img.style.display = 'none';
        img.src = '';
      }
      if (video) {
        video.style.display = 'none';
        video.src = '';
      }
      placeholder.style.display = 'block';
      removeBtn.style.display = 'none';
      display.classList.remove('has-image');
    }
  }
  
  function hideExistingFile(input, fileType, productId) {
    const container = input.closest('.file-item') || input.parentElement;
    const existingPreview = container.querySelector('.existing-file');
    if (existingPreview) {
      existingPreview.style.display = 'none';
    }
  }
  
  async function loadExistingFiles() {
    window.loadExistingFiles = loadExistingFiles;
    try {
      const response = await fetch('includes/regfull_get_files_js.php');
      const result = await response.json();
      
      if (result.ok === 1 && result.files) {
        fileState.existingFiles = result.files;
        setTimeout(() => {
          displayExistingFiles();
        }, 500);
      }
    } catch (error) {
      console.error('Error loading existing files:', error);
    }
  }
  
  function displayExistingFiles() {
    const files = fileState.existingFiles;
    
    // Product photos (массив продуктов)
    if (files.product_photo) {
      const productItems = document.querySelectorAll('.product-item');
      
      if (Array.isArray(files.product_photo)) {
        // Старый формат: массив файлов - для первого продукта
        if (files.product_photo.length > 0 && productItems.length > 0) {
          const input = productItems[0].querySelector('input[name="product_photo[]"]');
      if (input) {
        const file = files.product_photo[0];
            if (window.updateFileDisplay) {
              window.updateFileDisplay(input, file.url, file.name);
            } else {
        displayFilePreview(input, file.url, file.name, false);
      }
    }
        }
      } else if (typeof files.product_photo === 'object') {
        // Новый формат: объект с ключами product_id
        // Сначала создаем карту product_id -> элемент продукта
        const productIdToItem = new Map();
        productItems.forEach((item) => {
          const hiddenInput = item.querySelector('input[type="hidden"][name="product_id[]"]');
          if (hiddenInput && hiddenInput.value) {
            const productId = parseInt(hiddenInput.value, 10);
            if (!isNaN(productId)) {
              productIdToItem.set(productId, item);
            }
          }
        });
        
        Object.keys(files.product_photo).forEach(key => {
          let productId = null;
          
          // Проверяем, является ли ключ product_id (число)
          const keyAsNum = parseInt(key, 10);
          if (!isNaN(keyAsNum) && productIdToItem.has(keyAsNum)) {
            // Ключ - это product_id
            productId = keyAsNum;
          } else {
            // Пропускаем, если ключ не является product_id
            return;
          }
          
          const item = productIdToItem.get(productId);
          if (item) {
            const input = item.querySelector('input[name="product_photo[]"]');
            if (input) {
              const fileData = files.product_photo[key];
              const file = Array.isArray(fileData) ? fileData[0] : fileData;
              if (file && file.url) {
                if (window.updateFileDisplay) {
                  window.updateFileDisplay(input, file.url, file.name);
                } else {
                  displayFilePreview(input, file.url, file.name, false);
                }
              }
            }
          }
        });
      }
    }
    
    // Service photos
    if (files.service_photo) {
      const serviceItems = document.querySelectorAll('.service-item');
      
      if (Array.isArray(files.service_photo)) {
        // Старый формат: массив файлов - для первой услуги
        if (files.service_photo.length > 0 && serviceItems.length > 0) {
          const input = serviceItems[0].querySelector('input[name="service_photo[]"]');
          if (input) {
            const file = files.service_photo[0];
            if (window.updateFileDisplay) {
              window.updateFileDisplay(input, file.url, file.name);
            } else {
              displayFilePreview(input, file.url, file.name, false);
            }
          }
        }
      } else if (typeof files.service_photo === 'object') {
        // Новый формат: объект с ключами service_id (product_id в БД)
        // Сначала создаем карту service_id -> элемент услуги
        const serviceIdToItem = new Map();
        serviceItems.forEach((item) => {
          const hiddenInput = item.querySelector('input[type="hidden"][name="service_id[]"]');
          if (hiddenInput && hiddenInput.value) {
            const serviceId = parseInt(hiddenInput.value, 10);
            if (!isNaN(serviceId)) {
              serviceIdToItem.set(serviceId, item);
            }
          }
        });
        
        Object.keys(files.service_photo).forEach(key => {
          let serviceId = null;
          
          // Проверяем, является ли ключ service_id (число)
          const keyAsNum = parseInt(key, 10);
          if (!isNaN(keyAsNum) && serviceIdToItem.has(keyAsNum)) {
            // Ключ - это service_id
            serviceId = keyAsNum;
          } else {
            // Пропускаем, если ключ не является service_id
            return;
          }
          
          const item = serviceIdToItem.get(serviceId);
          if (item) {
            const input = item.querySelector('input[name="service_photo[]"]');
            if (input) {
              const fileData = files.service_photo[key];
              const file = Array.isArray(fileData) ? fileData[0] : fileData;
              if (file && file.url) {
                if (window.updateFileDisplay) {
                  window.updateFileDisplay(input, file.url, file.name);
                } else {
                  displayFilePreview(input, file.url, file.name, false);
                }
              }
            }
          }
        });
      }
    }
    
    // Company logo
    if (files.logo && Array.isArray(files.logo) && files.logo.length > 0) {
      files.logo.forEach((file, index) => {
        const inputs = document.querySelectorAll('input[name="company_logo[]"]');
        if (inputs[index]) {
          if (window.updateFileDisplay) {
            window.updateFileDisplay(inputs[index], file.url, file.name);
          } else {
            displayFilePreview(inputs[index], file.url, file.name, false);
          }
        }
      });
    }
    
    // Process photos
    if (files.process_photo && Array.isArray(files.process_photo) && files.process_photo.length > 0) {
      files.process_photo.forEach((file, index) => {
        const inputs = document.querySelectorAll('input[name="process_photos[]"]');
        if (inputs[index]) {
          if (window.updateFileDisplay) {
            window.updateFileDisplay(inputs[index], file.url, file.name);
          } else {
          displayFilePreview(inputs[index], file.url, file.name, false);
          }
        }
      });
    }
    
    // Digital catalog
    if (files.digital_catalog && Array.isArray(files.digital_catalog) && files.digital_catalog.length > 0) {
      files.digital_catalog.forEach((file, index) => {
        const inputs = document.querySelectorAll('input[name="digital_catalog[]"]');
        if (inputs[index]) {
          if (window.updateFileDisplay) {
            window.updateFileDisplay(inputs[index], file.url, file.name);
          } else {
            displayFilePreview(inputs[index], file.url, file.name, false);
          }
        }
      });
    }
    
    // Institutional video
    if (files.institutional_video && Array.isArray(files.institutional_video) && files.institutional_video.length > 0) {
      const input = document.querySelector('input[name="institutional_video"]');
      if (input) {
        const file = files.institutional_video[0];
        if (window.updateFileDisplay) {
          window.updateFileDisplay(input, file.url, file.name);
        } else {
        displayFilePreview(input, file.url, file.name, false);
        }
      }
    }
    
    // Secondary product photos
    if (files.product_photo_sec) {
      console.log('displayExistingFiles: Found product_photo_sec files:', files.product_photo_sec);
      console.log('displayExistingFiles: Loaded product IDs:', Array.from(loadedProductIds));
      
      Object.keys(files.product_photo_sec).forEach(productId => {
        const photos = files.product_photo_sec[productId];
        const productIdStr = String(productId);
        const productIdNum = parseInt(productId, 10);
        
        if (!loadedProductIds.has(productIdNum)) {
          console.warn('displayExistingFiles: Skipping file for product_id:', productIdStr, '- product does not exist');
          return;
        }
        
        console.log('displayExistingFiles: Processing product_id:', productIdStr, 'photos:', photos);
        
        photos.forEach((file, index) => {
          if (index > 0) return;
          
          const secItems = document.querySelectorAll('.sec_item');
          console.log('displayExistingFiles: Found', secItems.length, 'sec_item elements');
          
          let foundItem = null;
          
          secItems.forEach((item, itemIndex) => {
            const productIdInput = item.querySelector('input[name="product_id_sec[]"]');
            if (productIdInput) {
              const itemProductIdStr = String(productIdInput.value || '').trim();
              const itemProductIdNum = parseInt(itemProductIdStr, 10);
              
              console.log(`displayExistingFiles: sec_item[${itemIndex}] product_id:`, itemProductIdStr, 'comparing with', productIdStr);
              
              if (itemProductIdStr === productIdStr || (itemProductIdNum === productIdNum && !isNaN(itemProductIdNum) && !isNaN(productIdNum))) {
                foundItem = item;
                console.log('displayExistingFiles: Found matching sec_item for product_id:', productIdStr);
              }
            } else {
              console.log(`displayExistingFiles: sec_item[${itemIndex}] has no product_id input`);
            }
          });
          
          if (foundItem) {
            const input = foundItem.querySelector('input[name="product_photo_sec[]"]');
            if (input) {
              console.log('displayExistingFiles: Displaying file preview for product_id:', productIdStr, 'file:', file.name);
              displayFilePreview(input, file.url, file.name, false);
            } else {
              console.error('displayExistingFiles: Found sec_item but no input[name="product_photo_sec[]"]');
            }
          } else {
            console.warn('displayExistingFiles: No matching sec_item found for product_id:', productIdStr, 'retrying in 500ms...');
            setTimeout(() => {
              const secItems = document.querySelectorAll('.sec_item');
              let retryFoundItem = null;
              
              secItems.forEach(item => {
                const productIdInput = item.querySelector('input[name="product_id_sec[]"]');
                if (productIdInput) {
                  const itemProductIdStr = String(productIdInput.value || '').trim();
                  const itemProductIdNum = parseInt(itemProductIdStr, 10);
                  
                  if (itemProductIdStr === productIdStr || (itemProductIdNum === productIdNum && !isNaN(itemProductIdNum) && !isNaN(productIdNum))) {
                    retryFoundItem = item;
                  }
                }
              });
              
              if (retryFoundItem) {
                const input = retryFoundItem.querySelector('input[name="product_photo_sec[]"]');
                if (input) {
                  console.log('displayExistingFiles: Retry successful, displaying file preview for product_id:', productIdStr);
                  displayFilePreview(input, file.url, file.name, false);
                }
              } else {
                console.error('displayExistingFiles: Retry failed, still no matching sec_item for product_id:', productIdStr);
              }
            }, 500);
          }
        });
      });
    }
  }
  
  let loadedProductIds = new Set();
  
  async function loadExistingProducts() {
    window.loadExistingProducts = loadExistingProducts;
    try {
      const response = await fetch('includes/regfull_get_products_js.php');
      const result = await response.json();
      
      loadedProductIds.clear();
      
      if (result.ok === 1) {
        // Обработка продуктов
        if (result.products && result.has_products) {
        let allProducts = [];
        if (result.products.all && Array.isArray(result.products.all)) {
          allProducts = result.products.all;
        } else {
        const { main, secondary } = result.products;
            if (main) allProducts.push(main);
          if (secondary && Array.isArray(secondary)) {
            allProducts.push(...secondary);
          }
        }
        
        allProducts.forEach(prod => {
          if (prod && prod.id) {
            loadedProductIds.add(prod.id);
          }
        });
        
          const itemsList = document.querySelector('.items-list');
          const productTemplate = document.querySelector('.product-item-tpl');
          
          if (itemsList && productTemplate) {
        allProducts.forEach((product, index) => {
              const productItem = productTemplate.content.firstElementChild.cloneNode(true);
              itemsList.insertBefore(productItem, itemsList.firstChild);
              
          if (window.bindProductItemEvents) {
            window.bindProductItemEvents(productItem);
              }
              
              const nameInput = productItem.querySelector('input[name="product_name[]"]');
              const descInput = productItem.querySelector('input[name="product_description[]"]');
              const exportInput = productItem.querySelector('input[name="annual_export[]"]');
              const certInput = productItem.querySelector('input[name="product_certifications[]"]');
              const currentMarketsInput = productItem.querySelector('input[name="product_current_markets[]"]');
              
              if (nameInput) nameInput.value = product.name || '';
              if (descInput) descInput.value = product.description || '';
              if (exportInput) exportInput.value = product.annual_export || '';
              if (certInput) certInput.value = product.certifications || '';
              
              // Заполнение Mercados Actuales
              if (currentMarketsInput && product.current_markets) {
                currentMarketsInput.value = product.current_markets;
                const dropdown = currentMarketsInput.closest('.custom-dropdown');
                if (dropdown && typeof initCustomDropdown === 'function') {
                  initCustomDropdown(dropdown, currentMarketsInput);
                  const options = dropdown.querySelectorAll('.dropdown-option');
                  for (let option of options) {
                    if (option.dataset.value === product.current_markets) {
                      const selectedText = dropdown.querySelector('.selected-text');
                      if (selectedText) selectedText.textContent = option.textContent;
                      option.classList.add('selected');
                      break;
                    }
                  }
                }
              }
              
              // Заполнение Mercados de Interés (если есть в данных)
              if (product.target_markets && Array.isArray(product.target_markets) && product.target_markets.length > 0) {
                const mercadosBox = productItem.querySelector('.mercados_act');
                if (mercadosBox) {
                  const actList = mercadosBox.querySelector('.act-list');
                  const actAdd = mercadosBox.querySelector('.act-add');
                  if (actList && actAdd) {
                    product.target_markets.forEach((market, idx) => {
                      if (idx === 0) {
                        const firstRow = actList.querySelector('.act-row');
                        if (firstRow) {
                          const dropdown = firstRow.querySelector('.custom-dropdown');
                          const hiddenInput = firstRow.querySelector('input[type="hidden"]');
                          if (dropdown && hiddenInput) {
                            hiddenInput.value = market;
                            if (typeof initCustomDropdown === 'function') {
                              initCustomDropdown(dropdown, hiddenInput);
                              const options = dropdown.querySelectorAll('.dropdown-option');
                              for (let option of options) {
                                if (option.dataset.value === market) {
                                  const selectedText = dropdown.querySelector('.selected-text');
                                  if (selectedText) selectedText.textContent = option.textContent;
                                  option.classList.add('selected');
                                  break;
                                }
                              }
                            }
                          }
                        }
                      } else {
                        actAdd.click();
                        setTimeout(() => {
                          const rows = actList.querySelectorAll('.act-row');
                          const newRow = rows[rows.length - 1];
                          if (newRow) {
                            const dropdown = newRow.querySelector('.custom-dropdown');
                            const hiddenInput = newRow.querySelector('input[type="hidden"]');
                            if (dropdown && hiddenInput) {
                              hiddenInput.value = market;
                              if (typeof initCustomDropdown === 'function') {
                                initCustomDropdown(dropdown, hiddenInput);
                                const options = dropdown.querySelectorAll('.dropdown-option');
                                for (let option of options) {
                                  if (option.dataset.value === market) {
                                    const selectedText = dropdown.querySelector('.selected-text');
                                    if (selectedText) selectedText.textContent = option.textContent;
                                    option.classList.add('selected');
                                    break;
                                  }
                                }
                              }
                            }
                          }
                        }, 100);
                      }
                    });
                  }
                }
              }
              
              if (product.id) {
                let hiddenInput = productItem.querySelector('input[type="hidden"][name="product_id[]"]');
                if (!hiddenInput) {
                  hiddenInput = document.createElement('input');
                  hiddenInput.type = 'hidden';
                  hiddenInput.name = 'product_id[]';
                  productItem.querySelector('.producto_grid').appendChild(hiddenInput);
                }
                hiddenInput.value = product.id;
              }
            });
            
            if (window.updateRemoveButtons) {
              window.updateRemoveButtons();
            }
          }
        }
        
        // Обработка услуг
        if (result.services && result.has_services) {
          let allServices = [];
          if (result.services.all && Array.isArray(result.services.all)) {
            allServices = result.services.all;
                } else {
            const { main, secondary } = result.services;
            if (main) allServices.push(main);
            if (secondary && Array.isArray(secondary)) {
              allServices.push(...secondary);
            }
          }
          
          allServices.forEach(serv => {
            if (serv && serv.id) {
              loadedProductIds.add(serv.id);
            }
          });
          
          const itemsList = document.querySelector('.items-list');
          const serviceTemplate = document.querySelector('.service-item-tpl');
          
          if (itemsList && serviceTemplate) {
            allServices.forEach((service, index) => {
              const serviceItem = serviceTemplate.content.firstElementChild.cloneNode(true);
              itemsList.insertBefore(serviceItem, itemsList.firstChild);
              
              if (window.bindServiceItemEvents) {
                window.bindServiceItemEvents(serviceItem);
              }
              
              // Заполнение dropdown Actividad
              if (service.activity) {
                const activityInput = serviceItem.querySelector('input[name="service_activity[]"]');
                if (activityInput) {
                  activityInput.value = service.activity;
                  const dropdown = activityInput.closest('.custom-dropdown');
                  if (dropdown && typeof initCustomDropdown === 'function') {
                    initCustomDropdown(dropdown, activityInput);
                    const options = dropdown.querySelectorAll('.dropdown-option');
                    for (let option of options) {
                      if (option.dataset.value === service.activity) {
                        const selectedText = dropdown.querySelector('.selected-text');
                        if (selectedText) selectedText.textContent = option.textContent;
                        option.classList.add('selected');
                        break;
                      }
                    }
                  }
                }
              }
              
              const nameInput = serviceItem.querySelector('input[name="service_name[]"]');
              const descInput = serviceItem.querySelector('input[name="service_description[]"]');
              const exportInput = serviceItem.querySelector('input[name="annual_export[]"]');
              const certInput = serviceItem.querySelector('input[name="service_certifications[]"]');
              const currentMarketsInput = serviceItem.querySelector('input[name="service_current_markets[]"]');
              
              if (nameInput) nameInput.value = service.name || '';
              if (descInput) descInput.value = service.description || '';
              if (exportInput) exportInput.value = service.annual_export || '';
              if (certInput) certInput.value = service.certifications || '';
              
              // Заполнение Mercados Actuales
              if (currentMarketsInput && service.current_markets) {
                currentMarketsInput.value = service.current_markets;
                const dropdown = currentMarketsInput.closest('.custom-dropdown');
                if (dropdown && typeof initCustomDropdown === 'function') {
                  initCustomDropdown(dropdown, currentMarketsInput);
                  const options = dropdown.querySelectorAll('.dropdown-option');
                  for (let option of options) {
                    if (option.dataset.value === service.current_markets) {
                      const selectedText = dropdown.querySelector('.selected-text');
                      if (selectedText) selectedText.textContent = option.textContent;
                      option.classList.add('selected');
                      break;
                    }
                  }
                }
              }
              
              // Заполнение Mercados de Interés (если есть в данных)
              if (service.target_markets && Array.isArray(service.target_markets) && service.target_markets.length > 0) {
                const mercadosBox = serviceItem.querySelector('.mercados_act');
                if (mercadosBox) {
                  const actList = mercadosBox.querySelector('.act-list');
                  const actAdd = mercadosBox.querySelector('.act-add');
                  if (actList && actAdd) {
                    service.target_markets.forEach((market, idx) => {
                      if (idx === 0) {
                        const firstRow = actList.querySelector('.act-row');
                        if (firstRow) {
                          const dropdown = firstRow.querySelector('.custom-dropdown');
                          const hiddenInput = firstRow.querySelector('input[type="hidden"]');
                          if (dropdown && hiddenInput) {
                            hiddenInput.value = market;
                            if (typeof initCustomDropdown === 'function') {
                              initCustomDropdown(dropdown, hiddenInput);
                              const options = dropdown.querySelectorAll('.dropdown-option');
                              for (let option of options) {
                                if (option.dataset.value === market) {
                                  const selectedText = dropdown.querySelector('.selected-text');
                                  if (selectedText) selectedText.textContent = option.textContent;
                                  option.classList.add('selected');
                                  break;
                                }
                              }
                            }
                          }
                        }
                      } else {
                        actAdd.click();
                        setTimeout(() => {
                          const rows = actList.querySelectorAll('.act-row');
                          const newRow = rows[rows.length - 1];
                          if (newRow) {
                            const dropdown = newRow.querySelector('.custom-dropdown');
                            const hiddenInput = newRow.querySelector('input[type="hidden"]');
                            if (dropdown && hiddenInput) {
                              hiddenInput.value = market;
                              if (typeof initCustomDropdown === 'function') {
                                initCustomDropdown(dropdown, hiddenInput);
                                const options = dropdown.querySelectorAll('.dropdown-option');
                                for (let option of options) {
                                  if (option.dataset.value === market) {
                                    const selectedText = dropdown.querySelector('.selected-text');
                                    if (selectedText) selectedText.textContent = option.textContent;
                                    option.classList.add('selected');
                                    break;
                                  }
                                }
                              }
                            }
                          }
                        }, 100);
                      }
                    });
                  }
                }
              }
              
              if (service.id) {
                let hiddenInput = serviceItem.querySelector('input[type="hidden"][name="service_id[]"]');
            if (!hiddenInput) {
              hiddenInput = document.createElement('input');
              hiddenInput.type = 'hidden';
                  hiddenInput.name = 'service_id[]';
                  serviceItem.appendChild(hiddenInput);
                }
                hiddenInput.value = service.id;
              }
            });
            
            if (window.updateRemoveButtons) {
              window.updateRemoveButtons();
            }
          }
        }
      }
    } catch (error) {
      console.error('Error loading products and services:', error);
    }
  }
  
  document.addEventListener('DOMContentLoaded', () => {
    setTimeout(async () => {
      await loadExistingProducts();
      setTimeout(async () => {
        await loadExistingFiles();
      }, 300);
    }, 500);
    
    document.addEventListener('change', function(e) {
      if (e.target && e.target.type === 'file') {
        console.log('File input change event:', e.target.name, 'files:', e.target.files);
        if (e.target.files && e.target.files.length > 0) {
          console.log('Calling uploadFile for:', e.target.files[0].name);
          uploadFile(e.target.files[0], e.target);
        } else {
          console.log('No files selected');
        }
      }
    });
  });
  
  window.getFileState = function() {
    return fileState;
  };
})();
</script>

<script src="js/i18n.js?v=1.0.4"></script>
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
<!-- validacion -->