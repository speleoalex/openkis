<?php

require_once(dirname(__FILE__) . "/proj4php/proj4php.php");
//require_once(dirname(__FILE__)."/proj4php_2020/Proj4php.php");
//-------------------------------internal-------------------------------------->
//coordinate geografiche, WGS 84
Proj4php::$defs["GEOWGS84"] = "+proj=longlat +ellps=WGS84 +datum=WGS84 +no_defs";
//coordinate geografiche, ED50
Proj4php::$defs["GEOED50"] = "+proj=longlat +ellps=intl +towgs84=-87,-98,-121,0,0,0,0 +units=m +no_defs";
//coordinate geografiche, DATUM ROMA 1940  riferite a Monte Mario:
Proj4php::$defs["GEOMONTEMARIO"] = "+proj=longlat +ellps=intl +towgs84=-104.1,-49.1,-9.9,0.971,-2.917,0.714,-11.68 +pm=rome +no_defs"; //EPSG:4806 https://epsg.io/4806
//coordinate geografiche, DATUM ROMA 1940  riferite a Greenwich:
Proj4php::$defs["GEOROME1940"] = "+proj=longlat +ellps=intl  +towgs84=-104.1,-49.1,-9.9,0.971,-2.917,0.714,-11.68";
//UTM, WGS84, zona Nord
for ($i = 1; $i <= 60; $i++)
{
    //+proj=utm +zone=32 +datum=WGS84 +units=m +no_defs 
    Proj4php::$defs["UTMWGS84{$i}"] = "+proj=utm +zone={$i} +ellps=WGS84 +datum=WGS84 +no_defs";
}
//UTM, ED50, zona Nord
for ($i = 1; $i <= 60; $i++)
{
    Proj4php::$defs["UTMED50{$i}"] = "+proj=utm +zone={$i} +ellps=intl +towgs84=-87,-98,-121,0,0,0,0 +units=m +no_defs";
}
//GAUSS-BOAGA, ROMA 1940 (rif. Monte Mario), fuso Ovest, metrico
//https://www.rigacci.org/wiki/doku.php/tecnica/gps_cartografia_gis/gauss_boaga_wgs84
//da verificare il primo:
Proj4php::$defs["GAUSSBOAGA_ZONE_1"] = "+proj=tmerc +ellps=intl +lat_0=0 +lon_0=9 +k=0.999600 +x_0=1500000 +y_0=0 +no_defs +a=6378388 +rf=297 +towgs84=-104.1,-49.1,-9.9,0.971,-2.917,0.714,-11.68 +to_meter=1";
Proj4php::$defs["GAUSSBOAGA_ZONE_1"] = "+proj=tmerc +lat_0=0 +lon_0=9 +k=0.9996 +x_0=1500000 +y_0=0 +ellps=intl +units=m +towgs84=-104.1,-49.1,-9.9,0.971,-2.917,0.714,-11.68 +no_defs";
Proj4php::$defs["GAUSSBOAGA_ZONE_2"] = "+proj=tmerc +lat_0=0 +lon_0=15 +k=0.9996 +x_0=2520000 +y_0=0 +ellps=intl +units=m +towgs84=-104.1,-49.1,-9.9,0.971,-2.917,0.714,-11.68 +no_defs";
Proj4php::$defs["GAUSSBOAGA_SARDINIA"] = "+proj=tmerc +lat_0=0 +lon_0=9 +k=0.9996 +x_0=1500000 +y_0=0 +ellps=intl +units=m +towgs84=-168.6,-34.0,38.6,-0.374,-0.679,-1.379,-9.48 +no_defs";
Proj4php::$defs["GAUSSBOAGA_SICILY"] = "+proj=tmerc +lat_0=0 +lon_0=9 +k=0.9996 +x_0=1500000 +y_0=0 +ellps=intl +units=m +towgs84=-50.2,-50.4,84.8,-0.690,-2.012,0.459,-28.08 +no_defs";

//-------------------------------internal--------------------------------------<





class openkis_geoconverter extends stdClass
{

    var
            $wgs84lat;
    var
            $wgs84lon;

    function __construct($lat_y, $lon_x, $coordtype = "", $proj = "")
    {

        if ($lat_y == 0 || $lon_x == 0 || $lat_y === ""  || $lon_x === "")
        {
         //   return;
        }
        if ($proj != "" && $coordtype != "")
        {
            Proj4php::$defs[strtoupper($coordtype)] = $proj;
        }
        else
        {
            if (!isset(Proj4php::$defs[strtoupper($coordtype)]))
            {
                if (false !== stristr($lon_x, "mario"))
                {
                    $coordtype = "GEOMONTEMARIO";
                }
            }
        }
        $this->proj4 = new Proj4php();

        $lon_x = $this->normalize_grad($lon_x, false);
        $lat_y = $this->normalize_grad($lat_y, true);

        //$coordtype=strtoupper("$coordtype");
        if (!isset(Proj4php::$defs[strtoupper($coordtype)]))
        {
            echo "\n/*todo: |$coordtype|   $lat_y,$lon_x **/";
            echo "\n/*n: $coordtype   $lat_y,$lon_x **/";
            if (strstr($lat_y, ".") || strstr($lat_y, ",") || strstr($lat_y, "°"))
                $coordtype = "GEOWGS84";
            else
                $coordtype = "UTMWGS8432";
        }
        $ct1 = $coordtype;
        $ct2 = "GEOWGS84";
        $ct1 = new Proj4phpProj($ct1, $this->proj4);
        $ct2 = new Proj4phpProj($ct2, $this->proj4);
        if ($coordtype == "GEOROME1940MONTEMARIOW")
        {
            $lon_x = 0 - $lon_x;
        }
        $pointSrc = new proj4phpPoint($lon_x, $lat_y);
        $pointDest = $this->proj4->transform($ct1, $ct2, $pointSrc);
        $this->wgs84lat = $this->FLOATNUM($pointDest->y);
        $this->wgs84lon = $this->FLOATNUM($pointDest->x);
        //die("th:{$this->wgs84lat},{$this->wgs84lon}");
    }

    /**
     * 
     * @param type $ct
     * @return type
     */
    function GetCoordinates($ct)
    {
        $ct_dest = new Proj4phpProj($ct, $this->proj4);
        $ct_source = new Proj4phpProj("GEOWGS84", $this->proj4);
        $pointSrc = new proj4phpPoint($this->wgs84lon, $this->wgs84lat);
        $pointDest = $this->proj4->transform($ct_source, $ct_dest, $pointSrc);
        $northing = $this->FLOATNUM($pointDest->y);
        $easting = $this->FLOATNUM($pointDest->x);
        return array("x" => $easting, "y" => $northing);
    }

    /**
     * 
     * @param type $mixed
     * @return type
     */
    function isint($mixed)
    {
        return ( preg_match('/^\d*$/', $mixed) == 1 );
    }

    /**
     * 
     * @return type
     */
    function getWGS84Geo()
    {
        return array("lat" => $this->wgs84lat, "lon" => $this->wgs84lon, "latitude" => $this->wgs84lat, "longitude" => $this->wgs84lon);
    }

    /**
     * 
     * @return type
     */
    function getWGS84_DMS()
    {
        return $this->todms($this->wgs84lat, $this->wgs84lon);
    }

    /**
     * 
     * @return type
     */
    function getWGS84UTM()
    {
        return $this->ConvertGeoToUTM($this->wgs84lat, $this->wgs84lon, $datum = "WGS84");
    }

    /**
     * 
     * @param type $datumname
     * @return type
     */
    function getGEO($datumname)
    {
        return $this->convert_datum_to_datum($this->wgs84lat, $this->wgs84lon, "WGS84", $datumname);
    }

    /**
     * 
     * @param type $datumname
     * @return type
     */
    function getGEO_DMS($datumname)
    {
        $latlon = $this->convert_datum_to_datum($this->wgs84lat, $this->wgs84lon, "WGS84", $datumname);
        $ret = $this->todms($latlon['lat'], $latlon['lon']);
        return $ret;
    }

    /**
     * 
     * @param type $datumname
     * @return type
     */
    function getUTM($datumname)
    {
        $latlon = $this->convert_datum_to_datum($this->wgs84lat, $this->wgs84lon, "WGS84", $datumname);
        return $this->ConvertGeoToUTM($latlon['lat'], $latlon['lon'], $datumname);
    }

    /**
     * 
     * @return type
     */
    function getGAUSSBOAGA_ZONE_1()
    {
        $ct1 = "GEOWGS84";
        $ct2 = "GAUSSBOAGA_ZONE_1";
        $ct1 = new Proj4phpProj($ct1, $this->proj4);
        $ct2 = new Proj4phpProj($ct2, $this->proj4);
        $pointSrc = new proj4phpPoint($this->wgs84lon, $this->wgs84lat);
        $pointDest = $this->proj4->transform($ct1, $ct2, $pointSrc);
        $ret['lat'] = round($this->FLOATNUM($pointDest->y));
        $ret['lon'] = round($this->FLOATNUM($pointDest->x));
        return $ret;
    }

    /**
     * 
     * @return type
     */
    function getGEOMONTEMARIOGeo()
    {
        $ct1 = new Proj4phpProj("GEOWGS84", $this->proj4);
        $ct2 = new Proj4phpProj("GEOROME1940MONTEMARIOW", $this->proj4);

        $pointSrc = new proj4phpPoint($this->wgs84lon, $this->wgs84lat); //x,y


        $pointDest = $this->proj4->transform($ct1, $ct2, $pointSrc);
        if ($this->wgs84lon > 12.452111)
            $pointDest->x = 1 - $this->FLOATNUM($pointDest->x);
        $ret = array(
            'lat' => $this->FLOATNUM($pointDest->y),
            'lon' => $this->FLOATNUM($pointDest->x)
        );

        return $ret;
    }

    /**
     * 
     * @return string
     */
    function getGEOMONTEMARIO_DMS()
    {
        $latlon = $this->getGEOMONTEMARIOGeo();
        if ($latlon['lon'] < 0)
        {
            $latlon['lon'] = 0 - $latlon['lon'];
            $lett = "Ovest di Monte Mario";
        }
        else
        {
            $lett = "E di Monte Mario";
        }
        $ret = $this->todms($latlon['lat'], $latlon['lon']);
        $ret['lon'] = $ret['lon'] . " " . $lett;
        return $ret;
    }

    /**
     * 
     * @return type
     */
    function getGEOMONTEMARIOWGeo()
    {
        $ct1 = new Proj4phpProj("GEOWGS84", $this->proj4);
        $ct2 = new Proj4phpProj("GEOROME1940MONTEMARIOW", $this->proj4);

        $pointSrc = new proj4phpPoint($this->wgs84lon, $this->wgs84lat); //x,y


        $pointDest = $this->proj4->transform($ct1, $ct2, $pointSrc);
        if ($this->wgs84lon > 12.452111)
            $pointDest->x = 1 - $this->FLOATNUM($pointDest->x);
        $ret = array(
            'lat' => $this->FLOATNUM($pointDest->y),
            'lon' => $this->FLOATNUM($pointDest->x)
        );

        return $ret;
    }

    /**
     * 
     * @return type
     */
    function getGEOMONTEMARIOW_DMS()
    {
        $latlon = $this->getGEOMONTEMARIOWGeo();
        return $this->todms($latlon['lat'], $latlon['lon']);
    }

    /**
     * 
     * @return type
     */
    function getGEOMONTEMARIOG_DMS()
    {
        $latlon = $this->getGEOMONTEMARIOGGeo();
        $ret = $this->todms($latlon['lat'], $latlon['lon']);
        return $ret;
    }

    /**
     * 
     * @return type
     */
    function getGEOMONTEMARIOGGeo()
    {
        $latlon = $this->convert_datum_to_datum($this->wgs84lat, $this->wgs84lon, "WGS84", "ROME1940");
        $latlon['lon'] = $latlon['lon'];
        $ret['lat'] = $latlon['lat'];
        $ret['lon'] = $latlon['lon'];
        return $ret;
    }

    /**
     * converte coordinate da stringa a in dd.dddddd mm.mmmmm WGS84
     * es 44° 10' 12'' N 4° 10' 23'' W di M.Mario
     * @param string $lat
     * @param string $lon
     * @param string $datum
     */
    function to_wgs84($lat, $lon, $datumfrom = "")
    {
        $datumname = strtoupper($datumfrom);
        $mmario = false;
        if (preg_match("/mario/is", $lon))
        {
            $mmario = true;
        }
        if ($datumname == "")
        {
            $datumname = "WGS84";
            if (preg_match("/mario/is", $lon))
            {
                $mmario = true;
            }
        }
        if ($mmario)
        {
            
        }
        $lat = $this->normalize_grad($lat, true, $datumname);
        $lon = $this->normalize_grad($lon, false, $datumname);
        if ($lat > 360 || $lon > 360)
        {
            $lat = 0;
            $lon = 0;
        }
        if (strtoupper($datumname) == "WGS84")
        {
            $ct1 = "GEOWGS84";
        }
        if (strtoupper($datumname) == "ED50")
        {
            $ct1 = "GEOED50";
        }
        if (strtoupper($datumname) == "ROME1940")
        {
            $ct1 = "GEOROME1940";
        }
        if ($mmario)
        {
            $ct1 = "GEOROME1940MONTEMARIOW";
            $lon = 0 - $lon;
        }
        $ct2 = "GEOWGS84";
        if ($lat > 360 || $lon > 360)
        {
            $lat = 0;
            $lon = 0;
        }
        $ct1 = new Proj4phpProj($ct1, $this->proj4);
        $ct2 = new Proj4phpProj($ct2, $this->proj4);
        $pointSrc = new proj4phpPoint($lon, $lat); //x,y
        $pointDest = $this->proj4->transform($ct1, $ct2, $pointSrc);
        return array(
            'lat' => $this->FLOATNUM($pointDest->y),
            'lon' => $this->FLOATNUM($pointDest->x)
        );
    }

    /**
     * converte coordinate geografiche da un datum e l'altro
     * @param string $lat
     * @param string $lon
     * @param array $datum1
     * @param array $datum2
     */
    function convert_datum_to_datum($lat, $lon, $datum1, $datum2)
    {

        if (strtoupper($datum1) == "WGS84")
        {
            $ct1 = "GEOWGS84";
        }
        if (strtoupper($datum1) == "ED50")
        {
            $ct1 = "GEOED50";
        }
        if (strtoupper($datum1) == "ROME1940")
        {
            $ct1 = "GEOROME1940";
        }
        if (strtoupper($datum2) == "WGS84")
        {
            $ct2 = "GEOWGS84";
        }
        if (strtoupper($datum2) == "ED50")
        {
            $ct2 = "GEOED50";
        }
        if (strtoupper($datum2) == "ROME1940")
        {
            $ct2 = "GEOROME1940";
        }
        if ($lat > 360 || $lon > 360)
        {
            $lat = 0;
            $lon = 0;
        }

        $ct1 = new Proj4phpProj($ct1, $this->proj4);
        $ct2 = new Proj4phpProj($ct2, $this->proj4);
        $pointSrc = new proj4phpPoint($lon, $lat); //x,y
        $pointDest = $this->proj4->transform($ct1, $ct2, $pointSrc);
        return array(
            'lat' => $this->FLOATNUM($pointDest->y),
            'lon' => $this->FLOATNUM($pointDest->x)
        );
    }

    /**
     * converte dd.ddddd a d°m's''
     * @param type $lat
     * @param type $lon
     * @return type
     */
    function todms($lat, $lon)
    {
        $la = $this->ConvertDDToDMS($lat);
        $lo = $this->ConvertDDToDMS($lon);
        return array(
            'lat' => $la,
            'lon' => $lo
        );
    }

    /**
     * 
     * @param type $dd
     * @return type
     */
    function ConvertDDToDMS($dd)
    {
        $deg = intval(floatval($dd)) | 0; // truncate dd to get degrees
        $frac = abs(floatval($dd) - $deg); // get fractional part
        $min = intval(floatval($frac) * 60) | 0; // multiply fraction by 60 and truncate
        $sec = round(($frac * 3600 - $min * 60) * 1000) / 1000;

        return $deg . "° " . $min . "' " . $sec . "''";
    }

    /**
     * converte coordinate in d.dddddd m.mmmmmm ovest
     *
     * @param string $str
     * @param string $lat
     * @param string $datum
     */
    function normalize_grad($str, $lat = true)
    {

        $str = html_entity_decode($str);
        $str = html_entity_decode($str);
        $grad = "";
        $min = "";
        $sec = "";
        $str = str_replace(",", ".", $str);
        $i = 0;
        while ($i < strlen($str) && is_numeric($str[$i]) == false)
        {
            $i++;
        }
        while ($i < strlen($str))
        {
            if (is_numeric($str[$i]) != false || $str[$i] == '.')
            {
                $grad = $grad . $str[$i];
            }
            else
            {
                break;
            }
            $i++;
        }
        while ($i < strlen($str) && is_numeric($str[$i]) == false)
        {
            $i++;
        }
        while ($i < strlen($str))
        {
            if (is_numeric($str[$i]) != false || $str[$i] == '.' || $str[$i] == ',')
            {
                $min = $min . $str[$i];
            }
            else
            {
                break;
            }
            $i++;
        }
        while ($i < strlen($str) && is_numeric($str[$i]) == false)
        {
            $i++;
        }
        while ($i < strlen($str))
        {
            if (is_numeric($str[$i]) != false || $str[$i] == '.' || $str[$i] == ',')
            {
                $sec = $sec . $str[$i];
            }
            else
            {
                break;
            }
            $i++;
        }
        $grad = doubleval($grad);
        $min = doubleval($min);
        $sec = doubleval($sec);
        $grad += doubleval($min / 60);
        $grad += doubleval(( $sec / 60) / 60);
        // cerco di capire se sono coordinate riferite a monte mario
        //dprint_r($str);
        if ($lat == false)
        {
            if (stristr($str, "ovest") != false || stristr($str, "W") != false || stristr($str, "west") != false)
            {
                $grad = 0 - $grad;
            }
        }
        else
        {
            //latitude sud
            if (($this->str_word($str, "SUD") || $this->str_word($str, "S")) && (!$this->str_word($str, "NORD") && !$this->str_word($str, "N")))
            {
                $grad = 0 - $grad;
            }
        }

        //dprint_r($grad);
        return $grad;
    }

    /**
     *
     * @param string $string
     * @param string $needle
     */
    function str_word($string, $needle)
    {
        return preg_match("/([^[:alpha:]]|^)" . str_replace("/", "\\/", $needle) . "([^[:alpha:]]|" . '$' . ")/si", $string);
    }

    /**
     * @param string $latitude  dd.dddddd
     * @param string $longitude dd.dddddd
     */
    function ConvertGeoToUTM($latitude, $longitude, $datum = "WGS84")
    {
        //----WGS84---------------------------------------------------------------------
        return $this->convertDecimalToUTM($latitude, $longitude, $datum);
    }

    function convertDecimalToUTM($latitude, $longitude, $datum)
    {
        //dprint_r($latitude);
        if (!is_numeric($latitude) || !is_numeric($longitude))
            return false;
        $latitude = floatval($latitude);
        $longitude = floatval($longitude);
        // make sure longitude is between -180 and 180
        if ($longitude < -180.0)
            $longitude += 360.0;
        if ($longitude > 180.0)
            $longitude -= 360.0;
        // get UTM letter
        if ($latitude <= 84.0 && $latitude >= 72.0)
            $utmLetter = 'X';
        else if ($latitude < 72.0 && $latitude >= 64.0)
            $utmLetter = 'W';
        else if ($latitude < 64.0 && $latitude >= 56.0)
            $utmLetter = 'V';
        else if ($latitude < 56.0 && $latitude >= 48.0)
            $utmLetter = 'U';
        else if ($latitude < 48.0 && $latitude >= 40.0)
            $utmLetter = 'T';
        else if ($latitude < 40.0 && $latitude >= 32.0)
            $utmLetter = 'S';
        else if ($latitude < 32.0 && $latitude >= 24.0)
            $utmLetter = 'R';
        else if ($latitude < 24.0 && $latitude >= 16.0)
            $utmLetter = 'Q';
        else if ($latitude < 16.0 && $latitude >= 8.0)
            $utmLetter = 'P';
        else if ($latitude < 8.0 && $latitude >= 0.0)
            $utmLetter = 'N';
        else if ($latitude < 0.0 && $latitude >= -8.0)
            $utmLetter = 'M';
        else if ($latitude < -8.0 && $latitude >= -16.0)
            $utmLetter = 'L';
        else if ($latitude < -16.0 && $latitude >= -24.0)
            $utmLetter = 'K';
        else if ($latitude < -24.0 && $latitude >= -32.0)
            $utmLetter = 'J';
        else if ($latitude < -32.0 && $latitude >= -40.0)
            $utmLetter = 'H';
        else if ($latitude < -40.0 && $latitude >= -48.0)
            $utmLetter = 'G';
        else if ($latitude < -48.0 && $latitude >= -56.0)
            $utmLetter = 'F';
        else if ($latitude < -56.0 && $latitude >= -64.0)
            $utmLetter = 'E';
        else if ($latitude < -64.0 && $latitude >= -72.0)
            $utmLetter = 'D';
        else if ($latitude < -72.0 && $latitude >= -80.0)
            $utmLetter = 'C';
        else
            $utmLetter = 'Z'; // returns 'Z' if the latitude is outside the UTM limits of 84N to 80S

        $zone = (int) ( ( $longitude + 180 ) / 6 ) + 1;
        if ($latitude >= 56.0 && $latitude < 64.0 && $longitude >= 3.0 && $longitude <
                12.0)
            $zone = 32;
        // Special zones for Svalbard.
        if ($latitude >= 72.0 && $latitude < 84.0)
        {
            if ($longitude >= 0.0 && $longitude < 9.0)
                $zone = 31;
            else if ($longitude >= 9.0 && $longitude < 21.0)
                $zone = 33;
            else if ($longitude >= 21.0 && $longitude < 33.0)
                $zone = 35;
            else if ($longitude >= 33.0 && $longitude < 42.0)
                $zone = 37;
        }
        if (strtoupper($datum) == "WGS84")
        {
            $ct1 = "GEOWGS84";
            $ct2 = "UTMWGS84{$zone}{$utmLetter}";
        }
        if (strtoupper($datum) == "ED50")
        {
            $ct1 = "GEOED50";
            $ct2 = "UTMED50{$zone}";
        }
        if (!isset(Proj4php::$defs[$ct2]))
        {
            $ct2 = "UTM{$datum}32";
        }
        $ct1 = new Proj4phpProj($ct1, $this->proj4);
        $ct2 = new Proj4phpProj($ct2, $this->proj4);
        $pointSrc = new proj4phpPoint($longitude, $latitude);
        $pointDest = $this->proj4->transform($ct1, $ct2, $pointSrc);
        $return["northing"] = round($pointDest->y);
        $return["easting"] = round($pointDest->x);
        $return["zone"] = $zone . $utmLetter;
        return $return;
    }

    function FLOATNUM($str)
    {
        return (str_replace(",", ".", $str));
    }

}

?>
