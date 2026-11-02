<?php
/**
 * Below the map of the natural caves (sections/map_caves): summary of the
 * caves in the register, as on the home of the Liguria register.
 */
require_once "extra/openkis/cavestats/cavestats.inc.php";
?>
<style>
.openkis-home-number{font-size:1.8rem;font-weight:600;line-height:1.1;color:#d67e36}
.openkis-home-total{background:#fbf1e8}
.openkis-home-ranking td,.openkis-home-ranking th{vertical-align:middle;padding-left:.75rem;padding-right:.75rem}
.openkis-home-ranking .openkis-sheet-code{font-size:.7rem;font-weight:500}
</style>
<?php
echo openkis_CaveStats(array(
    "region" => "Lombardia",
    "provinces" => array(
        "BG" => "Bergamo", "BS" => "Brescia", "CO" => "Como", "CR" => "Cremona", "LC" => "Lecco", "LO" => "Lodi",
        "MB" => "Monza e Brianza", "MI" => "Milano", "MN" => "Mantova", "PV" => "Pavia", "SO" => "Sondrio", "VA" => "Varese",
    ),
));
