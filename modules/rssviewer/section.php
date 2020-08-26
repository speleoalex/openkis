<?php
/**
 * @package Flatnux_module_rssviewer
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
echo FN_HtmlContent("sections/{$_FN['mod']}");
$config = FN_LoadConfig();
$rss_url = $config['url_rss'];

$rssfeed = new FNRSS($rss_url);
echo $rssfeed->Tohtml();
class FNRSS
{
	function Tohtml()
	{
		$html = "";
		$items = $this->rss_to_array();
		foreach ($items as $item)
		{
			$date = strtotime($item['pubDate']);
			$html .="<h3>".$item['title']."</h3>";
			if (isset($item['description']))
				$html .="<div>".$item['description']."</div>";
			$html .="<div>".$this->HtmlDate(date("Y-m-d H:i:s",$date))."</div>";
		}
		return $html;
	}

	function FNRSS($url)
	{
		$this->url = $url;
		$this->urls = explode(",",$this->url);
	}

	function HtmlDate($date)
	{
		global $_FN;
		if ($date == "")
			return "";
		$date = explode("-",$date);
		if (!isset($date[1]))
			return "";
		$h = explode(" ",$date[2]);
		$h = isset($h[1]) ? $h[1] : "";
		return intval($date[2])." ".$_FN['months'][intval($date[1]) - 1]." ".$date[0]." - ".$h;
	}

	function rss_to_array()
	{
		$tag = 'item';
		$array = array(
			'title',
			'link',
			'guid',
			'comments',
			'description',
			'pubDate',
			'category',
		);
		$rss_array = array();
		foreach ($this->urls as $url)
		{
			$items = array();
			$doc = new DOMdocument();
			$doc->load($url);
			foreach ($doc->getElementsByTagName($tag) AS $node)
			{
				foreach ($array AS $key=> $value)
				{
					if (isset($node->getElementsByTagName($value)->item(0)->nodeValue))
						$items[$value] = $node->getElementsByTagName($value)->item(0)->nodeValue;
				}
				array_push($rss_array,$items);
			}
		}
		return $rss_array;
	}

}
?>
