<?php
global $_FN;
require_once __DIR__ . "/pdf_functions.php";

$num = FN_GetParam("id", $_GET, "flat");
$format = isset($_GET['format']) ? $_GET['format'] : '';

if ($format === 'pdf') {
    // Genera PDF diretto
    require_once "{$_FN['src_finis']}/include/html2pdf4/html2pdf.class.php";
    $content = get_pdf_content($num);

    if (isset($_GET["debug"])) {
        echo $content;
        die();
    }

    $Anum = intval($num);
    if ($num < 10) $Anum = "000" . $Anum;
    elseif ($num < 100) $Anum = "00" . $Anum;
    elseif ($num < 1000) $Anum = "0" . $Anum;
    $filename = "scheda_catastale_{$Anum}__" . date("Y-m-d-") . time() . ".pdf";

    while (false !== @ob_end_clean());
    $html2pdf = new HTML2PDF('P', 'A4', "en", true, "UTF-8", array(10, 10, 10, 10));
    $html2pdf->setDefaultFont('Arial');
    $html2pdf->WriteHTML($content, isset($_GET['viewhtml']));
    @ob_get_clean();
    $html2pdf->Output($filename);
} else {
    // Pagina HTML stampabile
    $html = get_html_pdf($num);
    while (false !== @ob_end_clean());
    echo $html;
}
