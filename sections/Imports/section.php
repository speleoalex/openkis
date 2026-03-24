<?php

// SELECT * FROM `ctl_caves` WHERE code REGEXP '^[0-9\.]+$'
// DELETE  FROM `ctl_caves` WHERE code REGEXP '^[0-9\.]+$'
global $_FN;
$mod = $_FN['mod'];
$op = FN_GetParam("op", $_REQUEST);
$id = FN_GetParam("id", $_REQUEST);

//require_once "include/flatnux.php";
if (!FN_IsAdmin())
{
    echo FN_LoginForm();
}
else
{
    if (!file_exists("{$_FN['datadir']}/wish_imports"))
        mkdir("{$_FN['datadir']}/wish_imports");

    $files = glob($_FN['datadir'] . "/wish_imports/*.inc.php");

    if (is_array($files))
    {
        foreach ($files as $file)
        {
            require_once $file;
        }
    }

    $table_regioni = FN_XMDBTable("ctl_wishregioni");
    $regioni = $table_regioni->GetRecords();

    if ($op == "")
    {
        echo "<h3>Scaricamento dati</h3>";
        //--------------------visualizzazione files------------------------------------------------>
        echo "<a href=\"?mod=$mod&\">Scarica tutti i files</a><br />";
        foreach ($regioni as $regione)
        {
            $file_details = "";
            $filename = "{$_FN['datadir']}/wish_imports/{$regione['id']}.txt";
            if (file_exists($filename))
            {
                $file_details = "  " . FN_FormatDate(filemtime($filename));
            }
            echo "<a href=\"?mod=$mod&op=get&id={$regione['id']}\">Scarica {$regione['regione']}</a>";
            if (empty($regione['url_data_caves']))
            {
                echo " N.D.";
            }
            echo "$file_details<br />";
        }
        //--------------------visualizzazione files------------------------------------------------<
        //--------------------visualizzazione importazioni----------------------------------------->
        echo "<h3>Aggiornamento dati</h3>";
        echo "<a href=\"?mod=$mod&op=update&id=updateall\">Aggiorna tutto</a><br />";
        foreach ($regioni as $regione)
        {
            echo "<a href=\"?mod=$mod&op=update&id={$regione['id']}\">Aggiorna {$regione['regione']}</a>";

            if (empty($regione['driver']))
            {
                echo " n.d.";
            }
            echo "<br />";
        }
        //--------------------visualizzazione importazioni-----------------------------------------<
    }
//--------------------importazione dati --------------------------------------->
    if ($op == "update")
    {

        foreach ($regioni as $regione)
        {
            if ($id == "updateall" || $id == $regione['id'])
            {
                $id_regione = $id;

                //dprint_r($regione);
                if (!empty($regione['driver']))
                {
                    echo "Importazione {$regione['regione']} con algoritmo: {$regione['driver']} <br />\n";
                    if (function_exists("wish_Import_{$regione['driver']}"))
                    {
                        echo "Importazione regione {$regione['regione']} con funzione wish_Import_{$regione['driver']} <br />";
                        $fname = "wish_Import_{$regione['driver']}";
                        $filedata = "misc/wish_imports/{$regione['id']}.txt";
                        if (!file_exists($filedata) && $regione['file_caves'] != "")
                        {
                            $filedata = "misc/fndatabase/ctl_wishregioni/{$regione['id']}/file_caves/{$regione['file_caves']}";
                        }
                        if (file_exists($filedata))
                        {
                            //$data = file_get_contents($filedata);
                            $fname($filedata, $id_regione);
                        }
                        else
                        {
                            echo "$filedata mancante<br />";
                        }
                    }
                    else
                    {
                        echo "Non esiste una funzione wish_Import_{$regione['driver']}(\$data) per importare la regione {$regione['regione']}<br />";
                    }
                    /*
                      $text = wish_GetWebPage($regione['url_data_caves']);
                      if ($text)
                      {
                      if (file_exists("{$_FN['datadir']}/wish_imports/{$regione['id']}.txt"))
                      {
                      rename("{$_FN['datadir']}/wish_imports/{$regione['id']}.txt", "{$_FN['datadir']}/wish_imports/{$regione['id']}." . time() . ".txt");
                      }
                      file_put_contents("{$_FN['datadir']}/wish_imports/{$regione['id']}.txt", $text);
                      }
                     */
                }
                else
                {
                    echo "Nessun driver di importazione per la regione {$regione['regione']}<br />";
                }
            }
        }
        openkis_UpdateCoords("caves", false);
        echo "<br /><a class=\"btn btn-primary\" href=\"index.php?mod=$mod\">Torna alla lista</a>";
    }
//--------------------importazione dati ---------------------------------------<
//--------------------download     dati --------------------------------------->

    if ($op == "get")
    {
        foreach ($regioni as $regione)
        {
            if ($id == "" || $id == $regione['id'])
            {
                //dprint_r($regione);
                if (!empty($regione['url_data_caves']))
                {
                    if (false === stristr($regione['url_data_caves'],"http"))
                    {
                        $regione['url_data_caves']=$_FN['siteurl'].$regione['url_data_caves'];
                    }
                    echo "Scaricamento {$regione['regione']} all'url {$regione['url_data_caves']}<br />\n";
                    $text = wish_GetWebPage($regione['url_data_caves']);
                    if ($text)
                    {
                        if (file_exists("{$_FN['datadir']}/wish_imports/{$regione['id']}.txt"))
                        {
                            rename("{$_FN['datadir']}/wish_imports/{$regione['id']}.txt", "{$_FN['datadir']}/wish_imports/{$regione['id']}." . time() . ".txt");
                        }
                        file_put_contents("{$_FN['datadir']}/wish_imports/{$regione['id']}.txt", $text);
                        echo "$text";
                    }
                     echo "<br /><a class=\"btn btn-primary\" href=\"index.php?mod=$mod\">Torna alla lista</a>";
                }
            }
        }
    }
//--------------------download     dati ---------------------------------------<
}
?>

