<?php

/**
 * Files of the historical sheets of a cave: the scans of the old cadastral
 * registers, one folder per cave ("0042_name"), files one level of subfolders
 * deep. Used by the cave sheet of the regions that serve the same archives
 * (extra/openkis/custom/liguria and custom/wish).
 */

/**
 * Files of the historical sheets of a cave. Folders are looked up by exact
 * code, then zero padded, then zero padded with $wildcard ("_*" for the
 * archives that separate the code from the title, "*" for the ones that do
 * not). Windows thumbnails are skipped.
 *
 * @param string $basedir folder of the archive
 * @param string $id cave code without the regional prefix
 * @param string $wildcard suffix pattern of the folder names
 * @return array file paths, empty when the cave has no sheet
 */
function openkis_HistoricSheetFiles($basedir, $id, $wildcard = "_*")
{
    $padded = sprintf("%04d", $id);
    $folders = array();
    foreach (array("{$basedir}/{$id}", "{$basedir}/{$padded}", "{$basedir}/{$padded}{$wildcard}") as $pattern)
    {
        $folders = glob($pattern);
        if ($folders && count($folders))
            break;
    }
    $files = array();
    foreach ((array) $folders as $folder)
    {
        foreach ((array) glob("{$folder}/*") as $item)
        {
            if (is_dir($item))
                $files = array_merge($files, (array) glob("{$item}/*.*"));
            else
                $files[] = $item;
        }
    }
    return array_values(array_filter($files, function ($file)
    {
        return !is_dir($file) && basename($file) != "Thumbs.db";
    }));
}

/**
 * Card of one file of the historical sheets: icon of the type (a thumbnail for
 * images), link to ops_getfile.php. Template historic_file_card.tp.html.
 *
 * @param string $file path of the file
 * @return string html
 */
function openkis_HistoricSheetFileCard($file)
{
    global $_FN;
    // the path may contain spaces and special characters: encode it in the URL
    $fileurl = str_replace("%2F", "/", rawurlencode($file));
    $ext = strtolower(FN_GetFileExtension($file));
    if (in_array($ext, array("doc", "docx", "odt", "rtf")))
        $icon = "images/mime/doc.png";
    elseif (in_array($ext, array("txt", "csv", "tro", "th", "svx")))
        $icon = "images/mime/text.png";
    elseif ($ext == "pdf")
        $icon = "images/mime/pdf.png";
    else
        $icon = "images/mime/unknown.png";
    if ($ext == "jpg" || $ext == "jpeg" || $ext == "png")
        $icon = "thumb.php?f={$fileurl}&amp;h=300&amp;w=300";
    return openkis_TplApply("historic_file_card", array(
        "url" => $_FN['siteurl'] . "ops_getfile.php?f={$fileurl}",
        "icon" => $_FN['siteurl'] . $icon,
        "title" => htmlspecialchars(basename($file)),
    ));
}
