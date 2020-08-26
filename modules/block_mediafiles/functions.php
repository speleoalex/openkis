<?php

function MD_init()
{
    $config=FN_LoadConfig();
    $tablename=$config['tablename'];
    global $_FN;
    if (!file_exists("{$_FN['datadir']}/fndatabase/$tablename.php"))
    {
        FN_Copy("modules/block_mediafiles/install/mediafiles.php","{$_FN['datadir']}/fndatabase/$tablename.php","w");
    }
}

/**
 * 
 * @param type $str
 * @return string
 */
function FN_TPL_tp_create_mediafiles($tp_str)
{
    global $_FN;
    $config=FN_LoadConfig();
    $table=FN_XmlForm($config['tablename']);
    $htmlItem=FN_TPL_GetHtmlPart("item",$tp_str);
    $htmlItemActive=FN_TPL_GetHtmlPart("item active",$tp_str);
    $restr = array("status"=>1);
    if ($config['category'])
    {
        $restr['category']=$config['category'];
    }
    //GetRecords($restr=false,$min=false,$length=false,$order=false,$reverse=false,$fields=false)
    $items=$table->xmltable->GetRecords($restr,false,false,"position");
    if ($htmlItemActive== "")
    {
        $htmlItemActive=$htmlItem;
    }
    $htmlitems="";
    $i=0;
    if (is_array($items))
    {
        
        
        foreach($items as $item)
        {
            $item['pos']=$i;
            if ($item['title']== "")
                $item['title']=$item['file'];
            
            
            $item['description']=empty($image['description']) ? "" : $item['description'];
            $item['urlimage']=$table->xmltable->get_file($item,'image');
            if ($item['url']=="" &&  $item['file']!="")
            {
                $item['url']=$table->xmltable->get_file($item,'file');
            }
            $item['url_whatsapp']="whatsapp://send?text=".urlencode($item['url']);
            if ($i== 0)
                $htmlitems.=FN_TPL_ApplyTplString($htmlItemActive,$item);
            else
                $htmlitems.=FN_TPL_ApplyTplString($htmlItem,$item);
            $i++;
        }
    }

    return FN_TPL_ReplaceHtmlPart("items",$htmlitems,$tp_str);
}

/**
 * 
 */
function MD_printFiles()
{
    global $_FN;
    $config=FN_LoadConfig();
    $tpfile=FN_FromTheme("modules/block_mediafiles/page.tp.html",false);
    $params=$config;
    //$params['sid']=$_FN['block'];
    $html=FN_TPL_ApplyTplFile($tpfile,$params);
    $html=FN_TPL_include_tpl($html,$params);
    echo $html;
}

?>
