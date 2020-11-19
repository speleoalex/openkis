<?php

class xmldbfrm_field_filecatasto
{

    function xmldbform_field_filecatasto()
    {
        
    }

    function show($params)
    {
        $toltips="";
        $size=isset($params['frm_size']) ? $params['frm_size'] : 20;
        $oldvalues=$params['oldvalues'];
        $oldval=$params['value'];
        $primarykey=$params['oldvalues_primarikey'];
        $html="<input $toltips size=\"$size\" name=\"".$params['name']."\" type=\"file\" />\n";
        $html.="<br />";
        $attributes=isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        if ($oldval!= "")
        {
            $html.="<br /><a $attributes href=\"".$params['fieldform']->path."/".$params['fieldform']->databasename."/".$params['fieldform']->tablename."/".$oldvalues[$primarykey]."/".$params['name']."/$oldval\" >$oldval</a>";
            $html.="<input  $toltips type=\"checkbox\" value=\"null\" name=\"__isnull__".$params['name']."\" />".FN_i18n("delete");
        }
        return $html;
    }

    function view($params)
    {
        global $_FN;
        $databasename=$params['fieldform']->databasename;
        $tablename=$params['fieldform']->tablename;
        $path=$params['fieldform']->path;
        $value=$params['value'];
        $values=$params['values'];
        $tablepath=$params['fieldform']->xmltable->FindFolderTable($values);
        $table=xmldb_table($databasename,$tablename);
        $htmlout="";
        $fileimage=isset($values[$table->primarykey]) ? "$path/$databasename/$tablepath/".$values[$table->primarykey]."/".$params['name']."/".$values[$params['name']] : "";
        $fileimage2=isset($values[$table->primarykey]) ? "".$values[$table->primarykey]."/".$params['name']."/".$values[$params['name']] : "";
        $link=FN_GetParam("QUERY_STRING",$_SERVER);
        $htmlout.="\n<a title=\"Download $value\" href=\"?$link&xmldb_ddfile_{$params['name']}={$values[$params['name']]}\"  >$value</a>";
        $downloadfile=FN_GetParam("xmldb_ddfile_{$params['name']}",$_GET);
        if ($downloadfile!= "" && $downloadfile == $values[$params['name']])
        {
            $downloadfile=$values[$table->primarykey]."/{$params['name']}/$downloadfile";
            xmldb_go_download($downloadfile,$databasename,$tablename,$path,$tablepath);
        }
        $fsize=0;
        if (file_exists($fileimage))
            $fsize=filesize($fileimage);
        $suff="bytes";
        if ($fsize > 1024)
        {
            $fsize=round($fsize / 1024,2);
            $suff="Kb";
        }
        if ($fsize > 1024)
        {
            $fsize=round($fsize / 1024,2);
            $suff="Mb";
        }
        $stat=new XMLTable($databasename,$tablename."_download_stat",$_FN['datadir']);
        $val=$stat->GetRecordByPrimaryKey($fileimage2);
        $count=isset($val['numdownload']) ? $val['numdownload'] : 0;
        $st=" | $count Download";
        $htmlout.="&nbsp;($fsize $suff$st)";
        return $htmlout;
    }

}

?>
