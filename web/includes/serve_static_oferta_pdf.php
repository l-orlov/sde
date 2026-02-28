<?php
/**
 * Раздача статического PDF «Oferta exportable» (modelo_Clasico_v2.pdf).
 * Вызывается из index.php при page=download_oferta_pdf, чтобы не зависеть от маршрутизации статики.
 */
$pdfFile = __DIR__ . '/../pdf/modelo_Clasico_v2.pdf';
if (!is_file($pdfFile) || !is_readable($pdfFile)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('PDF not found');
}
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($pdfFile));
header('Cache-Control: public, max-age=86400');
header('Content-Disposition: inline; filename="modelo_Clasico_v2.pdf"');
readfile($pdfFile);
exit;
