const supportedLangs = ['es', 'en'];

function normalizeLang(lang) {
  const s = String(lang == null ? 'es' : lang).trim().toLowerCase();
  return supportedLangs.includes(s) ? s : 'es';
}

/** База приложения (напр. /sde/web) из пути к подключённому i18n.js — для корректного fetch JSON при нестандартном DOCUMENT_ROOT */
function getWebRootFromI18nScript() {
  try {
    const s = document.querySelector('script[src*="i18n.js"]');
    if (!s || !s.src) return '';
    const u = new URL(s.src, window.location.href);
    const p = u.pathname;
    const ix = p.indexOf('/js/i18n.js');
    if (ix === -1) return '';
    return p.slice(0, ix);
  } catch (e) {
    return '';
  }
}

function buildLangJsonUrl(page, lang, v) {
  const root = getWebRootFromI18nScript().replace(/\/$/, '');
  const path = `lang/${page}/${lang}.json`;
  const base = root ? `${root}/` : '';
  return base + path + (v ? '?v=' + encodeURIComponent(v) : '');
}

async function setLang(page, lang) {
  const langNorm = normalizeLang(lang);
  const currentLangEl = document.getElementById('current-lang');
  if (currentLangEl) {
    currentLangEl.textContent = langNorm.toUpperCase();
  }

  localStorage.setItem('lang', langNorm);

  try {
    let v = '';
    try {
      const s = document.querySelector('script[src*="i18n.js"]');
      if (s && s.src) {
        const m = s.src.match(/[?&]v=([^&]+)/);
        v = m ? m[1] : '';
      }
    } catch (e) {}
    let url = buildLangJsonUrl(page, langNorm, v);
    let res = await fetch(url);
    let fallbackLang = (langNorm === 'es') ? 'en' : 'es';

    if (!res.ok) {
      url = buildLangJsonUrl(page, fallbackLang, v);
      res = await fetch(url);
      if (!res.ok) {
        throw new Error(`Failed to load lang file, status ${res.status}`);
      }
    }

    const dict = await res.json();

    // Обычные тексты
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (key && Object.prototype.hasOwnProperty.call(dict, key) && dict[key] != null) {
        // Проверяем, есть ли внутри элемента звездочка с классом .req
        const reqSpan = el.querySelector('.req');
        const hasAsterisk = !!reqSpan;
        
        // Сохраняем звездочку, если она есть
        const asterisk = hasAsterisk ? reqSpan.outerHTML : null;
        
        // Получаем переведенный текст и удаляем из него все звездочки
        let translatedText = String(dict[key]);
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
      if (key && Object.prototype.hasOwnProperty.call(dict, key) && dict[key] != null) {
        el.setAttribute('placeholder', String(dict[key]));
      }
    });

    document.querySelectorAll('[data-i18n-alt]').forEach(el => {
      const key = el.getAttribute('data-i18n-alt');
      if (key && Object.prototype.hasOwnProperty.call(dict, key) && dict[key] != null) {
        el.setAttribute('alt', String(dict[key]));
      }
    });

    // HTML-тексты
    document.querySelectorAll('[data-i18n-html]').forEach(el => {
      const key = el.getAttribute('data-i18n-html');
      if (key && Object.prototype.hasOwnProperty.call(dict, key) && dict[key] != null) {
        el.innerHTML = String(dict[key]);
      }
    });

    document.querySelectorAll('[data-bilingual-es]').forEach(el => {
      const es = el.getAttribute('data-bilingual-es') ?? '';
      const en = el.getAttribute('data-bilingual-en');
      const useEn = langNorm === 'en' && en != null && String(en).trim() !== '';
      el.textContent = useEn ? en : es;
    });

    document.querySelectorAll('[data-bilingual-alt-es]').forEach(el => {
      const es = el.getAttribute('data-bilingual-alt-es') ?? '';
      const en = el.getAttribute('data-bilingual-alt-en');
      const useEn = langNorm === 'en' && en != null && String(en).trim() !== '';
      el.setAttribute('alt', useEn ? en : es);
    });

    window.__i18nDict = dict;
    appendPdfLangToLinks(langNorm);
  } catch (err) {
    console.error(`Language load error for ${langNorm}:`, err);
  }
}

/** Текущий язык для PDF: только 'en' или 'es'. */
function getPdfLang() {
  var raw = (localStorage.getItem('lang') || 'es').toString().trim().toLowerCase();
  return (raw === 'en') ? 'en' : 'es';
}

/** Ставит href у ссылок .js-pdf-link по выбранному языку (data-pdf-url-es / data-pdf-url-en). */
function appendPdfLangToLinks(lang) {
  var safeLang = (normalizeLang(lang) === 'en') ? 'en' : 'es';
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
  var browserLang = ((navigator.language || '').split('-')[0] || '').toLowerCase();
  var requested = storedLang != null ? storedLang : (supportedLangs.includes(browserLang) ? browserLang : defaultLang);
  var lang = normalizeLang(requested);

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
