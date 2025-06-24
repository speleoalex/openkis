<?php

if (FN_IsAdmin()&&!empty($_GET['clearcache']))
{
    FN_ClearCache();
}
if (!empty($_FN['use_cache']))
{
    //sections
    $cachekey="sections";
    if (file_exists("{$_FN['datadir']}/_cache/$cachekey"))
    {
        $allByKey=unserialize(file_get_contents("{$_FN['datadir']}/_cache/$cachekey"));
        if ($allByKey)
        {
            $_FN[$cachekey]=$allByKey;
        }
    }
    else
    {
        $_FN[$cachekey]=FN_GetAllSections();
        FN_Write(serialize($_FN[$cachekey]),"{$_FN['datadir']}/_cache/$cachekey");
    }
    //blocks
    if (file_exists("{$_FN['datadir']}/_cache/blocks"))
    {
        $allByKey=unserialize(file_get_contents("{$_FN['datadir']}/_cache/blocks"));
        if ($allByKey)
        {
            $_FN['blocks']=$allByKey;
        }
    }
    else
    {
        $_FN['blocks']=FN_GetAllBlocks();
        FN_Write(serialize($_FN['blocks']),"{$_FN['datadir']}/_cache/blocks");
    }
    if (file_exists("{$_FN['datadir']}/_cache/sectionstypes"))
    {
        $allByKey=unserialize(file_get_contents("{$_FN['datadir']}/_cache/sectionstypes"));
        if ($allByKey)
        {
            $_FN['sectionstypes']=$allByKey;
        }
    }
    else
    {
        $_FN['sectionstypes']=FN_GetAllSectionTypes();
        FN_Write(serialize($_FN['sectionstypes']),"{$_FN['datadir']}/_cache/sectionstypes");
    }
}
?>