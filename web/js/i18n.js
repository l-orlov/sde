async function setLang(page, lang) {
  const currentLangEl = document.getElementById('current-lang');
  if (currentLangEl) {
    currentLangEl.textContent = lang.toUpperCase();
  }

  // Сохраняем выбранный язык в localStorage
  localStorage.setItem('lang', lang);

  try {
    const url = `lang/${page}/${lang}.json?v=1.0.4`;
    const res = await fetch(url);

    if (!res.ok) {
      throw new Error(`Failed to load ${url}, status ${res.status}`);
    }

    const dict = await res.json();

    // Обычные тексты
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (dict[key]) {
        // Проверяем, есть ли внутри элемента звездочка с классом .req
        const reqSpan = el.querySelector('.req');
        const hasAsterisk = !!reqSpan;
        
        // Сохраняем звездочку, если она есть
        const asterisk = hasAsterisk ? reqSpan.outerHTML : null;
        
        // Получаем переведенный текст и удаляем из него все звездочки
        let translatedText = dict[key];
        // Удаляем все звездочки и пробелы вокруг них из переведенного текста
        translatedText = translatedText.replace(/\s*\*\s*/g, '').trim();
        
        // Устанавливаем переведенный текст (это удалит все дочерние элементы, включая звездочку)
        el.textContent = translatedText;
        
        // Если была звездочка в исходном HTML, добавляем её обратно
        if (hasAsterisk && asterisk) {
          el.insertAdjacentHTML('beforeend', ' ' + asterisk);
        }
      }
    });

    // Плейсхолдеры
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
      const key = el.getAttribute('data-i18n-placeholder');
      if (dict[key]) {
        el.setAttribute('placeholder', dict[key]);
      }
    });

    // HTML-тексты
    document.querySelectorAll('[data-i18n-html]').forEach(el => {
      const key = el.getAttribute('data-i18n-html');
      if (dict[key]) {
        el.innerHTML = dict[key];
      }
    });

  } catch (err) {
    console.error(`Language load error for ${lang}:`, err);
  }
}

const supportedLangs = ['es', 'en', 'ru'];

function initLang(page = 'landing', defaultLang = 'es') {
  const storedLang = localStorage.getItem('lang');
  const browserLang = (navigator.language || '').split('-')[0];

  const lang = storedLang ?? (supportedLangs.includes(browserLang) ? browserLang : defaultLang);

  setLang(page, lang);
}
