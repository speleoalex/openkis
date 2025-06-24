<?php
global $_FN;
$_FN['src_application'] = empty($_FN['src_application']) ? "." : $_FN['src_application'];

class FINIS
{

    function __construct($config = array())
    {
        global $_FN;
        foreach ($config as $k => $_item)
        {
            $_FN[$k] = $_item;
        }
        require_once __DIR__ . "/include/finis.php";
    }

    public function __call($name, $arguments)
    {
        global $_FN;
        if (file_exists("{$_FN['src_application']}/include/applications/{$name}.php"))
        {
            include_once "{$_FN['src_application']}/include/applications/{$name}.php";
        }
        elseif (file_exists("{$_FN['src_finis']}/include/applications/{$name}.php"))
        {
            include_once "{$_FN['src_finis']}/include/applications/{$name}.php";
        }
        elseif (file_exists("{$_FN['src_application']}/include/methods/{$name}.php"))
        {
            include_once "{$_FN['src_application']}/include/methods/{$name}.php";
        }
        elseif (file_exists("{$_FN['src_finis']}/include/methods/{$name}.php"))
        {
            include_once "{$_FN['src_finis']}/include/methods/{$name}.php";
        }
        else
        {
            // Handle the error for undefined method
            throw new Exception("Method $name does not exist");
        }
    }
    function setTable($tablename,$params)
    {
        global $_FN;
        $_FN['tables'][$tablename]=$params;
    }
    function setVar($id, $value)
    {
        global $_FN;
        $_FN[$id] = $value;
    }

    function runSection($section = "")
    {
        global $_FN;
        $section = $section ? $section : $_FN['mod'];
        if (FN_UserCanViewSection($section))
        {
            FN_RunSection($section, false);
        }
    }

    function runFolder($folder)
    {
        FN_RunFolder($folder, false);
    }

    function finis()
    {
        $application = FN_GetParam("fnapp", $_GET);
        if ($application == "")
        {
            $application = "website";
        }
        $this->__call($application, array());
    }
    function isConsole()
    {
        global $_FN;
        return $_FN['consolemode'] ;
    }
}
