<?php
/**
 * Раздача статического PDF «Oferta exportable» по языку: oferta_exportable_es.pdf / oferta_exportable_en.pdf.
 * Вызывается из index.php при page=download_oferta_pdf&lang=es|en.
 */
$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'es';
$filename = $lang === 'en' ? 'oferta_exportable_en.pdf' : 'oferta_exportable_es.pdf';
$pdfFile = __DIR__ . '/../pdf/' . $filename;
if (!is_file($pdfFile) || !is_readable($pdfFile)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('PDF not found');
}
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($pdfFile));
header('Cache-Control: public, max-age=86400');
header('Content-Disposition: inline; filename="' . $filename . '"');
readfile($pdfFile);
exit;
