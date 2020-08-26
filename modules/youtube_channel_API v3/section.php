<?php

/**
 * @package flatnux_module_youtube_channel
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2005
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * 
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$YT=new FN_YoutubeChannel();
$id=FN_GetParam("id",$_GET);
$config=FN_LoadConfig();
echo FN_HtmlContent("sections/{$_FN['mod']}");
//get_youtube_channel($channelID,$maxResults,$API_key)
$videos=$YT->get_youtube_channel($config['youtube_user'],$config['youtube_max_video'],$config['API_key']);

if ($videos)
{
    foreach($videos as $video)
    {
        echo "<div id=\"{$video['id']}\" title=\"".htmlspecialchars(str_replace("\"","",$video['title']))."\" >";
        echo htmlspecialchars($video['title'])."<br />";
        if ($id== $video['id'])
        {
            echo "<iframe width=\"420\" height=\"315\" src=\"http://www.youtube.com/embed/{$video['id']}?autoplay=1\" frameborder=\"0\" allowfullscreen></iframe>";
        }
        else
        {
            echo "<a title=\"play\" onclick=\"return fn_to_ajax(this,'{$video['id']}')\" href=\"".FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;id={$video['id']}")."\"><img width=\"420\" height=\"315\" src=\"{$video['thumb']}\" alt=\"\"/></a>";
        }
        echo "<br /><em>".$video['date']."</em>";
        echo "</div><br />";
    }
}

//dprint_r($videos);
/**
 * 
 */
class FN_YoutubeChannel
{

    /**
     * 
     */
    function __construct()
    {
        $this->config=FN_LoadConfig();
    }

    /**
     * 
     * @param type $username
     * @return boolean
     */
    function get_youtube_channel($channelID,$maxResults,$API_key)
    {
        if ($channelID== "")
            return false;
        $url = 'https://www.googleapis.com/youtube/v3/search?order=date&part=snippet&channelId='.$channelID.'&maxResults='.$maxResults.'&key='.$API_key.'';
        $videoList = json_decode(file_get_contents($url));
        dprint_r($url);
        dprint_r($videoList);
        
        //die();
        if (!$videoList)
            return false;
        foreach($videoList->items as $item)
        {
            $tmp['id']=$item->id->videoId;
            $tmp['title']=$item->snippet->title;
            $tmp['date']=$item->snippet->publishedAt;
            $tmp['thumb']=$item->snippet->thumbnails->default->url;
            $videos[]=$tmp;
        }
        return $videos;
    }

    /**
     * 
     * @param type $elem
     * @param type $data
     * @return boolean
     */
    function get_xml_elements($elem,$data)
    {
        $out=false;
        preg_match_all('/<'.$elem.'>.*?<\/'.$elem.'>/s',$data,$out);
        if (isset($out[0]))
            return $out[0];
        return false;
    }

    /**
     * 
     * @param type $elem
     * @param type $data
     * @return boolean
     */
    function get_xml_element($elem,$data)
    {
        $out=false;
        preg_match_all('/<'.$elem.'.*?>(.*?)<\/'.$elem.'>/s',$data,$out);
        if (isset($out[0][0]))
            return strip_tags($out[0][0]);
        else
            return false;
    }

}

?>
