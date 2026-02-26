async function setLang(page, lang) {
  const currentLangEl = document.getElementById('current-lang');
  if (currentLangEl) {
    currentLangEl.textContent = lang.toUpperCase();
  }

  // Сохраняем выбранный язык в localStorage
  localStorage.setItem('lang', lang);

  try {
    let v = '';
    try {
      const s = document.querySelector('script[src*="i18n.js"]');
      if (s && s.src) {
        const m = s.src.match(/[?&]v=([^&]+)/);
        v = m ? m[1] : '';
      }
    } catch (e) {}
    let url = `lang/${page}/${lang}.json` + (v ? '?v=' + v : '');
    let res = await fetch(url);
    let fallbackLang = (lang === 'es') ? 'en' : 'es';

    if (!res.ok) {
      url = `lang/${page}/${fallbackLang}.json` + (v ? '?v=' + v : '');
      res = await fetch(url);
      if (!res.ok) {
        throw new Error(`Failed to load lang file, status ${res.status}`);
      }
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

    window.__i18nDict = dict;
    appendPdfLangToLinks(lang);
  } catch (err) {
    console.error(`Language load error for ${lang}:`, err);
  }
}

const supportedLangs = ['es', 'en'];

/** Текущий язык для PDF: только 'en' или 'es'. */
function getPdfLang() {
  var raw = (localStorage.getItem('lang') || 'es').toString().trim().toLowerCase();
  return (raw === 'en') ? 'en' : 'es';
}

/** Ставит href у ссылок .js-pdf-link по выбранному языку (data-pdf-url-es / data-pdf-url-en). */
function appendPdfLangToLinks(lang) {
  var safeLang = (lang === 'en') ? 'en' : 'es';
  var attr = 'data-pdf-url-' + safeLang;
  document.querySelectorAll('.js-pdf-link').forEach(function (a) {
    var url = a.getAttribute(attr);
    if (url) a.setAttribute('href', url);
  });
}

/** Находит ссылку .js-pdf-link, поднимаясь от узла вверх. */
function findPdfLink(el) {
  while (el && el !== document.body) {
    if (el.nodeType === 1 && el.classList && el.classList.contains('js-pdf-link')) return el;
    el = el.parentNode;
  }
  return null;
}

/** При mousedown обновляем href по текущему языку, чтобы и "открыть в новой вкладке" работало. */
function initPdfMousedown() {
  document.body.addEventListener('mousedown', function (e) {
    var a = findPdfLink(e.target);
    if (a) {
      var safeLang = getPdfLang();
      var url = a.getAttribute('data-pdf-url-' + safeLang);
      if (url) a.setAttribute('href', url);
    }
  }, true);
}

/** При клике по .js-pdf-link открывать URL для текущего языка из localStorage. */
function initPdfLangClick() {
  document.body.addEventListener('click', function (e) {
    var a = findPdfLink(e.target);
    if (!a) return;
    var safeLang = getPdfLang();
    var url = a.getAttribute('data-pdf-url-' + safeLang);
    if (url) {
      e.preventDefault();
      e.stopPropagation();
      a.setAttribute('href', url);
      if (a.getAttribute('target') === '_blank') {
        window.open(url, '_blank', 'noopener');
      } else {
        window.location.href = url;
      }
    }
  }, true);
}

function initLang(page = 'landing', defaultLang = 'es') {
  var storedLang = localStorage.getItem('lang');
  var browserLang = (navigator.language || '').split('-')[0];
  var requested = storedLang != null ? storedLang : (supportedLangs.includes(browserLang) ? browserLang : defaultLang);
  var lang = supportedLangs.includes(requested) ? requested : defaultLang;

  appendPdfLangToLinks(lang);
  setLang(page, lang);
}

if (document.body) {
  initPdfMousedown();
  initPdfLangClick();
} else {
  document.addEventListener('DOMContentLoaded', function () {
    initPdfMousedown();
    initPdfLangClick();
  });
}
