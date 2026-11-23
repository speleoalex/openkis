<?php
/**
 * Run by finis (include/autoexec.php) on every page of the site, after FN_InitSections():
 * the sections a region does not use, listed in $_FN['openkis_sections_off'] by
 * extra/openkis/custom/<region>/<region>.php, leave the list of the request, so they are
 * neither in the menus nor openable (FN_UserCanViewSection() is false for a section that
 * is not in the list). openkis ships every section and each region turns off the ones it
 * does not use: a deploy of the whole tree that brings a new section does not show it.
 * The control center runs no autoexec, so the administrator still sees them in the sitemap.
 */
defined('_FNEXEC') or die('Restricted access');
if (!empty($_FN['openkis_sections_off']))
{
    // FN_InitSections() leaves the list empty, FN_GetSections() would read it again anyway
    if (empty($_FN['sections']))
        $_FN['sections'] = FN_GetAllSections();
    foreach ($_FN['openkis_sections_off'] as $section)
        unset($_FN['sections'][$section]);
    // website.php has already cached the values of every section (FN_GetSectionValues()),
    // the requested one included: an empty mod gets the page of a missing section
    if (in_array($_FN['mod'], $_FN['openkis_sections_off']))
        $_FN['mod'] = "";
}
