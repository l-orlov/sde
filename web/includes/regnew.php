<div class="datos">
  <div class="datos_header">
    <h1>1. Datos de la empresa</h1>
    <img src="img/icons/datos.png">
  </div>
  <div class="form controls" novalidate>
    <!-- Nombre -->
    <div class="label"><label for="nombre">Nombre de la Empresa/Emprendimiento <span class="req">*</span></label></div>
    <div class="field"><input id="nombre" name="nombre" required></div>
    <!-- CUIT -->
    <div class="label"><label for="cuit">CUIT / Identificación Fiscal <span class="req">*</span></label></div>
    <div class="field"><input id="cuit" name="cuit" placeholder="XX-XXXXXXXX-X" required></div>
    <!-- Razón social -->
    <div class="label"><label for="razon">Razón social <span class="req">*</span></label></div>
    <div class="field"><input id="razon" name="razon" required></div>
    <!-- Fecha de inicio -->
    <div class="label"><label for="inicio">Fecha de Inicio de Actividad <span class="req">*</span></label></div>
    <div class="field"><input id="inicio" name="inicio" placeholder="dd/mm/aaaa" inputmode="numeric" required></div>
    <!-- Página web -->
    <div class="label"><label for="web">Página web (si aplica)</label></div>
    <div class="field"><input id="web" name="web" type="url" placeholder="http://…"></div>
    <!-- Redes sociales -->
    <div class="label"><span>Redes sociales:</span></div>
    <div class="field" id="social-wrapper">
      <div class="social_row">
        <select class="net" aria-label="Тип сети">
          <option value="">…</option>
          <option>Instagram</option><option>Facebook</option><option>LinkedIn</option>
          <option>X (Twitter)</option><option>TikTok</option><option>YouTube</option><option>Otra</option>
        </select>
        <input class="net-other" type="text" placeholder="Ingresa la red…" hidden>
        <input class="net-final" type="hidden" name="social_tipo[]">
        <div class="inline">
          <input name="social_url[]" type="url" placeholder="http://…">
          <button type="button" class="remove" hidden>&times;</button>
        </div>
      </div>
      <button type="button" class="add_more" id="add-social">agregar más</button>
    </div>
    <!-- Domicilio Legal -->
    <div class="address">
      <div class="label"><span>Domicilio Legal <span class="req">*</span></span></div>
      <div class="address_grid">
        <label class="label_span">Calle <span class="req">*</span></label>
        <input class="span_right">
        <label class="label_span">Altura <span class="req">*</span></label>
        <input class="">
        <label class="label_span">Código Postal <span class="req">*</span></label>
        <input class="">
        <label class="label_span">Piso</label>
        <input class="">
        <label class="label_span">Departamento</label>
        <input class="">
        <label class="label_span">Localidad <span class="req">*</span></label>
        <select class="span_right"></select>
        <label class="label_span">Departamento <span class="req">*</span></label>
        <select class="span_right"></select>
      </div>
    </div>
    <!-- Dirección administrativa -->
    <div class="address">
      <div class="label"><span>Dirección administrativa <span class="req">*</span></span></div>
      <div class="address_grid">
        <label class="label_span">Calle <span class="req">*</span></label>
        <input class="span_right">
        <label class="label_span">Altura <span class="req">*</span></label>
        <input class="">
        <label class="label_span">Código Postal <span class="req">*</span></label>
        <input class="">
        <label class="label_span">Piso</label>
        <input class="">
        <label class="label_span">Departamento</label>
        <input class="">
        <label class="label_span">Localidad <span class="req">*</span></label>
        <select class="span_right"></select>
        <label class="label_span">Departamento <span class="req">*</span></label>
        <select class="span_right"></select>
      </div>
    </div>
    <!-- Contacto -->
    <div class="contacto_datos">
      <div class="label"><span>Persona de Contacto <span class="req">*</span></span></div>
      <div class="contacto_grid">
        <input class="span_all">
        <label class="label_span">Cargo de Persona de contacto <span class="req">*</span></label>
        <input>
        <label class="label_span">E-mail <span class="req">*</span></label>
        <input type="email">
        <label class="label_span">Teléfono <span class="req">*</span></label>
        <div class="phone_inline">
          <input class="area" placeholder="Código de área">
          <input placeholder="">
        </div>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const wrapper = document.getElementById('social-wrapper');
  const addBtn  = document.getElementById('add-social');

  function bindRow(row) {
    const sel   = row.querySelector('select.net');
    const other = row.querySelector('input.net-other');
    const final = row.querySelector('input.net-final');
    const rmBtn = row.querySelector('.remove');

    // Инициализация финального значения при загрузке
    syncFinal();

    // Селект: показываем/прячем поле "Otra" и синхронизируем
    sel.addEventListener('change', () => {
      if (sel.value === 'Otra') {
        other.hidden = false;
        other.required = true;          // опционально
        other.focus();
      } else {
        other.hidden = true;
        other.required = false;
        other.value = '';
      }
      syncFinal();
    });

    // Ввод в поле "Otra": пишем в скрытое финальное
    other.addEventListener('input', syncFinal);

    // Кнопка удаления
    if (rmBtn && !rmBtn._bound) {
      rmBtn.addEventListener('click', () => {
        row.remove();
        updateRemoveButtons();
      });
      rmBtn._bound = true;
    }

    function syncFinal() {
      if (sel.value === 'Otra') {
        final.value = other.value.trim();
      } else {
        final.value = sel.value;
      }
    }
  }

  function updateRemoveButtons() {
    const rows = wrapper.querySelectorAll('.social_row');
    rows.forEach((row, idx) => {
      const rm = row.querySelector('.remove');
      if (!rm) return;
      rm.hidden = (rows.length === 1 || idx === 0); // первую не удаляем
    });
  }

  // Кнопка «добавить»
  addBtn.addEventListener('click', () => {
    const first = wrapper.querySelector('.social_row');
    const clone = first.cloneNode(true);

    // Очистка значений в клоне
    const sel   = clone.querySelector('select.net');
    const other = clone.querySelector('input.net-other');
    const final = clone.querySelector('input.net-final');
    const url   = clone.querySelector('input[name="social_url[]"]');

    sel.selectedIndex = 0;
    other.value = '';
    other.hidden = true;
    other.required = false;
    final.value = '';
    if (url) url.value = '';

    // Убедимся, что remove видна у клонов
    const rm = clone.querySelector('.remove');
    if (rm) rm.hidden = false;

    // Вставка и привязка обработчиков
    addBtn.before(clone);
    bindRow(clone);
    updateRemoveButtons();
  });

  // Привязать обработчики к первой строке
  const firstRow = wrapper.querySelector('.social_row');
  if (firstRow) bindRow(firstRow);

  updateRemoveButtons();
});
</script>

