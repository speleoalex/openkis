<?php
//---------image--------------------------------------->
class xmetadbfrm_field_image
{
    var $fieldvalues;
    function __construct($field)
    {
        $this->fieldvalues = $field['fieldvalues'];
    }

    function show($params)
    {
        $html = "";
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        $oldvalues = $params['oldvalues'];
        $primarykey = $params['oldvalues_primarikey'];
        $oldval = $params['value'];
        $toltips = "";
        $size = isset($params['frm_size']) ? $params['frm_size'] : 20;
        $tsize = isset($params['thumbsize']) ? $params['thumbsize'] : 20;
        $html .= "<table style=\"border:0px;padding:0px\" cellpadding=\"0\" cellspacing=\"0\"><tr><td><input $attributes $toltips size=\"" . $size . "\" name=\"" . $params['name'] . "\" type=\"file\" />\n";
        $html .= "<br />";
        if ($oldval != "" && isset($oldvalues[$primarykey]))
        {
            $width = $height = "";
            $imgthsrc = $params['fieldform']->xmltable->getThumbPath($oldvalues, $params['name']);
            if (file_exists($imgthsrc))
                list($width, $height) = getimagesize($imgthsrc);
            if ($height >= $width)
                $res = "height=\"$tsize\"";
            else
                $res = "width=\"$tsize\"";
            $html .= "<table cellpadding=\"0\" cellspacing=\"0\" style=\"border:1px dotted;height:$tsize" . "px;width:$tsize" . "px\"><tr><td valign=\"center\"><img style=\"vertical-align: middle;\" $res src=\"{$params['fieldform']->siteurl}" . $imgthsrc . "\" alt=\"$oldval\" border=\"0\" />";
            $html .= "<span style=\"white-space:nowrap\" ><label><input type=\"checkbox\" value=\"null\" name=\"__isnull__" . $params['name'] . "\" />" . $params['messages']["_XMLDBDELETE"] . "</label>";
            $html .= "</span></td></tr></table>";
        }
        $html .= "</td></tr></table>";
        return $html;
    }

    function view($params)
    {
        $htmlout = "";
        $Table = $params['fieldform'];
        $path = $Table->path;
        $databasename = $Table->databasename;
        $tablename = $Table->tablename;
        $value = $params['value'];
        $row = $values = $params['values'];
        $field = $this->fieldvalues;

        //$fileimage = isset($row[$Table->xmltable->primarykey]) ? "$path/$databasename/$tablename/" . $values[$Table->xmltable->primarykey] . "/" . $field['name'] . "/" . $values[$field['name']] : "";
        //$filethumb = isset($row[$Table->xmltable->primarykey]) ? "$path/$databasename/$tablename/" . $values[$Table->xmltable->primarykey] . "/" . $field['name'] . "/thumbs/" . $values[$field['name']] . ".jpg" : "";
        $fileimage = $params['fieldform']->xmltable->getFilePath($values, $params['name']);
        $filethumb = $params['fieldform']->xmltable->getThumbPath($values, $params['name']);
        //echo "$fileimage";
        $ww = $hh = empty($field['thumbsize']) ? 100 : $field['thumbsize'];
        if (isset($field['thumbsize_w']))
        {
            $ww = $field['thumbsize_w'];
        }
        if (isset($field['thumbsize_h']))
        {
            $hh = $field['thumbsize_h'];
        }
        //if (file_exists ( "thumb.php" ))
        //	$filethumb = isset ( $row ['unirecid'] ) ? "thumb.php?d=$databasename&amp;t=$tablename&amp;i=" . $row ['unirecid'] . "&amp;h=$hh&amp;w=$ww&amp;c=" . $field ['name'] : "";
        if ($fileimage != "" && file_exists($fileimage))
        {
            $htmlout .= "\n<a href=\"{$params['fieldform']->siteurl}$fileimage\" onclick=\"window.open(this.href);return false;\" ><img alt=\"\" title=\"";
            $htmlout .= XMETADB_i18n("click to zoom in") . "\"";
            $htmlout .= " border=\"0\" src=\"{$params['fieldform']->siteurl}$filethumb\"></a><br />";
        }
        else
        {
            if ($fileimage != "" && file_exists($fileimage))
            {
                $htmlout .= "\n$st<a href=\"{$params['fieldform']->siteurl}$fileimage\" onclick=\"window.open(this.href);return false;\" ><img width=\"" . $field['thumbsize'] . "\"  alt=\"\" ";
                $htmlout .= tooltip(XMETADB_i18n("click to zoom in"));
                $htmlout .= " border=\"0\" src=\"{$params['fieldform']->siteurl}$fileimage\"></a><br />";
            }
            else
            {
                if ($fileimage != "" && !file_exists($fileimage))
                {
                    $htmlout .= "<br />" . basename($fileimage) . "<br />";
                }
            }
        }
        return $htmlout;
    }

    //TODO
    function Verify($newvalues, $update = false)
    {
        
    }

}

//---------image---------------------------------------<
