<?
/**
 * Версия ассета по времени изменения файла и версии приложения (для cache busting).
 * $path — путь относительно корня web/ (например 'css/style.css', 'js/i18n.js', 'admin/css/style.css').
 */
function asset_version($path) {
    $webRoot = dirname(__DIR__);
    $full = $webRoot . '/' . ltrim($path, '/');
    $mtime = file_exists($full) ? (string) filemtime($full) : (string) time();
    static $appVersion = null;
    if ($appVersion === null) {
        $configPath = dirname(__DIR__) . '/includes/config/config.php';
        $config = file_exists($configPath) ? (require $configPath) : [];
        $appVersion = $config['app_version'] ?? '1';
    }
    return $appVersion . '.' . $mtime;
}

function DBconnect() {
    global $link;

    // Load config
    $configPath = __DIR__ . '/config/config.php';
    if (!file_exists($configPath)) {
        die("Error: Configuration file not found.");
    }
    $config = require $configPath;

    // Check if the parameters are loaded
    if (!isset($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_database'], $config['db_port'])) {
        die("Error: The configuration file is corrupted or incomplete.");
    }

    // Extract database connection details
    $db_host = $config['db_host'];
    $db_user = $config['db_user'];
    $db_pass = $config['db_pass'];
    $db_database = $config['db_database'];
    $db_port = $config['db_port']; // Ensure we use the port

    // Connect to MySQL with port
    $link = mysqli_connect($db_host, $db_user, $db_pass, $db_database, $db_port);

    // Check connection
    if (!$link) {
        die("Unable to establish a DB connection: " . mysqli_connect_error());
    }

    mysqli_set_charset($link, "utf8");
}

// Removes all non-digit characters from a phone number
function clear_phone($phone) {
    return preg_replace('/\D/', '', $phone);
}

/**
 * URL пути к serve_file.php для раздачи загруженных файлов по ID.
 * Учитывает web_base из config (если сайт в подпапке, напр. /sde).
 * $file_id — ID записи в таблице files.
 */
function get_serve_file_url($file_id) {
    static $web_base = null;
    if ($web_base === null) {
        $configPath = __DIR__ . '/config/config.php';
        $config = file_exists($configPath) ? (require $configPath) : [];
        $web_base = rtrim($config['web_base'] ?? '', '/');
    }
    return $web_base . '/serve_file.php?id=' . (int) $file_id;
}

/**
 * URL для публичного доступа к изображениям товаров (лендинг, карусель).
 * Только файлы из одобренных компаний.
 */
function get_serve_file_public_url($file_id) {
    static $web_base = null;
    if ($web_base === null) {
        $configPath = __DIR__ . '/config/config.php';
        $config = file_exists($configPath) ? (require $configPath) : [];
        $web_base = rtrim($config['web_base'] ?? '', '/');
    }
    return $web_base . '/serve_file_public.php?id=' . (int) $file_id;
}

/**
 * Сколько строк займёт текст при переносе по ширине $widthMm (мм) в текущем шрифте mPDF.
 * Перед вызовом нужно выставить шрифт через $mpdf->SetFont(...).
 *
 * @param \Mpdf\Mpdf $mpdf
 */
function pdf_mpdf_wrapped_line_count($mpdf, float $widthMm, string $text): int {
    $text = str_replace(["\r\n", "\r"], "\n", trim($text));
    if ($text === '') {
        return 1;
    }
    $linesOut = 0;
    foreach (explode("\n", $text) as $para) {
        if ($para === '') {
            $linesOut++;
            continue;
        }
        $words = preg_split('/\s+/u', $para, -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false) {
            $words = [];
        }
        $line = '';
        foreach ($words as $w) {
            $trial = $line === '' ? $w : $line . ' ' . $w;
            if ($mpdf->GetStringWidth($trial) <= $widthMm) {
                $line = $trial;
                continue;
            }
            if ($line !== '') {
                $linesOut++;
                $line = '';
            }
            if ($mpdf->GetStringWidth($w) <= $widthMm) {
                $line = $w;
                continue;
            }
            $chars = preg_split('//u', $w, -1, PREG_SPLIT_NO_EMPTY);
            if ($chars === false) {
                $chars = [];
            }
            $chunk = '';
            foreach ($chars as $ch) {
                $t2 = $chunk . $ch;
                if ($mpdf->GetStringWidth($t2) <= $widthMm || $chunk === '') {
                    $chunk = $t2;
                } else {
                    $linesOut++;
                    $chunk = $ch;
                }
            }
            if ($chunk !== '') {
                $linesOut++;
            }
            $line = '';
        }
        if ($line !== '') {
            $linesOut++;
        }
    }
    return max(1, $linesOut);
}

/**
 * Una fila de company_contacts → texto multilínea para slides PDF (oferta exportable).
 * Solo teléfono y correo (sin nombre ni cargo).
 *
 * @param array<string,mixed> $row
 */
function pdf_company_contact_slide_value(array $row): string {
    $lines = [];
    $phone = trim(trim((string) ($row['area_code'] ?? '')) . ' ' . trim((string) ($row['phone'] ?? '')));
    if ($phone !== '') {
        $lines[] = $phone;
    }
    $em = trim((string) ($row['email'] ?? ''));
    if ($em !== '') {
        $lines[] = $em;
    }

    return $lines !== [] ? implode("\n", $lines) : '-';
}

/**
 * Primer registro de company_contacts por empresa (para slides de datos de compañía).
 *
 * @param array<int|string> $companyIds
 * @return array<int,string> company_id => texto multilínea o '-'
 */
function pdf_load_first_company_contact_strings_for_slides($link, array $companyIds): array {
    $out = [];
    if ($companyIds === [] || !$link) {
        return $out;
    }
    $check = @mysqli_query($link, "SHOW TABLES LIKE 'company_contacts'");
    if (!$check || mysqli_num_rows($check) === 0) {
        return $out;
    }
    $fields = ['company_id', 'email', 'area_code', 'phone'];
    $ids = implode(',', array_map('intval', $companyIds));
    $q = 'SELECT ' . implode(', ', $fields) . " FROM company_contacts WHERE company_id IN ($ids) ORDER BY company_id, id ASC";
    $r = @mysqli_query($link, $q);
    if (!$r) {
        return $out;
    }
    while ($row = mysqli_fetch_assoc($r)) {
        $cid = (int) ($row['company_id'] ?? 0);
        if ($cid <= 0 || isset($out[$cid])) {
            continue;
        }
        $out[$cid] = pdf_company_contact_slide_value($row);
    }

    return $out;
}
