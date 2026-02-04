<?php
/**
 * Entrada para PDF de presentación de empresa. Dos estilos: D1 y D2.
 * Opcional: ?design=D1 o ?design=D2 para elegir uno; si no se pasa, se elige al azar.
 */
$designParam = isset($_GET['design']) ? strtolower(trim($_GET['design'])) : '';
$design = ($designParam === 'd1' || $designParam === 'd2') ? $designParam : (mt_rand(0, 1) === 0 ? 'd1' : 'd2');
require __DIR__ . '/company_pdf_' . $design . '.php';
