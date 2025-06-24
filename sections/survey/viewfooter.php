<?php

if (!empty($row['filelox']))
{
    $file = "misc/fndatabase/ctl_surveys/{$row['id']}/filelox/{$row['filelox']}";
    $file = urlencode($file);
    $fileiframe = "{$_FN['siteurl']}cave_viewer.php?f={$file}";
    echo "<iframe style=\"width:100%;height:800px;border:0px\" src=\"$fileiframe\"></iframe>";
    echo "<br/><a href=\"$fileiframe\" target=\"_blank\">".FN_Translate("open")."</a>";
}


/*
  global $_FN;



  $cave=get_cave_by_num($row['NUMCAVE'],true);
  if (!empty($cave['NUM']))
  {
  $id=$cave['NUM'];
  }
  if (isset($cave['longitude']) && isset($cave['latitude']) && $cave['longitude']!= "" && $cave['latitude']!= "")
  {
  $r=coordinate($cave);
  $lat=$r['WGS84']['latitude'];
  $lon=$r['WGS84']['longitude'];
  echo "<h4>Posizione della grotta:</h4>";
  echo "<iframe frameborder=\"0\" style=\"height:670px;width:100%\" height=\"670\" width=\"100%\" src=\"{$_FN['siteurl']}bs_map.php?num={$cave['NUM']}&amp;lat=$lat&amp;lon=$lon&amp;tc={$cave['TC_01']}&amp;zoom=20&amp;footer=0\" ></iframe>";
  }
  //    echo "<iframe frameborder=\"0\" height=\"520\" width=\"480\" src=\"{$_FN['siteurl']}bs_map.php?num={$row['NUMCAVE']}\" ></iframe>";
  echo "<br /><a href=\"".FN_RewriteLink("index.php?mod=Navigator&op=view&id=$id")."\">Vai alla scheda completa della grotta</a>";
  $altri=FN_XMETADBQuery("SELECT * FROM ctl_CART WHERE NUMCAVE LIKE '{$row['NUMCAVE']}' ORDER BY NAME");
  //dprint_r($altri);
  if (count($altri) > 1)
  {
  echo "<br /><br /><h3>Altri rilievi eseguiti in questa cavità:</h3>";
  foreach($altri as $altro)
  {
  if ($altro['ID']!= $row['ID'])
  {
  echo "<br /><a href=\"".FN_RewriteLink("index.php?mod=rilievi&op=view&num={$altro['ID']}")."\"><b>**rilievo**</b> {$altro['NAME']} {$altro['CART']}</a>";
  }
  }
  }



  echo "<br style=\"clear:both\"/></div>
  ";
 * 
 */
?>
