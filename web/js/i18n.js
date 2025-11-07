async function setLang(page, lang) {
  const currentLangEl = document.getElementById('current-lang');
  if (currentLangEl) {
    currentLangEl.textContent = lang.toUpperCase();
  }

  // Сохраняем выбранный язык в localStorage
  localStorage.setItem('lang', lang);

  try {
    const url = `/lang/${page}/${lang}.json`;
    const res = await fetch(url);

    if (!res.ok) {
      throw new Error(`Failed to load ${url}, status ${res.status}`);
    }

    const dict = await res.json();

    // Обычные тексты
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (dict[key]) {
        el.textContent = dict[key];
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
