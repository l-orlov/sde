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
    $prompt = "You are a translator from Spanish to English. Translate each of the following lines to English.\n\n"
        . "Rules:\n"
        . "- Translate ALL Spanish words and phrases to English, including text in parentheses (e.g. \"(la capital)\" → \"(the capital)\", \"A completar\" → \"To be completed\").\n"
        . "- Keep proper nouns (place names, company names) but use standard English spelling where applicable (e.g. México → Mexico, Perú → Peru).\n"
        . "- Translate common abbreviations: PyME → SME, EE. UU. → United States.\n"
        . "- If a line is already in English, return it unchanged.\n"
        . "- Reply with ONLY the translated lines, one per line, in the exact same order. Do not number or add any other text.\n\n"
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

    $stmt = mysqli_prepare($link, "SELECT id, name, main_activity, organization_type FROM companies WHERE id = ? LIMIT 1");
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
    $orgType = isset($company['organization_type']) ? trim((string) $company['organization_type']) : '';

    $firstAddress = null;
    $stmt = mysqli_prepare($link, "SELECT id, locality, department FROM company_addresses WHERE company_id = ? ORDER BY id ASC LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $companyId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res && mysqli_num_rows($res) > 0) {
            $firstAddress = mysqli_fetch_assoc($res);
        }
        mysqli_stmt_close($stmt);
    }

    $firstContact = null;
    $stmt = mysqli_prepare($link, "SELECT id, position FROM company_contacts WHERE company_id = ? ORDER BY id ASC LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $companyId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res && mysqli_num_rows($res) > 0) {
            $firstContact = mysqli_fetch_assoc($res);
        }
        mysqli_stmt_close($stmt);
    }

    $products = [];
    $productCols = "id, name, description, annual_export, certifications, current_markets, target_markets";
    $stmt = mysqli_prepare($link, "SELECT $productCols FROM products WHERE company_id = ? AND (deleted_at IS NULL OR deleted_at = 0) ORDER BY id ASC");
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

    $extractMarketNames = function ($raw) {
        $names = [];
        if ($raw === null || $raw === '') return $names;
        $dec = is_string($raw) ? json_decode($raw, true) : $raw;
        if (is_array($dec)) {
            foreach ($dec as $m) {
                $n = is_array($m) ? trim((string)($m['nombre'] ?? $m['name'] ?? '')) : trim((string)$m);
                if ($n !== '') $names[] = $n;
            }
        } else {
            // current_markets can be stored as plain string (e.g. "América del Sur") from the form
            $one = trim((string) $raw);
            if ($one !== '') $names[] = $one;
        }
        return $names;
    };

    $uniqueMarkets = [];
    foreach ($products as $p) {
        foreach ($extractMarketNames($p['current_markets'] ?? null) as $n) { $uniqueMarkets[$n] = true; }
        foreach ($extractMarketNames($p['target_markets'] ?? null) as $n) { $uniqueMarkets[$n] = true; }
    }
    $uniqueMarketsOrdered = array_keys($uniqueMarkets);

    $texts = [];
    $texts[] = $name !== '' ? $name : '(no name)';
    $texts[] = $mainActivity !== '' ? $mainActivity : '(none)';
    foreach ($products as $p) {
        $texts[] = isset($p['name']) && trim((string) $p['name']) !== '' ? trim($p['name']) : '(no name)';
        $texts[] = $singleLine($p['description'] ?? '') ?: '(none)';
        $texts[] = $singleLine($p['annual_export'] ?? '') ?: '(none)';
        $texts[] = $singleLine($p['certifications'] ?? '') ?: '(none)';
    }
    foreach ($uniqueMarketsOrdered as $m) {
        $texts[] = $m;
    }
    $companyExtraStart = count($texts);
    $texts[] = $orgType !== '' ? $orgType : '(none)';
    $texts[] = ($firstAddress && trim((string)($firstAddress['locality'] ?? '')) !== '') ? trim($firstAddress['locality']) : '(none)';
    $texts[] = ($firstAddress && trim((string)($firstAddress['department'] ?? '')) !== '') ? trim($firstAddress['department']) : '(none)';
    $texts[] = ($firstContact && trim((string)($firstContact['position'] ?? '')) !== '') ? trim($firstContact['position']) : '(none)';

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

    $orgTypeEn = null;
    $localityEn = null;
    $departmentEn = null;
    $positionEn = null;
    if ($companyExtraStart + 4 <= count($translated)) {
        $o = trim($translated[$companyExtraStart] ?? '');
        if ($o !== '' && $o !== '(none)') $orgTypeEn = $o;
        $loc = trim($translated[$companyExtraStart + 1] ?? '');
        if ($loc !== '' && $loc !== '(none)') $localityEn = $loc;
        $dept = trim($translated[$companyExtraStart + 2] ?? '');
        if ($dept !== '' && $dept !== '(none)') $departmentEn = $dept;
        $pos = trim($translated[$companyExtraStart + 3] ?? '');
        if ($pos !== '' && $pos !== '(none)') $positionEn = $pos;
    }

    $stmt = mysqli_prepare($link, "UPDATE companies SET name_en = ?, main_activity_en = ?, organization_type_en = ?, updated_at = UNIX_TIMESTAMP() WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sssi', $nameEn, $mainActivityEn, $orgTypeEn, $companyId);
        if (!mysqli_stmt_execute($stmt)) {
            error_log("refresh_company_products_en: UPDATE companies failed: " . mysqli_error($link));
        }
        mysqli_stmt_close($stmt);
    }
    if ($firstAddress && ($localityEn !== null || $departmentEn !== null)) {
        $aid = (int) $firstAddress['id'];
        $stmt = mysqli_prepare($link, "UPDATE company_addresses SET locality_en = ?, department_en = ? WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssi', $localityEn, $departmentEn, $aid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    if ($firstContact && $positionEn !== null) {
        $cid = (int) $firstContact['id'];
        $stmt = mysqli_prepare($link, "UPDATE company_contacts SET position_en = ? WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $positionEn, $cid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
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

        $marketsStartIdx = 2 + count($products) * 4;
        $marketMap = [];
        foreach ($uniqueMarketsOrdered as $i => $m) {
            $marketMap[$m] = isset($translated[$marketsStartIdx + $i]) ? trim($translated[$marketsStartIdx + $i]) : $m;
        }
        $buildEnJson = function ($raw) use ($marketMap) {
            if ($raw === null || $raw === '') return '[]';
            $dec = is_string($raw) ? json_decode($raw, true) : $raw;
            $items = [];
            if (is_array($dec)) {
                foreach ($dec as $m) {
                    $name = is_array($m) ? trim((string)($m['nombre'] ?? $m['name'] ?? '')) : trim((string)$m);
                    if ($name === '') continue;
                    $items[] = isset($marketMap[$name]) ? $marketMap[$name] : $name;
                }
            } else {
                $one = trim((string) $raw);
                if ($one !== '') {
                    $items[] = isset($marketMap[$one]) ? $marketMap[$one] : $one;
                }
            }
            return json_encode($items);
        };
        $currentMarketsEnJson = $buildEnJson($p['current_markets'] ?? null);
        $targetMarketsEnJson = $buildEnJson($p['target_markets'] ?? null);

        $st = mysqli_prepare($link, "UPDATE products SET name_en = ?, description_en = ?, annual_export_en = ?, certifications_en = ?, current_markets_en = ?, target_markets_en = ?, updated_at = UNIX_TIMESTAMP() WHERE id = ?");
        if ($st) {
            mysqli_stmt_bind_param($st, 'ssssssi', $pNameEn, $pDescEn, $pAnnualEn, $pCertEn, $currentMarketsEnJson, $targetMarketsEnJson, $pid);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
        }
    }
}
