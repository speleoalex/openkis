<?php

//---------latitude--------------------------------------->
class xmetadbfrm_field_latlon
{

    function show($params)
    {
        global $_FN;
        $toltips=($params['frm_help']!="")?"title=\"".$params['frm_help']."\"":"";
        $size=isset($params['frm_size'])?$params['frm_size']:30;
        //-----------------------------------------
        $oldvalues=$params['oldvalues'];
        $tooltip=$params['frm_help'];
        //dprint_r("value=".$params['value']);
        $fn="lat";
        if($params['name']=="longitude")
        {
            $fn="lon";
        }
        $html="<script type=\"text/javascript\">
		var check_$fn = function(input,change)
		{
		var str = input.value.toString();
		
		str = str.replace(/\\s+$/,'');
		str = str.replace(',','.');
		str = str.replace('\"',\"''\");
		";
        if($params['name']=="longitude")
        {
            $html.= "str = str.replace(/W$/i,' Ovest di Monte Mario');
			str = str.replace(/'Ovest$/i,\"' Ovest di Monte Mario\");
			str = str.replace(/'O$/i,\"' O\");
			str = str.replace(/ O$/i,' Ovest di Monte Mario');
			str = str.replace(/ Ovest$/i, ' Ovest di Monte Mario');
			str = str.replace(/''[\\D]*mario[\\D]*/i,\"'' Ovest di Monte Mario\");
			
			str = str.replace(/Est$/i,'E');
			str = str.replace(/ e$/i,' E');		
		";
        }
        if($params['name']=="latitude")
        {
            $html.= "
			str = str.replace(/''$/,\"'' N\");
			str = str.replace(/nord$/i,\"'' N\");
			
		";
        }
        $html.= "
		str = str.replace(/ +N/,' N');
		str = str.replace(/ +E/,' E');
		str = str.replace(/ +Ovest/,' Ovest');
		if (str != input.value)
		{
			if (change)
			{
				input.value = str;
			}
			else
			{
				input.style.color='red';
			}
		}
		else
		{
			input.style.color='green';
		}
		//alert (input.value)
		}
		</script>";
        $name=$params['name'];
        $value=$params['value'];
        $id="latlon_{$params['name']}";
        $html.= "<input id=\"$id\" onkeyup=\"check_$fn(this,false)\" onchange=\"check_$fn(this,true)\" onblur=\"check_$fn(this,true)\"  title=\"$tooltip\" name=\"$name\"  value=\"".add_db_sl($value)."\" />";
        $html.= "<script>check_$fn(document.getElementById('$id'),false)</script>";
        //----mappa---->
        // the map link reads the latitude, longitude and coordinates type of the
        // same point: the fields share the prefix of this one ("" for
        // latitude_txt/longitude_txt/coordinates_type, "detection_" for
        // detection_latitude_txt/detection_longitude_txt/detection_coordinates_type)
        if($params['name']!="latitude")
        {
            $prefix=preg_replace('/(latitude|longitude)_txt$/','',$name);
            if($prefix===$name)
            {
                $prefix="";
            }
            $prefix_js=htmlspecialchars(json_encode($prefix),ENT_QUOTES);
            $html.= " <a onclick=\"openkis_refreshmap($prefix_js)\" style=\"cursor:pointer\">Apri mappa a queste coordinate</a>";
            //echo "<iframe id=\"mapframe\" src=\"\" frameborder=\"0\" width=\"200\" height=\"200\"></iframe>";
            //http://localhost/speleoalex/speleo/dbcave/bs_map.htm?point=circle&lat=45.463588&lon=7.5065431&zoom=18
            $html.="

<script type=\"text/javascript\">
		openkis_refreshmap = function(prefix)
		{
			prefix = prefix || '';
			var lat = document.getElementById('latlon_' + prefix + 'latitude_txt').value;
			var lon = document.getElementById('latlon_' + prefix + 'longitude_txt').value;
			var select = document.getElementsByName(prefix + 'coordinates_type')[0];
			var TC_01 = (select && select.selectedIndex >= 0) ? select.options[select.selectedIndex].value : '';
			var href=\"{$_FN['siteurl']}openkis_map_coordinates.php?lat=\"+encodeURIComponent(lat)+'&lon='+encodeURIComponent(lon)+'&coordinates_type='+encodeURIComponent(TC_01);
			window.open(href, \"map\", \"width=400,height=400 ,toolbar=no, location=no,status=no,menubar=no	,scrollbars=no,resizable=yes\");
		}
		refreshmap = function()
		{
			openkis_refreshmap('');
		}
</script>
		";
            //----mappa----<
        }
        return $html;
    }

}

?>
