<?php
/**
 * @package Flatnux_statistics
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
##<fnmodule>statistics</fnmodule>
defined('_FNEXEC') or die('Restricted access');
FNSTAT_Stats();
$config = FN_LoadConfig("plugins/statistics/config.php");
if (!empty($config['track_usersonline']))
{
	FNSTAT_UpdateUsersOnline();
}

/**
 *
 * @param type $ip
 * @return type 
 */
function FN_GetLocation($ip)
{
    /*
      Array
      (
      [known] => true
      [locationcode] => ITLIGENV
      [fips104] => IT
      [iso2] => IT
      [iso3] => ITA
      [ison] => 380
      [internet] => IT
      [countryid] => 119
      [country] => Italy
      [regionid] => 2245
      [region] => Liguria
      [regioncode] => LI
      [adm1code] => IT08
      [cityid] => 12005
      [city] => Genova
      [latitude] => 44.4170
      [longitude] => 8.9500
      [timezone] => +01:00
      [certainty] => 90
      [mapbytesremaining] => Free
      )
     */
    $tags = get_meta_tags("http://www.geobytes.com/IpLocator.htm?GetLocation&template=php3.txt&IpAddress=$ip");
    return $tags;
}
/**
 * update_users_online
 * aggiorna le tabelle con utenti online
 * 
 */
function FN_UpdateUsersOnline()
{
    global $_FN;
    $timeout = 180;
    if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/stats_usersonline.php"))
    {
        $fields = array();
        $fields[1]['name'] = "unirecid";
        $fields[1]['primarykey'] = 1;
        $fields[1]['extra'] = "autoincrement";
        $fields[2]['name'] = "REMOTE_ADDR";
        $fields[3]['name'] = "REQUEST_URI";
        $fields[5]['name'] = "HTTP_REFERER";
        $fields[6]['name'] = "HTTP_COOKIE";
        $fields[7]['name'] = "time";
        $fields[8]['name'] = "PAGES";
        $fields[8]['defaultvalue'] = "1";
        $fields[9]['name'] = "HTTP_USER_AGENT";
        $fields[10]['name'] = "LAST_HTTP_REFERER";
        $fields[11]['name'] = "user";
        echo createxmltable("{$_FN['database']}", "stats_usersonline", $fields, "{$_FN['datadir']}");
    }
    $table = FN_XmlTable("stats_usersonline");
    //eliminazione dei vecchi
    $all = $table->GetRecords();
    foreach ($all as $one)
    {
        if (time() > $one['time'] + $timeout)
        {
            $table->DelRecord($one['unirecid']);
        }
    }
    $restr['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
    $old = $table->GetRecords($restr);
    $newvalues['REMOTE_ADDR'] = FN_GetParam("REMOTE_ADDR", $_SERVER, "flat");
    $newvalues['REQUEST_URI'] = FN_GetParam("REQUEST_URI", $_SERVER, "flat");
    $newvalues['PHP_SELF'] = FN_GetParam("PHP_SELF", $_SERVER, "flat");
    $newvalues['HTTP_COOKIE'] = FN_GetParam("HTTP_COOKIE", $_SERVER, "flat");
    $newvalues['HTTP_USER_AGENT'] = FN_GetParam("HTTP_USER_AGENT", $_SERVER, "flat");
    $newvalues['user'] = $_FN['user'];
    $newvalues['LAST_HTTP_REFERER'] = FN_GetParam("HTTP_REFERER", $_SERVER, "flat");
    $newvalues['time'] = time();
    $newvalues['PAGES'] = 1;
    if (is_array($old) && count($old) > 0)
    {
        $newvalues['unirecid'] = $old[0]['unirecid'];
        $newvalues['PAGES'] = $old[0]['PAGES'] + 1;
        $table->UpdateRecord($newvalues);
    }
    else
    {
        $newvalues['HTTP_REFERER'] = htmlentities(FN_GetParam("HTTP_REFERER", $_SERVER, "flat"));
        $table->InsertRecord($newvalues);
    }
}
/**
 * 
 * @param $date
 * @return 
 */
function FN_Stats($date = "")
{
    global $_FN;
//---------------config-------------------------------------------------------->	
	$config = FN_LoadConfig("plugins/statistics/config.php");
	$track_ip=$config['track_ip'];
	$track_refer=$config['track_refer'];
	$track_total=$config['track_total'];
	$track_pages=$config['track_pages'];
	$track_data=$config['track_data'];
	$track_agents=$config['track_agents'];
    $antispam = $config['antispam'];
//---------------config--------------------------------------------------------<
	
    $_FN['counter'] = 1;
    $_FN['counter_unique'] = 1;
    $from = FN_GetParam("HTTP_REFERER", $_SERVER, "flat");
    $host = FN_GetParam("HTTP_HOST", $_SERVER, "flat");
    $self = FN_GetParam("PHP_SELF", $_SERVER, "flat");
    $where = "http://" . $host . $self;
    $where = str_replace(basename($where), "", $where);
    $url = str_replace("http://", "", $where);
    $url = str_replace("www.", "", $url);
    $from = str_replace("http://", "", $from);
    $from = str_replace("www.", "", $from);
    //--------accessi univoci---------------------------->
    $is_unique = false;
    if (!stristr($from, $url))
    {
        $is_unique = true;
        //---------------browsers-------------------------------------->
        if ($track_agents)
        {
            if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/fn_stats_browsers"))
            {
                $fields = array();
                $fields[1]['name'] = "browser";
                $fields[1]['primarykey'] = 1;
                $fields[2]['name'] = "counter_unique";
                $fields[2]['defaultvalue'] = "1";
                echo createxmltable("{$_FN['database']}", "fn_stats_browsers", $fields, "{$_FN['datadir']}", "stat");
            }
            $r = array();
            $table = FN_XmlTable("fn_stats_browsers");
            $agent = FN_GetParam("HTTP_USER_AGENT", $_SERVER, "flat");
            $t = FN_BrowserDetect($agent);
            $r['browser'] = "{$t['name']};{$t['platform']}";
            $r['counter'] = 1;
            $old = $table->GetRecordByPrimaryKey($r['browser']);
            if ($old !== null) //non è in grado di leggere il file
                if ($old == false)
                {
                    $table->InsertRecord($r);
                }
                else
                {
                    $old['counter_unique'] = $old['counter_unique'] + 1;
                    $table->UpdateRecord($old);
                }
        }
        //---------------agent--------------------------------------<
        //trakip:
        //--------------- ip---------------------------------------->
        if ($track_ip)
        {
            if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/fn_stats_ip"))
            {
                $fields = array();
                $fields[1]['name'] = "ip";
                $fields[1]['primarykey'] = 1;
                $fields[2]['name'] = "counter_unique";
                $fields[2]['defaultvalue'] = "1";
                echo createxmltable("{$_FN['database']}", "fn_stats_ip", $fields, "{$_FN['datadir']}", "stat");
            }
            $r = array();
            $table = FN_XmlTable("fn_stats_ip");
            $r['ip'] = FN_GetParam("REMOTE_ADDR", $_SERVER, "flat");
            $r['counter_unique'] = 1;
            $old = $table->GetRecordByPrimaryKey($r['ip']);
            if ($old !== null) //non è in grado di leggere il file
                if ($old === false)
                {
                    $table->InsertRecord($r);
                }
                else
                {
                    $old['counter_unique'] = $old['counter_unique'] + 1;
                    $table->UpdateRecord($old);
                }
        }
        //--------------- ip----------------------------------------<
        //trak_ref:
        //---------------ref---------------------------------------->
        if ($track_refer)
        {
            if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/fn_stats_ref"))
            {
                $fields = array();
                $fields[1]['name'] = "url";
                $fields[1]['primarykey'] = 1;
                $fields[2]['name'] = "counter_unique";
                $fields[2]['defaultvalue'] = "1";
                echo createxmltable("{$_FN['database']}", "fn_stats_ref", $fields, "{$_FN['datadir']}", "stat");
            }
            $r = array();
            $table = FN_XmlTable("fn_stats_ref");
            $r['url'] = FN_GetParam("HTTP_REFERER", $_SERVER, "flat"); //$_SERVER['HTTP_REFERER'];
           
            if ($antispam == 1 && FN_IsSpam($r['url']))
                $r['url'] = "SPAM";
            $r['counter_unique'] = 1;
            if ($r['url'] == "")
                $r['url'] = "none";
            $old = $table->GetRecordByPrimaryKey($r['url']);
            if ($old !== null) //non è in grado di leggere il file
                if ($old === false)
                {
                    $table->InsertRecord($r);
                }
                else
                {
                    $old['counter_unique'] = $old['counter_unique'] + 1;
                    $table->UpdateRecord($old);
                }
        }
        //---------------ref---------------------------------------<
    }
    //--------accessi univoci----------------------------<
    //track_total:
    //---contatore totale-------------------------------->
    if ($track_total)
    {
        if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/fn_stats_total"))
        {
            $fields = array();
            $fields[1]['name'] = "id";
            $fields[1]['primarykey'] = 1;
            $fields[2]['name'] = "counter";
            $fields[2]['defaultvalue'] = "1";
            $fields[3]['name'] = "counter_unique";
            $fields[3]['defaultvalue'] = "1";
            echo createxmltable("{$_FN['database']}", "fn_stats_total", $fields, "{$_FN['datadir']}", "stat");
        }
        $table = FN_XmlTable("fn_stats_total");
        $old = $table->GetRecordByPrimaryKey(1);
        if ($old !== null) //non è in grado di leggere il file
            if ($old === false)
            {
                //echo "aa";
                $table->InsertRecord(array("id" => 1, "counter" => 1, "counter_unique" => 1));
            }
            else
            {
                $old['counter'] = $old['counter'] + 1;
                if ($is_unique)
                    $old['counter_unique'] = $old['counter_unique'] + 1;
                $old = $table->UpdateRecord($old);
                $_FN['counter_unique'] = $old['counter_unique'];
                $_FN['counter'] = $old['counter'];
            }
    }
    //---contatore totale--------------------------------<
    //track_pages:
    //---------------pagine------------------------------>	
    if ($track_pages)
    {
        if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/fn_stats_pages"))
        {
            $fields = array();
            $fields[1]['name'] = "url";
            $fields[1]['primarykey'] = 1;
            $fields[2]['name'] = "counter";
            $fields[2]['defaultvalue'] = "1";
            $fields[3]['name'] = "counter_unique";
            $fields[3]['defaultvalue'] = "1";
            echo createxmltable("{$_FN['database']}", "fn_stats_pages", $fields, "{$_FN['datadir']}", "stat");
        }
        $r = array();
        $table = FN_XmlTable("fn_stats_pages");
        $scriptname = FN_GetParam("SCRIPT_NAME", $_SERVER, "flat");
        $op = FN_GetParam("op", $_GET, "flat");
        $id = FN_GetParam("id", $_GET, "flat");
        $u = "";
        //dprint_r($_SERVER);
        if ($op != "")
            $u .= "&amp;op=$op";
        if ($id != "")
            $u .= "&amp;id=$id";
        $r['url'] = FN_RewriteLink("$scriptname?mod={$_FN['mod']}$u");
        $r['counter'] = 1;
        $r['counter_unique'] = 1;
        //dprint_r($r);
        $old = $table->GetRecordByPrimaryKey($r['url']);
        if ($old !== null) //non è in grado di leggere il file
            if ($old === false)
            {
                $table->InsertRecord($r);
            }
            else
            {
                $old['counter'] = $old['counter'] + 1;
                if ($is_unique)
                    $old['counter_unique'] = $old['counter_unique'] + 1;
                $table->UpdateRecord($old);
            }
    }
    //---------------pagine------------------------------<	
    //track_data:
    //---------------pagine visitate per giorno---------->
    if ($track_data)
    {
        if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/fn_stats_date"))
        {
            $fields = array();
            $fields[1]['name'] = "day";
            $fields[1]['primarykey'] = 1;
            $fields[2]['name'] = "counter";
            $fields[2]['defaultvalue'] = "1";
            $fields[3]['name'] = "counter_unique";
            $fields[3]['defaultvalue'] = "1";
            echo createxmltable("{$_FN['database']}", "fn_stats_date", $fields, "{$_FN['datadir']}", false);
        }
        $r = array();
        $table = FN_XmlTable("fn_stats_date");
        if ($date == "")
            $r['day'] = date("Y-m-d");
        else
        {
            $r['day'] = $date;
        }
        $old = $table->GetRecordByPrimaryKey($r['day']);
        if ($old !== null) //non è in grado di leggere il file
            if ($old === false)
            {
                $r['counter'] = 1;
                $r['counter_unique'] = 1;
                $table->InsertRecord($r);
            }
            else
            {
                $old['counter'] = $old['counter'] + 1;
                if ($is_unique)
                    $old['counter_unique'] = $old['counter_unique'] + 1;
                $table->UpdateRecord($old);
            }
    }
    //end:
    //---------------pagine visitate per giorno----------<
}
/**
 *
 * @param string $userAgent
 * @return string 
 */
function FN_BrowserDetect($userAgent)
{
    // Identify the browser. Check Opera and Safari first in case of spoof. Let Google Chrome be identified as Safari.
    $browsers = array("Yahoo", "Googlebot", "Msnbot", "Firefox", "MS Explorer|msie", "Opera", "Chrome", "Safari", "Mozilla", "Seamonkey", "Konqueror", "Netscape", "Gecko", "Navigator", "Mosaic", "Lynx", "Amaya",
        "Omniweb", "Avant", "Camino", "Flock", "Aol");
    $name = "unknown";
    foreach ($browsers as $browser)
    {
        if (preg_match("/$browser/i", $userAgent))
        {
            $browser = explode("|", $browser);
            $name = $browser[0];
            break;
        }
    }
    $platforms = array("Linux", "PalmOS", "iPhone OS", "Symbian", "Mac os x|macintosh", "Windows CE", "Windows");
    $platform = 'unknown';
    foreach ($platforms as $c_platform)
    {
        if (preg_match("/$c_platform/i", $userAgent))
        {
            $c_platform = explode("|", $c_platform);
            $platform = $c_platform[0];
            break;
        }
    }
    return array('name' => $name, 'platform' => $platform, 'userAgent' => $userAgent);
}




/**
 * update_users_online
 * aggiorna le tabelle con utenti online
 * 
 */
function FNSTAT_UpdateUsersOnline()
{
	global $_FN;
	$timeout = 180;
	if ( !file_exists("{$_FN['datadir']}/fndatabase/fn_stat_usersonline.php") )
	{
		$fields = array();
		$fields[1]['name'] = "unirecid";
		$fields[1]['primarykey'] = 1;
		$fields[1]['extra'] = "autoincrement";
		$fields[2]['name'] = "REMOTE_ADDR";
		$fields[3]['name'] = "REQUEST_URI";
		$fields[5]['name'] = "HTTP_REFERER";
		$fields[6]['name'] = "HTTP_COOKIE";
		$fields[7]['name'] = "time";
		$fields[8]['name'] = "PAGES";
		$fields[8]['defaultvalue'] = "1";
		$fields[9]['name'] = "HTTP_USER_AGENT";
		$fields[10]['name'] = "LAST_HTTP_REFERER";
		$fields[11]['name'] = "user";
		echo createxmltable("fndatabase", "fn_stat_usersonline", $fields, "{$_FN['datadir']}");
	}
	$table = new XMLTable("fndatabase", "fn_stat_usersonline", "{$_FN['datadir']}");
	//eliminazione dei vecchi
	$all = $table->GetRecords();
	foreach ( $all as $one )
	{
		if ( time() > $one['time'] + $timeout )
		{
			$table->DelRecord($one['unirecid']);
		}
	}
	$restr['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
	$old = $table->GetRecords($restr);
	//dprint_r($_SERVER);	
	$newvalues['REMOTE_ADDR'] = FN_GetParam("REMOTE_ADDR", $_SERVER, "flat");
	$newvalues['REQUEST_URI'] = FN_GetParam("REQUEST_URI", $_SERVER, "flat");
	$newvalues['PHP_SELF'] = FN_GetParam("PHP_SELF", $_SERVER, "flat");
	$newvalues['HTTP_COOKIE'] = FN_GetParam("HTTP_COOKIE", $_SERVER, "flat");
	$newvalues['HTTP_USER_AGENT'] = FN_GetParam("HTTP_USER_AGENT", $_SERVER, "flat");
	$newvalues['user'] = $_FN['user'];
	$newvalues['LAST_HTTP_REFERER'] = FN_GetParam("HTTP_REFERER", $_SERVER, "flat");
	$newvalues['time'] = time();
	$newvalues['PAGES'] = 1;
	if ( is_array($old) && count($old) > 0 )
	{
		$newvalues['unirecid'] = $old[0]['unirecid'];
		$newvalues['PAGES'] = $old[0]['PAGES'] + 1;
		$table->UpdateRecord($newvalues);
	}
	else
	{
		$newvalues['HTTP_REFERER'] = htmlentities(FN_GetParam("HTTP_REFERER", $_SERVER, "flat"));
		$table->InsertRecord($newvalues);
	}
}
/**
 * 
 * @param $date
 * @return 
 */
function FNSTAT_Stats($date = "")
{
	global $_FN;
	if ( !file_exists("plugins/statistics/config.php") )
		return;
	$config = FN_LoadConfig("plugins/statistics/config.php");
//---------------config-------------------------------------------------------->	
	$config = FN_LoadConfig("plugins/statistics/config.php");
	$track_ip=$config['track_ip'];
	$track_refer=$config['track_refer'];
	$track_total=$config['track_total'];
	$track_pages=$config['track_pages'];
	$track_data=$config['track_data'];
	$track_agents=$config['track_agents'];
    $antispam = $config['antispam'];
//---------------config--------------------------------------------------------<

	
	$_FN['counter'] = 1;
	$_FN['counter_unique'] = 1;
	$from = FN_GetParam("HTTP_REFERER", $_SERVER, "flat");
	$host = FN_GetParam("HTTP_HOST", $_SERVER, "flat");
	$self = FN_GetParam("PHP_SELF", $_SERVER, "flat");
	$where = "http://" . $host . $self;
	$where = str_replace(basename($where), "", $where);
	$url = str_replace("http://", "", $where);
	$url = str_replace("www.", "", $url);
	$from = str_replace("http://", "", $from);
	$from = str_replace("www.", "", $from);
	//--------accessi univoci---------------------------->
	$is_unique = false;
	if ( !stristr($from, $url) )
	{
		$is_unique = true;
		//---------------browsers-------------------------------------->
		if ( $track_agents )
		{
			if ( !file_exists("{$_FN['datadir']}/fndatabase/stats_browsers") )
			{
				$fields = array();
				$fields[1]['name'] = "browser";
				$fields[1]['primarykey'] = 1;
				$fields[2]['name'] = "counter_unique";
				$fields[2]['defaultvalue'] = "1";
				echo createxmltable("fndatabase", "stats_browsers", $fields, "{$_FN['datadir']}", "statistiche");
			}
			$r = array();
			$table = new XMLTable("fndatabase", "stats_browsers", "{$_FN['datadir']}");
			$agent = FN_GetParam("HTTP_USER_AGENT", $_SERVER, "flat"); //$_SERVER['HTTP_USER_AGENT'];
			$t = FNSTAT_BrowserDetect($agent);
			$r['browser'] = "{$t['name']};{$t['platform']}";
			//dprint_r($t);
			//die();
			$r['counter'] = 1;
			$old = $table->GetRecordByPrimaryKey($r['browser']);
			if ( $old !== null ) //non è in grado di leggere il file
				if ( $old == false )
				{
					$table->InsertRecord($r);
				}
				else
				{
					$old['counter_unique'] = $old['counter_unique'] + 1;
					$table->UpdateRecord($old);
				}
		}
		//---------------agent--------------------------------------<
		//trakip:
		//--------------- ip---------------------------------------->
		if ( $track_ip )
		{
			if ( !file_exists("{$_FN['datadir']}/fndatabase/stats_ip") )
			{
				$fields = array();
				$fields[1]['name'] = "ip";
				$fields[1]['primarykey'] = 1;
				$fields[2]['name'] = "counter_unique";
				$fields[2]['defaultvalue'] = "1";
				echo createxmltable("fndatabase", "stats_ip", $fields, "{$_FN['datadir']}", "stat");
			}
			$r = array();
			$table = new XMLTable("fndatabase", "stats_ip", "{$_FN['datadir']}");
			$r['ip'] = FN_GetParam("REMOTE_ADDR", $_SERVER, "flat");
			$r['counter_unique'] = 1;
			$old = $table->GetRecordByPrimaryKey($r['ip']);
			if ( $old !== null ) //non è in grado di leggere il file
				if ( $old === false )
				{
					$table->InsertRecord($r);
				}
				else
				{
					$old['counter_unique'] = $old['counter_unique'] + 1;
					$table->UpdateRecord($old);
				}
		}
		//--------------- ip----------------------------------------<
		//trak_ref:
		//---------------ref---------------------------------------->
		if ( $track_refer )
		{
			if ( !file_exists("{$_FN['datadir']}/fndatabase/stats_ref") )
			{
				$fields = array();
				$fields[1]['name'] = "url";
				$fields[1]['primarykey'] = 1;
				$fields[2]['name'] = "counter_unique";
				$fields[2]['defaultvalue'] = "1";
				echo createxmltable("fndatabase", "stats_ref", $fields, "{$_FN['datadir']}", "stat");
			}
			$r = array();
			$table = new XMLTable("fndatabase", "stats_ref", "{$_FN['datadir']}");
			$r['url'] = FN_GetParam("HTTP_REFERER", $_SERVER, "flat"); //$_SERVER['HTTP_REFERER'];
			if ( $antispam == 1 && FN_IsSpam($r['url']) )
				$r['url'] = "SPAM";
			$r['counter_unique'] = 1;
			if ( $r['url'] == "" )
				$r['url'] = "none";
			$old = $table->GetRecordByPrimaryKey($r['url']);
			if ( $old !== null ) //non è in grado di leggere il file
				if ( $old === false )
				{
					$table->InsertRecord($r);
				}
				else
				{
					$old['counter_unique'] = $old['counter_unique'] + 1;
					$table->UpdateRecord($old);
				}
		}
		//---------------ref---------------------------------------<
	}
	//--------accessi univoci----------------------------<
	//track_total:
	//---contatore totale-------------------------------->
	if ( $track_total )
	{
		if ( !file_exists("{$_FN['datadir']}/fndatabase/stats_total") )
		{
			$fields = array();
			$fields[1]['name'] = "id";
			$fields[1]['primarykey'] = 1;
			$fields[2]['name'] = "counter";
			$fields[2]['defaultvalue'] = "1";
			$fields[3]['name'] = "counter_unique";
			$fields[3]['defaultvalue'] = "1";
			echo createxmltable("fndatabase", "stats_total", $fields, "{$_FN['datadir']}", "stat");
		}
		$table = new XMLTable("fndatabase", "stats_total", "{$_FN['datadir']}");
		$old = $table->GetRecordByPrimaryKey(1);
		if ( $old !== null ) //non è in grado di leggere il file
			if ( $old === false )
			{
				//echo "aa";
				$table->InsertRecord(array("id" => 1, "counter" => 1, "counter_unique" => 1));
			}
			else
			{
				$old['counter'] = $old['counter'] + 1;
				if ( $is_unique )
					$old['counter_unique'] = $old['counter_unique'] + 1;
				$old = $table->UpdateRecord($old);
				$_FN['counter_unique'] = $old['counter_unique'];
				$_FN['counter'] = $old['counter'];
			}
	}
	//---contatore totale--------------------------------<
	//track_pages:
	//---------------pagine------------------------------>	
	if ( $track_pages )
	{
		if ( !file_exists("{$_FN['datadir']}/fndatabase/stats_pages") )
		{
			$fields = array();
			$fields[1]['name'] = "url";
			$fields[1]['primarykey'] = 1;
			$fields[2]['name'] = "counter";
			$fields[2]['defaultvalue'] = "1";
			$fields[3]['name'] = "counter_unique";
			$fields[3]['defaultvalue'] = "1";
			echo createxmltable("fndatabase", "stats_pages", $fields, "{$_FN['datadir']}", "stat");
		}
		$r = array();
		$table = new XMLTable("fndatabase", "stats_pages", "{$_FN['datadir']}");
		$scriptname = FN_GetParam("SCRIPT_NAME", $_SERVER, "flat");
		$op = FN_GetParam("op", $_GET, "flat");
		$id = FN_GetParam("id", $_GET, "flat");
		$u = "";
		//dprint_r($_SERVER);
		if ( $op != "" )
			$u .= "&amp;op=$op";
		if ( $id != "" )
			$u .= "&amp;id=$id";
		$r['url'] = fn_rewritelink("$scriptname?mod={$_FN['mod']}$u");
		$r['counter'] = 1;
		$r['counter_unique'] = 1;
		//dprint_r($r);
		$old = $table->GetRecordByPrimaryKey($r['url']);
		if ( $old !== null ) //non è in grado di leggere il file
			if ( $old === false )
			{
				$table->InsertRecord($r);
			}
			else
			{
				$old['counter'] = $old['counter'] + 1;
				if ( $is_unique )
					$old['counter_unique'] = $old['counter_unique'] + 1;
				$table->UpdateRecord($old);
			}
	}
	//---------------pagine------------------------------<	
	//track_data:
	//---------------pagine visitate per giorno---------->
	if ( $track_data )
	{
		if ( !file_exists("{$_FN['datadir']}/fndatabase/stats_date") )
		{
			$fields = array();
			$fields[1]['name'] = "day";
			$fields[1]['primarykey'] = 1;
			$fields[2]['name'] = "counter";
			$fields[2]['defaultvalue'] = "1";
			$fields[3]['name'] = "counter_unique";
			$fields[3]['defaultvalue'] = "1";
			echo createxmltable("fndatabase", "stats_date", $fields, "{$_FN['datadir']}", false);
		}
		$r = array();
		$table = new XMLTable("fndatabase", "stats_date", "{$_FN['datadir']}");
		if ( $date == "" )
			$r['day'] = date("Y-m-d");
		else
		{
			$r['day'] = $date;
		}
		$old = $table->GetRecordByPrimaryKey($r['day']);
		if ( $old !== null ) //non è in grado di leggere il file
			if ( $old === false )
			{
				$r['counter'] = 1;
				$r['counter_unique'] = 1;
				$table->InsertRecord($r);
			}
			else
			{
				$old['counter'] = $old['counter'] + 1;
				if ( $is_unique )
					$old['counter_unique'] = $old['counter_unique'] + 1;
				$table->UpdateRecord($old);
			}
	}
	//end:
	//---------------pagine visitate per giorno----------<
}

/**
 *
 * @param string $userAgent
 * @return string 
 */
function FNSTAT_BrowserDetect($userAgent)
{
	// Identify the browser. Check Opera and Safari first in case of spoof. Let Google Chrome be identified as Safari.
	$browsers = array("Yahoo", "Googlebot", "Msnbot", "Firefox", "MS Explorer|msie", "Opera", "Chrome", "Safari", "Mozilla", "Seamonkey", "Konqueror", "Netscape", "Gecko", "Navigator", "Mosaic", "Lynx", "Amaya",
		"Omniweb", "Avant", "Camino", "Flock", "Aol");
	$name = "unknown";
	foreach ( $browsers as $browser )
	{
		if ( preg_match("/$browser/i", $userAgent) )
		{
			$browser = explode("|", $browser);
			$name = $browser[0];
			break;
		}
	}
	$platforms = array("Linux", "PalmOS", "iPhone OS", "Symbian", "Mac os x|macintosh", "Windows CE", "Windows");
	$platform = 'unknown';
	foreach ( $platforms as $c_platform )
	{
		if ( preg_match("/$c_platform/i", $userAgent) )
		{
			$c_platform = explode("|", $c_platform);
			$platform = $c_platform[0];
			break;
		}
	}
	return array('name' => $name, 'platform' => $platform, 'userAgent' => $userAgent);
}

?>