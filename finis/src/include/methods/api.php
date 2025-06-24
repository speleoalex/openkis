<?php
if (empty($_GET["debug"]))
    ob_start();
global $_FN;
$_FN['maintenance'] = 0;
header("Cache-Control: no-cache");
header("Pragma: no-cache");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (defined("DEBUG_TIME") && DEBUG_TIME == true)
{
    dprint_r("pre-section " . FN_GetPartialTimer());
}
$html = FN_HtmlSection();
if (defined("DEBUG_TIME") && DEBUG_TIME == true)
{
    echo ("<hr>");
    echo ($html);
    echo ("<hr>");
}
if (file_exists("sections/{$_FN['mod']}/footer.php"))
{
    ob_start();
    include("sections/{$_FN['mod']}/footer.php");
    $strfooter = ob_get_clean();
    $str = str_replace("</body>", $strfooter . "</body>", $str);
}
if (defined("DEBUG_TIME") && DEBUG_TIME == true)
{
    dprint_r(FN_GetPartialTimer());
}
if (!empty($_GET["debug"]))
{
    echo @ob_get_flush();
    echo ($html);

    $query_string = $_SERVER['QUERY_STRING'];
    $query_string = str_replace("debug=1&", "", $query_string);
    echo "<h2>{$_FN['mod']} - {$_FN['sectionvalues']['title']}</h2>";
    echo "<h3>{$_FN['sectionvalues']['description']}</h3>";

    echo "<p>URL API (query string server side):</p>";
    echo ($_FN['siteurl'] . "apijson.php?$query_string");
    echo "<p>URL API:</p>";
    echo ($_FN['siteurl'] . "api/v1/{$_FN['mod']}/");
    $op = FN_GetParam("op", $_GET);
    if ($op)
    {
        $id = FN_GetParam("id", $_GET);
        echo "$op/";
        if ($id)
        {
            echo "$id/";
        }
    }

    echo "<h2>GET</h2>";
    foreach ($_GET as $k => $v)
    {
        if ($k != "debug")
            echo "<b>$k = </b> " . htmlentities($v) . "<br />";
    }
    if (count($_POST))
    {
        echo "<h1>POST</h1>";
        foreach ($_POST as $k => $v)
        {
            if (is_array($v))
            {
                echo "<b>$k = </b> " . print_r($v, true) . "<br />";
            }
            else
            {
                if ($k != "debug")
                    echo "<b>$k = </b> " . htmlentities($v) . "<br />";
            }
        }
    }
    if (defined("DEBUG_TIME") && DEBUG_TIME == true)
    {
        dprint_r(FN_GetPartialTimer());
    }
    echo "<h2>RETURN (json)</h2>";
    echo "<pre>";
    echo htmlspecialchars(json_encode($_FN['return'], JSON_PRETTY_PRINT));
    echo "<h2>RETURN (php)</h2>";
    print_r($_FN['return']);
    echo "</pre>";
}
else
{
    //file_put_contents("misc/log.txt", ob_get_contents(),FILE_APPEND);
    @ob_end_clean();
    echo json_encode($_FN['return'], JSON_PRETTY_PRINT);
}
if (!empty($_GET["debug"]))
{
    dprint_r("Total time:" . FN_GetExecuteTimer());
}