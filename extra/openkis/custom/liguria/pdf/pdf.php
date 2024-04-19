<?php

global $_FN;
require_once "./include/flatnux.php";
require_once "./include/html2pdf4/html2pdf.class.php";
require_once __DIR__ . "/pdf_functions.php";

$num = FN_GetParam("id", $_GET, "flat");

$html = get_html_pdf($num);
if (isset($_GET["debug"]))
{
    FN_SaveFile($filecontents, $filename);
    dprint_xml($html);
    echo $html;
}
else
{
    $num = $num;
    $Anum = intval($num);
    if ($num < 10)
        $Anum = "000" . $Anum;
    if ($num < 100)
        $Anum = "00" . $Anum;
    if ($num < 1000)
        $Anum = "0" . $Anum;
    $filename = "scheda_catastale_{$Anum}__" . date("Y-m-d-") . time() . ".pdf";

    $content = $html;
    
    
    while (false !== @ob_end_clean()
    );
    $html2pdf = new HTML2PDF('P', 'A4', "en", true, "UTF-8", array(0, 0, 0, 0));
    //$html2pdf->setEncoding($_FN['charset_page']);
    $html2pdf->setDefaultFont('Arial');
    $html2pdf->WriteHTML($content, isset($_GET['viewhtml']));
    ob_get_clean();
    $html2pdf->Output($filename);
}
?>