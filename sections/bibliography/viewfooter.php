<?php

//footer bibliografia ....

global $_FN;
$table=new XMLTable("fndatabase","ctl_caves",$_FN['datadir']);
$caves=explode(",",$row['codecaves'].",".$row['modified_surveys'].",".$row['modified_caves'].",".$row['fossils']);
$caves=array_unique($caves);
$html="";
foreach($caves as $key=> $val)
{
    if ($val!= "")
    {
        $r=$table->GetRecord(array("code"=>$val));
        if (isset($r['name']))
        {
            $l=FN_RewriteLink("index.php?mod=caves&op=view&id={$r['id']}");
            $html.="<br /><b>{$r['name']}</b>&nbsp;<em>{$r['synonyms']}</em> <a class=\"\" href=\"$l\">".FN_Translate("view")."</a>";
        }
    }
}
if ($html!= "")
{
    echo "<br /><br /><h4>Grotte a catasto presenti in questa pubblicazione:</h4><div class=\"alert alert-info\">$html</div>";
}

//-------------------------biblio speleopaleo---------------------------------->
$grotte=explode(",",$row['fossils']);
$grotte=array_unique($grotte);
$html="";
foreach($grotte as $key=> $val)
{
    if ($val!= "")
    {
        $r=$table->GetRecord(array("id"=>$val));
        if (isset($r['name']))
        {


            $l=fn_rewritelink("index.php?mod=speleopaleo&op=view&id={$r['id']}");
            $html.="<br /><b>{$r['name']}</b>&nbsp;<em>{$r['synonyms']}</em> <a href=\"$l\">".FN_Translate("view")."</a>";
        }
    }
}
if ($html!= "")
{
    echo "<br /><br /><h4>Grotte a catasto presenti che parlano di fossili:</h4><div class=\"alert alert-primary\" >$html</div>";
}
//-------------------------biblio speleopaleo----------------------------------<



$table=new XMLTable("fndatabase","ctl_fauna",$_FN['datadir']);
$fauna=explode(",",$row['fauna']);
$fauna=array_unique($fauna);
$fhtml="";
foreach($fauna as $key=> $val)
{
    $val=trim(ltrim(str_replace("\n","",str_replace("\r","",$val))));
    if ($val!= "")
    {
        $query="SELECT * FROM ctl_fauna WHERE scientific_name LIKE \"%".$val."%\" OR scientific_name LIKE \"%".strtolower($val)."%\" OR scientific_name LIKE \"%".ucfirst(strtolower($val))."%\" OR scientific_name LIKE \"%".strtoupper($val)."%\" ";
        $r=FN_XMLQuery($query);

        if (isset($r[0]['scientific_name']))
        {
            $l=fn_rewritelink("index.php?mod=fauna&op=view&id={$r[0]['id']}");
            $fhtml.="<br /><b>{$r[0]['scientific_name']}</b>&nbsp;<em>{$r[0]['name']}</em> <a href=\"$l\">".FN_Translate("view")."</a>";
        }
    }
}
if ($fhtml!= "")
{
    echo "<br /><br /><h4>Schede della fauna presente in questa pubblicazione:</h4>
		<div class=\"alert alert-primary\">
		$fhtml</div>";
}
?>