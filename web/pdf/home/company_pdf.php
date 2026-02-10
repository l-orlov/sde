<?php
/**
 * Entrada para PDF de presentación de empresa. Elige al azar diseño D1 o D2.
 * Opcional: ?design=D1 o ?design=D2 para forzar uno (lo aplica el script incluido).
 */
// TODO: volver a aleatorio para producción: (mt_rand(0, 1) === 0) ? 'd1' : 'd2'
$design = 'd3';
require __DIR__ . '/company_pdf_' . $design . '.php';
