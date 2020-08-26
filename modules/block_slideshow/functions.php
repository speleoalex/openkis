<?php
/**
 * 
 * @global type $_FN
 */
function SL_init()
{
    $config=FN_LoadConfig();
    $tablename=$config['tablename'];
    global $_FN;
    if (!file_exists("{$_FN['datadir']}/fndatabase/$tablename.php"))
    {
        FN_Copy("modules/block_slideshow/install/slideshow.php","{$_FN['datadir']}/fndatabase/$tablename.php","w");
    }
}

/**
 * 
 * @param type $str
 * @return string
 */
function FN_TPL_tp_create_modslideshow($tp_str)
{
    global $_FN;
    $config=FN_LoadConfig();
    $table=FN_XmlForm($config['tablename']);
    $images=$table->xmltable->GetRecords(array("status"=>1));
    $htmlItem=FN_TPL_GetHtmlPart("imageitem",$tp_str);
    $htmlItemActive=FN_TPL_GetHtmlPart("imageitem active",$tp_str);
    if ($htmlItemActive== "")
    {
        $htmlItemActive=$htmlItem;
    }
    $htmlitems="";
    $i=0;
    if (is_array($images))
    {
        foreach($images as $image)
        {
            $image['pos']=$i;
            $image['description']=empty($image['description']) ? "" : $image['description'];
            $image['urlimage']=$table->xmltable->get_file($image,'image');
            if ($i== 0)
                $htmlitems.=FN_TPL_ApplyTplString($htmlItemActive,$image);
            else
                $htmlitems.=FN_TPL_ApplyTplString($htmlItem,$image);
            $i++;
        }
    }
    return $htmlitems;
}

/**
 * 
 */
function SL_PrintSlideshow()
{
    global $_FN;
    $config=FN_LoadConfig();
    $tpfile=FN_FromTheme("modules/block_slideshow/slideshow.tp.html",false);
    $params=$config;
    //$params['sid']=$_FN['block'];
    $html=FN_TPL_ApplyTplFile($tpfile,$params);
    $html=FN_TPL_include_tpl($html,$params);
    echo $html;
}

?>
