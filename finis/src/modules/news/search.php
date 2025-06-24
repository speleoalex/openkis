<?php
/**
 * @package Flatnux_module_news
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');


/**
 *
 * @global array $_FN
 * @param array $tosearch_array
 * @param string $method
 * @param array $sectionvalues
 * @param int $maxres
 * @return array 
 */
function FNSEARCH_module_news($tosearch_array, $method, $sectionvalues,$maxres)
{
	global $_FN;
	$results = array();
	$section_to_search = $sectionvalues['id']; // current section 
	$config = FN_LoadConfig("{$_FN['src_finis']}/modules/news/config.php", $sectionvalues['id']); //load config in section
	$tablename = $config['tablename'];
	$Table = FN_XMDBTable($tablename);
        $DB = new XMETADatabase($_FN['database'], $_FN['datadir'],$_FN);

	//--search query ---------------------------------------------------------->
	$query = "SELECT unirecid,title,summary,txtid FROM $tablename WHERE  ";
	$tmpmethod = "";
	foreach ($Table->fields as $fieldstoread => $fieldvalues)
	{
		if ($fieldstoread != "insert" && $fieldstoread != "update" && $fieldstoread != "unirecid" && $fieldvalues->type != "check")
		{
			foreach ($tosearch_array as $f)
			{
				if ($f != "")
				{
					$query .= " $tmpmethod " . $fieldstoread . " LIKE '%" . addslashes($f) . "%' ";
					$tmpmethod = $method;
				}
			}
			$tmpmethod = " OR ";
		}
	}
	if ((isset($viewonlycreator) && $viewonlycreator == 1) && !FN_IsAdmin())
	{
		$query .= " AND username = '" . $_FN['user'] . "'";
	}
	$query .= " LIMIT 1,$maxres";
	//--search query ----------------------------------------------------------<
	$records = $DB->Query("$query");
	$cont = 0;
	if (is_array($records))
	{
		foreach ($records as $data)
		{
			$link = FN_RewriteLink("index.php?mod=$section_to_search&amp;op={$data['txtid']}");
			$results[$cont]['link'] = $link;
			$results[$cont]['title'] = $sectionvalues['title'].": ". $data['title'] ;
			$results[$cont]['text'] = substr(strip_tags($data['summary']), 0, 100);
			$cont++;
		}
	}
	return $results;
}