<?php
/**
 * Helper for English PDF: format current_markets / target_markets for display.
 * Uses _en JSON when present, else translates known Spanish names to English, else original.
 * Include once in each *_en PDF before building market strings.
 */

if (!function_exists('pdf_en_markets_display_string')) {
    /** Known Spanish → English region/market names (for PDF EN when _en not yet filled) */
    $GLOBALS['_pdf_en_markets_map'] = [
        // Regions
        'América del Sur'  => 'South America',
        'America del Sur'  => 'South America',
        'Sudamérica'       => 'South America',
        'Sudamerica'       => 'South America',
        'Europa'           => 'Europe',
        'América del Norte'=> 'North America',
        'America del Norte'=> 'North America',
        'Norteamérica'     => 'North America',
        'Norteamerica'     => 'North America',
        'América Latina'   => 'Latin America',
        'America Latina'   => 'Latin America',
        'Latinoamérica'    => 'Latin America',
        'Latinoamerica'    => 'Latin America',
        'Asia'             => 'Asia',
        'Oceanía'          => 'Oceania',
        'Oceania'          => 'Oceania',
        'África'           => 'Africa',
        'Africa'           => 'Africa',
        'Caribe'           => 'Caribbean',
        'Centroamérica'    => 'Central America',
        'Centroamerica'    => 'Central America',
        'Medio Oriente'    => 'Middle East',
        'Lejano Oriente'   => 'Far East',
        'Europa Occidental'=> 'Western Europe',
        'Europa Oriental'  => 'Eastern Europe',
        // Trade blocs / unions
        'Mercosur'         => 'Mercosur',
        'Unión Europea'    => 'European Union',
        'Union Europea'    => 'European Union',
        'UE'               => 'European Union',
        'ALADI'            => 'LAIA',
        'Alianza del Pacífico' => 'Pacific Alliance',
        'Alianza del Pacifico' => 'Pacific Alliance',
        // Americas
        'Estados Unidos'   => 'United States',
        'EE. UU.'          => 'United States',
        'EE.UU.'           => 'United States',
        'México'           => 'Mexico',
        'Mexico'           => 'Mexico',
        'Canadá'           => 'Canada',
        'Canada'           => 'Canada',
        'Brasil'           => 'Brazil',
        'Chile'            => 'Chile',
        'Colombia'         => 'Colombia',
        'Perú'             => 'Peru',
        'Peru'             => 'Peru',
        'Argentina'        => 'Argentina',
        'Uruguay'          => 'Uruguay',
        'Paraguay'         => 'Paraguay',
        'Bolivia'          => 'Bolivia',
        'Ecuador'          => 'Ecuador',
        'Venezuela'        => 'Venezuela',
        'Costa Rica'       => 'Costa Rica',
        'Panamá'           => 'Panama',
        'Panama'           => 'Panama',
        'Nicaragua'        => 'Nicaragua',
        'Honduras'         => 'Honduras',
        'El Salvador'      => 'El Salvador',
        'Guatemala'        => 'Guatemala',
        'Belice'           => 'Belize',
        'Cuba'             => 'Cuba',
        'República Dominicana' => 'Dominican Republic',
        'Republica Dominicana' => 'Dominican Republic',
        'Puerto Rico'      => 'Puerto Rico',
        'Trinidad y Tobago'=> 'Trinidad and Tobago',
        'Jamaica'          => 'Jamaica',
        // Europe
        'Reino Unido'      => 'United Kingdom',
        'España'           => 'Spain',
        'Espana'           => 'Spain',
        'Francia'          => 'France',
        'Alemania'         => 'Germany',
        'Italia'           => 'Italy',
        'Países Bajos'     => 'Netherlands',
        'Paises Bajos'     => 'Netherlands',
        'Holanda'          => 'Netherlands',
        'Bélgica'          => 'Belgium',
        'Belgica'          => 'Belgium',
        'Portugal'         => 'Portugal',
        'Suiza'            => 'Switzerland',
        'Austria'          => 'Austria',
        'Polonia'          => 'Poland',
        'Suecia'           => 'Sweden',
        'Noruega'          => 'Norway',
        'Dinamarca'        => 'Denmark',
        'Finlandia'        => 'Finland',
        'Irlanda'          => 'Ireland',
        'Grecia'           => 'Greece',
        'Rusia'            => 'Russia',
        'Federación Rusa'   => 'Russian Federation',
        'Federacion Rusa'  => 'Russian Federation',
        'Ucrania'          => 'Ukraine',
        'República Checa'  => 'Czech Republic',
        'Republica Checa'  => 'Czech Republic',
        'Hungría'          => 'Hungary',
        'Hungria'          => 'Hungary',
        'Rumania'          => 'Romania',
        'Bulgaria'         => 'Bulgaria',
        'Turquía'          => 'Turkey',
        'Turquia'          => 'Turkey',
        // Asia-Pacific
        'China'            => 'China',
        'Japón'            => 'Japan',
        'Japon'            => 'Japan',
        'Corea del Sur'    => 'South Korea',
        'Corea del Norte'  => 'North Korea',
        'India'            => 'India',
        'Indonesia'        => 'Indonesia',
        'Tailandia'        => 'Thailand',
        'Vietnam'          => 'Vietnam',
        'Viet Nam'         => 'Vietnam',
        'Filipinas'        => 'Philippines',
        'Malasia'          => 'Malaysia',
        'Singapur'         => 'Singapore',
        'Taiwán'           => 'Taiwan',
        'Taiwan'           => 'Taiwan',
        'Hong Kong'        => 'Hong Kong',
        'Emiratos Árabes Unidos' => 'United Arab Emirates',
        'Emiratos Arabes Unidos' => 'United Arab Emirates',
        'Arabia Saudita'   => 'Saudi Arabia',
        'Israel'           => 'Israel',
        'Pakistán'         => 'Pakistan',
        'Pakistan'         => 'Pakistan',
        'Bangladesh'       => 'Bangladesh',
        'Australia'        => 'Australia',
        'Nueva Zelanda'    => 'New Zealand',
        // Africa
        'Sudáfrica'        => 'South Africa',
        'Sudafrica'        => 'South Africa',
        'Egipto'           => 'Egypt',
        'Marruecos'        => 'Morocco',
        'Nigeria'          => 'Nigeria',
        'Kenia'            => 'Kenya',
        'Argelia'          => 'Algeria',
        'Túnez'            => 'Tunisia',
        'Tunez'            => 'Tunisia',
    ];

    /**
     * Build display string for current_markets or target_markets in English PDF.
     * @param string|array|null $raw Original JSON string or decoded array
     * @param string|array|null $rawEn Optional _en JSON string or decoded array
     * @return string Comma-separated for display; '-' if empty
     */
    function pdf_en_markets_display_string($raw, $rawEn = null) {
        $map = $GLOBALS['_pdf_en_markets_map'] ?? [];
        $list = [];
        $useEn = null;
        if (!empty($rawEn)) {
            $dec = is_string($rawEn) ? json_decode($rawEn, true) : $rawEn;
            if (is_array($dec)) {
                foreach ($dec as $m) {
                    $list[] = is_array($m) ? trim((string)($m['name'] ?? $m['nombre'] ?? '')) : trim((string)$m);
                }
                $useEn = true;
            }
        }
        if (!empty($list) && $useEn) {
            $str = implode(', ', array_filter($list));
            return $str !== '' ? $str : '-';
        }
        if (!empty($raw)) {
            $dec = is_string($raw) ? json_decode($raw, true) : $raw;
            if (is_array($dec)) {
                foreach ($dec as $m) {
                    $name = is_array($m) ? ($m['nombre'] ?? $m['name'] ?? '') : (string)$m;
                    $name = trim((string)$name);
                    $list[] = isset($map[$name]) ? $map[$name] : $name;
                }
            } else {
                $list[] = is_string($raw) ? trim($raw) : (string)$raw;
            }
        }
        $str = implode(', ', array_filter($list));
        return $str !== '' ? $str : '-';
    }
}
