<?php

/**
 * Sheet of a fauna sighting, footer. Runs before viewheader.php: blocks pushed
 * into $GLOBALS['openkis_view_side'] go in the side column (map and coordinates
 * of the cave or artificial cavity of the sighting), echoed html goes below the
 * sheet at full width (other species of the cave, other sightings of the
 * species). The species, the cave and the cavity are left in
 * $GLOBALS['openkis_sighting'] for the title of the sheet.
 */
global $_FN;
require_once "extra/openkis/openkis_sheet.inc.php";
require_once "extra/openkis/openkis_fauna.inc.php";
$GLOBALS['openkis_view_side'] = array();
$GLOBALS['openkis_sighting'] = array("species" => false, "place" => false, "mod" => "");
$species_field = openkis_FaunaSpeciesField();
$species_name = ($species_field !== "" && isset($row[$species_field])) ? trim($row[$species_field]) : "";
if ($species_name !== "")
{
    $species = openkis_FaunaRecordsByField("fauna", "scientific_name", array($species_name), "id,scientific_name,name,type");
    if (count($species))
        $GLOBALS['openkis_sighting']['species'] = reset($species);
}
//---- cave or artificial cavity of the sighting: map and coordinates ---------->
foreach (array("caves" => "codecave", "artificials" => "codeartificial") as $place_mod => $place_field)
{
    if (empty($row[$place_field]) || !file_exists("sections/$place_mod"))
        continue;
    $places = openkis_FaunaRecordsByField($place_mod, "code", array(trim($row[$place_field])),
        "id,code,name,comune,provincia,elevation,latitude,longitude,latitude_txt,longitude_txt,coordinates_type,original_coordinates_type");
    if (count($places))
    {
        $place = reset($places);
        $GLOBALS['openkis_sighting']['place'] = $place;
        $GLOBALS['openkis_sighting']['mod'] = $place_mod;
        $GLOBALS['openkis_view_side'] = openkis_SheetMapCards($place_mod, $place);
        break;
    }
}
//---- cave or artificial cavity of the sighting -------------------------------<
// other species observed in the same cave
if (!empty($row['codecave']))
{
    echo openkis_FaunaCaveCard(trim($row['codecave']), $row['id'], FN_Translate("other species observed in the cave"));
}
// the other sightings of the species, with the map of their caves
if ($species_name !== "")
{
    echo openkis_FaunaSpeciesCard($species_name, $row['id'], FN_Translate("other sightings of the species"));
}
