<?php
global $_FN;
// regional additions to the sheet (extra/openkis/custom/<region>/viewfooter_survey_artificials.php)
if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php"))
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php");
