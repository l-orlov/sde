<?php
/**
 * Translate company/product texts to English via Gemini and optionally update DB.
 * Used after saving company/products in regfull, home profile, admin full data, approve moderation.
 */

/**
 * Translate an array of texts to English using Gemini API.
 * Returns array of translated strings (same order); on failure or empty input returns [].
 *
 * @param array $texts Non-empty strings to translate (Spanish or other)
 * @param string $apiKey Gemini API key
 * @return array Translated strings, one per input; or empty array on error
 */
function gemini_translate_to_en(array $texts, $apiKey) {
    $texts = array_values($texts);
    if (empty($texts)) {
        return [];
    }
    $apiKey = trim((string) $apiKey);
    if ($apiKey === '') {
        return [];
    }

    $inputLines = implode("\n", $texts);
    $prompt = "Translate each of the following lines to English. "
        . "Reply with ONLY the translations, one per line, in the exact same order. "
        . "Do not number the lines. If a line is already in English, return it unchanged.\n\n"
        . $inputLines;

    $payload = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [['text' => $prompt]]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.2,
            'maxOutputTokens' => 4096,
        ]
    ];

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . urlencode($apiKey);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr !== '') {
        error_log("gemini_translate_to_en: curl error: " . $curlErr);
        return [];
    }
    if ($httpCode !== 200) {
        error_log("gemini_translate_to_en: HTTP " . $httpCode . " " . substr($response, 0, 500));
        return [];
    }

    $data = json_decode($response, true);
    if (!is_array($data) || !isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        error_log("gemini_translate_to_en: invalid or empty response");
        return [];
    }

    $out = trim($data['candidates'][0]['content']['parts'][0]['text']);
    $lines = preg_split('/\r\n|\r|\n/', $out, -1, PREG_SPLIT_NO_EMPTY);
    $lines = array_map('trim', $lines);

    // Pad if Gemini returned fewer lines
    while (count($lines) < count($texts)) {
        $lines[] = '';
    }
    return array_slice($lines, 0, count($texts));
}

/**
 * Load company + products for the given company_id, translate name/main_activity/product names to EN, update DB.
 * Uses same Gemini key as config. On API or DB error only logs; does not throw.
 *
 * @param mysqli $link DB connection
 * @param int $companyId companies.id
 */
function refresh_company_products_en($link, $companyId) {
    $companyId = (int) $companyId;
    if ($companyId <= 0) {
        return;
    }

    $configPath = __DIR__ . '/config/config.php';
    if (!is_file($configPath)) {
        return;
    }
    $config = require $configPath;
    $apiKey = isset($config['gemini_api_key']) ? trim((string) $config['gemini_api_key']) : '';
    if ($apiKey === '') {
        return;
    }

    // Check EN columns exist
    $r = mysqli_query($link, "SHOW COLUMNS FROM companies LIKE 'name_en'");
    if (!($r && mysqli_num_rows($r) > 0)) {
        return;
    }
    mysqli_free_result($r);
    $r = mysqli_query($link, "SHOW COLUMNS FROM products LIKE 'name_en'");
    if (!($r && mysqli_num_rows($r) > 0)) {
        return;
    }
    mysqli_free_result($r);
    $r = mysqli_query($link, "SHOW COLUMNS FROM products LIKE 'description_en'");
    if (!($r && mysqli_num_rows($r) > 0)) {
        return;
    }
    mysqli_free_result($r);

    $stmt = mysqli_prepare($link, "SELECT id, name, main_activity FROM companies WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return;
    }
    mysqli_stmt_bind_param($stmt, 'i', $companyId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $company = $res && mysqli_num_rows($res) > 0 ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    if (!$company) {
        return;
    }

    $name = isset($company['name']) ? trim((string) $company['name']) : '';
    $mainActivity = isset($company['main_activity']) ? trim((string) $company['main_activity']) : '';

    $products = [];
    $stmt = mysqli_prepare($link, "SELECT id, name, description, annual_export, certifications FROM products WHERE company_id = ? AND (deleted_at IS NULL OR deleted_at = 0) ORDER BY id ASC");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $companyId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($res && ($row = mysqli_fetch_assoc($res))) {
            $products[] = $row;
        }
        mysqli_stmt_close($stmt);
    }

    $singleLine = function ($s) {
        $s = trim((string) $s);
        if ($s === '') return '';
        return preg_replace('/\s+/', ' ', $s);
    };

    $texts = [];
    $texts[] = $name !== '' ? $name : '(no name)';
    $texts[] = $mainActivity !== '' ? $mainActivity : '(none)';
    foreach ($products as $p) {
        $texts[] = isset($p['name']) && trim((string) $p['name']) !== '' ? trim($p['name']) : '(no name)';
        $texts[] = $singleLine($p['description'] ?? '') ?: '(none)';
        $texts[] = $singleLine($p['annual_export'] ?? '') ?: '(none)';
        $texts[] = $singleLine($p['certifications'] ?? '') ?: '(none)';
    }

    $translated = gemini_translate_to_en($texts, $apiKey);
    if (count($translated) < 2) {
        return;
    }

    $nameEn = isset($translated[0]) ? trim($translated[0]) : null;
    if ($nameEn === '(no name)' || $nameEn === '') {
        $nameEn = null;
    }
    $mainActivityEn = isset($translated[1]) ? trim($translated[1]) : null;
    if ($mainActivityEn === '(none)' || $mainActivityEn === '') {
        $mainActivityEn = null;
    }

    $stmt = mysqli_prepare($link, "UPDATE companies SET name_en = ?, main_activity_en = ?, updated_at = UNIX_TIMESTAMP() WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssi', $nameEn, $mainActivityEn, $companyId);
        if (!mysqli_stmt_execute($stmt)) {
            error_log("refresh_company_products_en: UPDATE companies failed: " . mysqli_error($link));
        }
        mysqli_stmt_close($stmt);
    }

    $idx = 2;
    foreach ($products as $p) {
        $pid = (int) $p['id'];
        $pNameEn = isset($translated[$idx]) ? trim($translated[$idx]) : null;
        $idx++;
        if ($pNameEn === '(no name)' || $pNameEn === '') {
            $pNameEn = null;
        }
        $pDescEn = isset($translated[$idx]) ? trim($translated[$idx]) : null;
        $idx++;
        if ($pDescEn === '(none)' || $pDescEn === '') {
            $pDescEn = null;
        }
        $pAnnualEn = isset($translated[$idx]) ? trim($translated[$idx]) : null;
        $idx++;
        if ($pAnnualEn === '(none)' || $pAnnualEn === '') {
            $pAnnualEn = null;
        }
        $pCertEn = isset($translated[$idx]) ? trim($translated[$idx]) : null;
        $idx++;
        if ($pCertEn === '(none)' || $pCertEn === '') {
            $pCertEn = null;
        }
        $st = mysqli_prepare($link, "UPDATE products SET name_en = ?, description_en = ?, annual_export_en = ?, certifications_en = ?, updated_at = UNIX_TIMESTAMP() WHERE id = ?");
        if ($st) {
            mysqli_stmt_bind_param($st, 'ssssi', $pNameEn, $pDescEn, $pAnnualEn, $pCertEn, $pid);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
        }
    }
}
