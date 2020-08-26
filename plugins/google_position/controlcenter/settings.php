<?php
/**
 * @package Flatnux_controlcenter
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2005
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');


$searchurl = FN_GetParam("url", $_POST, "flat");
$searchquery = FN_GetParam("q", $_POST, "flat");
$total_pages_to_search = FN_GetParam("total_pages_to_search", $_POST, "flat");
if ($total_pages_to_search == "")
    $total_pages_to_search = 3;
if ($searchurl == "")
    $searchurl = $_FN['siteurl'];
echo "TEST GOOGLE POSITION";
echo "<form action=\"\" method=\"post\">";
echo "<br />URL: <input name=\"url\" size=\"40\" type=\"text\" value=\"" . htmlspecialchars($searchurl) . "\" /><br />";
echo "QUERY: <input name=\"q\" type=\"text\" value=\"" . htmlspecialchars($searchquery) . "\"/><br />";
echo "MAX PAGES: <input name=\"total_pages_to_search\" type=\"text\" value=\"$total_pages_to_search\"/><br />";
echo "<button type=\"submit\">" . FN_i18n("execute") . "</button>";
echo "</form>";
if (!empty($searchquery) && !empty($searchurl))
{
    $query = urlencode($searchquery);
    
    $found_position = 0;
    $found_page = 0;

    $lastURL = NULL;
    $position_abs = 0;
    for ($page = 0; $page < $total_pages_to_search; $page++)
    {

        $url = "http://www.google.com/search?q=$query";
        if ($page > 0)
            $url .="&start=" . ($page * 10);
        $string = getWebPage($url);
        $strings = explode('<h3 class="r">', $string);
        unset($strings[0]);
        // dprint_r($strings);
        // die();
        if (isset($strings[1]))
        {
            foreach ($strings as $string)
            {
                $string2 = urldecode($string);


                if (false !== strpos($string, $searchurl) || false !== strpos($string2, $searchurl))
                {
                    $found_position = $position_abs + 1;
                    $found_page = $page + 1;
                    break;
                }
                $position_abs++;
            }
        }
        else
        {
            print_r($string);
            echo "error request";

            return;
        }

        if ($found_position)
            break;
    }
    if ($found_position)
    {
        echo "<br /><br />";
        print("The URL $searchurl is at page $found_page, position $found_position ");
        echo "<br /><iframe src=\"$url\" width=\"600\" height=\"400\" src=\"\"></iframe";
    }
    else
    {
        echo "<br />$searchquery not found";
    }
}
/**
 *
 * @global type $_FN
 * @param type $url
 * @return type 
 */
function getWebPage($url)
{
    global $_FN;
    $options = array(
        CURLOPT_RETURNTRANSFER => true, // ritorna la pagina
        CURLOPT_HEADER => false, // non ritornare l'header
        // CURLOPT_REFERER => $url,      // settiamo il referer
        CURLOPT_FOLLOWLOCATION => true, // seguiamo i redirects
        // CURLOPT_ENCODING => FN_i18n("_CHARSET"), // tutti gli encodings
        CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Linux x86_64; rv:2.0.1) Gecko/20100101 Firefox/4.0.1", // L'identit� del browser
        CURLOPT_AUTOREFERER => true, // setta il referer nel redirect
        CURLOPT_CONNECTTIMEOUT => 120, // timeout sulla connessione
        CURLOPT_TIMEOUT => 120, // timeout sulla risposta
        CURLOPT_MAXREDIRS => 10, // fermati dopo il decimo redirect
    );
    $ch = curl_init($url);              // impostiamo l'url per il download
    curl_setopt_array($ch, $options);   //settiamo le opzioni
    $content = curl_exec($ch);          //facciamo richiesta della pagina
    $err = curl_errno($ch);
    $errmsg = curl_error($ch);
    $header = curl_getinfo($ch);
    curl_close($ch);
    $header['errno'] = $err;            //eventuali errori
    $header['errmsg'] = $errmsg;        //header
    $header['content'] = $content;      //il contenuto della pagina quello che ci interessa
    return $header['content'];
}
?>