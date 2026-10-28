<?php

/**
 * Bibliography items linked to a cave or an artificial cavity.
 *
 * A bibliography item lists the caves it talks about as comma separated codes
 * (ctl_bibliography.codecaves = "LO12,LO7,...", codeartificials for the
 * artificial cavities). Finding the items of one cave in those lists takes
 * LIKE '%,CODE,%', which reads the whole bibliography on every cave page
 *. The codes are copied, one row each, into ctl_bibliography_links,
 * indexed on (linkfield, code), and the cave pages look them up there.
 *
 * The link table is rebuilt from the bibliography when the bibliography has
 * changed since the last build: every save through OpenKIS (forms, imports,
 * API) touches the table's change marker, as for the map caches. After the
 * data were changed directly in the database the table catches up within an
 * hour, or at once with Control center -> Utilities -> Refresh map data.
 */

/** seconds after which the link table is rebuilt even if nothing was saved */
define("OPENKIS_BIBLIOGRAPHY_LINKS_MAXAGE", 3600);

/**
 * Condition on ctl_bibliography for the items that list $code in $linkfield,
 * for FNDBVIEW's appendquery.
 *
 * @param string $code cave or artificial cavity code
 * @param string $linkfield "codecaves" or "codeartificials"
 * @return string|false the condition, false when no item lists the code
 */
function openkis_BibliographyFilter($code, $linkfield = "codecaves")
{
    $code = trim($code);
    if ($code === "" || !in_array($linkfield, array("codecaves", "codeartificials")))
    {
        return false;
    }
    $ids = openkis_BibliographyLinkIds($code, $linkfield);
    if ($ids === false)
    {
        // no link table (not MySQL, or it couldn't be built): scan as before
        $c = addslashes(str_replace(array("\\", "%", "_"), array("\\\\", "\\%", "\\_"), $code));
        return "($linkfield LIKE '$c' OR $linkfield LIKE '$c,%' OR $linkfield LIKE '%,$c,%' OR $linkfield LIKE '%,$c')";
    }
    if (count($ids) == 0)
    {
        return false;
    }
    return "id IN (" . implode(",", $ids) . ")";
}

/**
 * Ids of the bibliography items that list $code in $linkfield.
 *
 * @param string $code
 * @param string $linkfield
 * @return array|false ids (integers), false when the link table can't be used
 */
function openkis_BibliographyLinkIds($code, $linkfield)
{
    $links = openkis_BibliographyLinksTable();
    if (!$links)
    {
        return false;
    }
    $db = $links->driverclass;
    $rows = $db->dbQuery("SELECT `bibliography_id` FROM `{$db->sqltable}` WHERE `linkfield` = '" . $db->conn->real_escape_string($linkfield)
        . "' AND `code` = '" . $db->conn->real_escape_string($code) . "'");
    if (!is_array($rows))
    {
        return false;
    }
    $ids = array();
    foreach ($rows as $row)
    {
        $ids[intval($row['bibliography_id'])] = intval($row['bibliography_id']);
    }
    return array_values($ids);
}

/**
 * The link table, rebuilt first if the bibliography changed since it was built.
 *
 * @return object|false the XMETATable of ctl_bibliography_links, false when it
 *         can't be used (not MySQL, or the bibliography table is missing)
 */
function openkis_BibliographyLinksTable()
{
    static $checked = null;
    if ($checked !== null)
    {
        return $checked;
    }
    $checked = false;
    $biblio = FN_XMDBTable("ctl_bibliography");
    $links = FN_XMDBTable("ctl_bibliography_links");
    if (!openkis_BibliographyIsMysql($biblio) || !openkis_BibliographyIsMysql($links))
    {
        return false;
    }
    $db = $links->driverclass;
    // read before the bibliography, so a save made during the build triggers another one
    $stamp = openkis_MapCacheStamp("ctl_bibliography");
    $built = $db->dbQuery("SELECT `code` FROM `{$db->sqltable}` WHERE `linkfield` = '' LIMIT 1");
    if (!is_array($built))
    {
        return false;
    }
    if (isset($built[0]['code']))
    {
        $b = explode(":", $built[0]['code']);
        // built from this version, and after its second (the marker has 1 s resolution)
        if (count($b) == 2 && intval($b[0]) >= $stamp && intval($b[1]) > $stamp
            && time() - intval($b[1]) < OPENKIS_BIBLIOGRAPHY_LINKS_MAXAGE)
        {
            $checked = $links;
            return $checked;
        }
    }
    if (openkis_BibliographyLinksBuild($biblio, $links, $stamp))
    {
        $checked = $links;
    }
    return $checked;
}

/**
 * @param object $table XMETATable
 * @return bool true when the table is stored in MySQL/MariaDB
 */
function openkis_BibliographyIsMysql($table)
{
    return is_object($table) && isset($table->driverclass) && is_object($table->driverclass)
        && isset($table->driverclass->conn) && $table->driverclass->conn instanceof mysqli
        && !empty($table->driverclass->sqltable);
}

/**
 * Codes of a comma separated list, trimmed, without empty or repeated ones.
 *
 * @param string $list
 * @return array
 */
function openkis_BibliographySplitCodes($list)
{
    $codes = array();
    foreach (explode(",", (string) $list) as $code)
    {
        $code = trim($code);
        if ($code !== "")
        {
            $codes[$code] = $code;
        }
    }
    return array_values($codes);
}

/**
 * Rebuilds the link table from the bibliography, in one transaction so the
 * pages keep reading the previous version meanwhile. Only one request builds
 * at a time: the others go on with the old table (or the scan) instead of
 * waiting.
 *
 * @param object $biblio XMETATable of ctl_bibliography
 * @param object $links XMETATable of ctl_bibliography_links
 * @param int $stamp change time of the bibliography read before the build
 * @return bool true when the link table can be used
 */
function openkis_BibliographyLinksBuild($biblio, $links, $stamp)
{
    $db = $links->driverclass;
    $conn = $db->conn;
    $lock = "openkis_bibliography_links_" . $db->sqltable;
    $got = $db->dbQuery("SELECT GET_LOCK('" . $conn->real_escape_string($lock) . "', 0) AS l");
    if (!is_array($got) || empty($got[0]['l']))
    {
        // another request is building it: use the current table if it was ever built
        $any = $db->dbQuery("SELECT 1 AS x FROM `{$db->sqltable}` WHERE `linkfield` = '' LIMIT 1");
        return is_array($any) && count($any) > 0;
    }
    $ok = false;
    $fields = array();
    foreach (array("codecaves", "codeartificials") as $f)
    {
        if (isset($biblio->fields[$f]))
        {
            $fields[] = $f;
        }
    }
    $items = count($fields) ? $biblio->driverclass->dbQuery("SELECT `id`,`" . implode("`,`", $fields) . "` FROM `{$biblio->driverclass->sqltable}`") : array();
    if (is_array($items))
    {
        $values = array();
        foreach ($items as $item)
        {
            foreach ($fields as $f)
            {
                foreach (openkis_BibliographySplitCodes($item[$f]) as $code)
                {
                    $values[] = "('" . $conn->real_escape_string($item['id']) . "','$f','" . $conn->real_escape_string($code) . "')";
                }
            }
        }
        $values[] = "('','','" . intval($stamp) . ":" . time() . "')";
        $conn->begin_transaction();
        $ok = $db->dbQuery("DELETE FROM `{$db->sqltable}`") !== false;
        foreach (array_chunk($values, 500) as $chunk)
        {
            if (!$ok)
            {
                break;
            }
            $ok = $db->dbQuery("INSERT INTO `{$db->sqltable}` (`bibliography_id`,`linkfield`,`code`) VALUES " . implode(",", $chunk)) !== false;
        }
        if ($ok)
        {
            $conn->commit();
        }
        else
        {
            $conn->rollback();
            error_log("openkis: could not rebuild {$db->sqltable}: " . $conn->error);
        }
    }
    $db->dbQuery("SELECT RELEASE_LOCK('" . $conn->real_escape_string($lock) . "')");
    return $ok;
}
