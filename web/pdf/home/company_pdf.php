<?php
/**
 * Entrada para PDF de presentación de empresa. Elige al azar diseño o uno por parámetro.
 * ?design=d1 → Clásico, ?design=d2 → Corporativo, ?design=d3 → Moderno
 */
$designMap = ['d1' => 'clasico_company', 'd2' => 'corporativo_company', 'd3' => 'moderno_company'];
$design = isset($_GET['design']) && isset($designMap[$_GET['design']]) ? $_GET['design'] : array_rand($designMap);
$design = is_int($design) ? ['d1', 'd2', 'd3'][$design] : $design;
require __DIR__ . '/' . $designMap[$design] . '.php';
