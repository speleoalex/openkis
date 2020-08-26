<?php
require_once "include/flatnux.php";
header('cache-control: no-cache,no-store,must-revalidate'); 
header('pragma: no-cache'); 
header('expires: 0');

global $_FN;
$t=isset($_GET["t"]) ? $_GET["t"] : "";
$id=isset($_GET["id"]) ? $_GET["id"] : "";

if ( $t== "artificials" || $t== "ctl_artificials")
{
    $t="ctl_artificials";
    $mod="artificials";
    $tipo="artificiale";
    $t_photos="ctl_photos_artificials";
}
else
{
    $t="ctl_caves";
    $mod="caves";
    $tipo="naturale";
    $t_photos="ctl_photos";
}

$_GET['mod']=$mod;

require_once "include/flatnux.php";


header('cache-control: no-cache,no-store,must-revalidate');
header('pragma: no-cache');
header('expires: 0');
$name="Nessuna cavità selezionata";
if ($_FN['user']== "")
{
    echo "<h2>Per inserire nuove cavità devi prima eseguire l'accesso</h2>";
    echo FN_HtmlLoginForm();
    die("");
}
if ($id)
{
    $table=FN_XmlTable($t);
    $values=$table->GetRecordByPrimaryKey($id);
    $code = $values['code'];
  // dprint_r($values);
    if (isset($values ['name']))
    {
        $name=$values ['name'];
    }
}
?><html lang="it">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Gestionale speleologico</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <style type="text/css">
            #preview img{
                margin: 5px;
            }
            #preview2 img{
                margin: 5px;
            }
        </style>
    </head>
    <body style="padding: 5px;">
        <h2><?php echo $name;?></h2> 
        <p><a class="btn btn-primary" href="index.php?mod=<?php echo $mod;?>&op=edit&id=<?php echo $id;?>">Modifica scheda</a>
            <a class="btn btn-primary" href="index.php?mod=<?php echo $mod;?>&op=view&id=<?php echo $id;?>">Visualizza scheda</a></p>
        <h3>Aggiunta foto</h3>

        <form method='post' action='' enctype="multipart/form-data">
            <div id="uploadform" class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="inputGroupFileAddon01">Upload</span>
                </div>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="files" name="files[]"  multiple
                           aria-describedby="inputGroupFileAddon01">
                    <label class="custom-file-label" for="inputGroupFile01">Scegli foto</label>
                </div>
            </div>
            <div style='color:red' id="status"></div>

            <div class="form-group">
                <label class="" for="authorphoto1">Autore foto: </label><input class="form-control" id="authorphoto1" type="text" name="authorphoto1" />
            </div>        
            <div class="input-group">
                <!-- <input class="btn btn-primary" type="button" id="submit" value='Upload'> -->
            </div>
        </form>
        <!-- Preview -->
        <div id='preview'></div>
        <input type="hidden" id="session" value=''>
        <button id="confirm" class="btn btn-primary" style="display:none">Conferma inserimento</button>
        <button id="cancel" class="btn btn-danger" style="display:none">Annulla inserimento</button>
        <p>Foto già presenti in questa scheda:</p>
        <div id='preview2'></div>
        <div id='res'></div>

        <script src="https://code.jquery.com/jquery-3.3.1.min.js" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    </body>
    <script>
        $(document).ready(function () {
            $('#confirm').click(function () {
                var urlajax = 'openkis_photos_server.php?op=confirm&id=<?php echo $code;?>&t=<?php echo $t;?>' + "&session=" + $("#session").val();
                $.ajax({
                    url: urlajax,
                    type: 'post',
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    success: function (response) {
                        if (response.result)
                        {
                            alert("Le foto sono state caricate");
                            $("#uploadform").show();

                            window, location = "<?php echo $_FN['siteurl'];?>openkis_photos.php?id=<?php echo $id;?>&t=<?php echo $t;?>";
                                                } else
                                                {
                                                    alert("Errore caricamento");

                                                }
                                            }
                                        });

                                    }
                                    );

                                    $('#cancel').click(function () {
                                        var urlajax = 'openkis_photos_server.php?op=cancel&id=<?php echo $code;?>&t=<?php echo $t;?>' + "&session=" + $("#session").val();
                                        //$("#uploadform").show();
                                        $("#status").html("ANNULLAMENTO IN CORSO... ATTENDERE...");
                                        $.ajax({
                                            url: urlajax,
                                            type: 'post',
                                            dataType: 'json',
                                            contentType: false,
                                            processData: false,
                                            success: function (response) {
                                                
                                                $("#status").html("");
                                                if (response.result)
                                                {
                                                    window, location = "<?php echo $_FN['siteurl'];?>openkis_photos.php?id=<?php echo $id;?>&t=<?php echo $t;?>";
                                                                        }
                                                                    }
                                                                });

                                                            }
                                                            );
                                                            $('#files').change(function () {
                                                                // alert("funzione non ancora implementata");
                                                                // return;
                                                                if ($("#status").html() != "")
                                                                {
                                                                    return;
                                                                }
                                                                var form_data = new FormData();
                                                                $("#status").html("CARICAMENTO FOTO IN CORSO....ATTENDERE<br />" + "<img src=\"images/loading.gif\" />");
                                                                $("#uploadform").hide();

                                                                // Read selected files
                                                                var totalfiles = document.getElementById('files').files.length;
                                                                for (var index = 0; index < totalfiles; index++) {
                                                                    form_data.append("files[]", document.getElementById('files').files[index]);
                                                                }

                                                                // AJAX request
                                                                $.ajax({
                                                                    url: 'openkis_photos_server.php?t=<?php echo $t;?>',
                                                                    type: 'post',
                                                                    data: form_data,
                                                                    dataType: 'json',
                                                                    contentType: false,
                                                                    processData: false,
                                                                    success: function (response) {
                                                                        $("#status").html("");
                                                                        //$("#uploadform").show();

                                                                        var present = false;
                                                                        $("#session").val(response['session']);
                                                                        $('#preview').html("<h2>Nuove foto da inserire:</h2>");
                                                                        for (var index = 0; index < response['files'].length; index++) {
                                                                            var src = response['files'][index];
                                                                            // Add img element in <div id='preview'>
                                                                            $('#preview').append('<img src="' + src + '" width="200px;" height="200px">');
                                                                            present = true;
                                                                        }
                                                                        if (present)
                                                                        {
                                                                            $('#confirm').show();
                                                                            $('#cancel').show();
                                                                        } else {
                                                                            $('#confirm').hide();
                                                                            $('#cancel').show();
                                                                        }

                                                                    }
                                                                });

                                                            });
                                                            $.ajax({
                                                                url: 'openkis_photos_server.php?id=<?php echo $code;?>&t=<?php echo $t;?>',
                                                                type: 'post',
                                                                dataType: 'json',
                                                                contentType: false,
                                                                processData: false,
                                                                success: function (response) {
                                                                    var exists = false;
                                                                    for (var index = 0; index < response['files'].length; index++) {
                                                                        var src = response['files'][index];
                                                                        exists = true;
                                                                        var idphoto = response['photos'][index];
                                                                        // Add img element in <div id='preview'>
                                                                        var link_modify = "index.php?pk___xdb_<?php echo "$t_photos"?>=" + idphoto + "&op___xdb_<?php echo "$t_photos"?>=insnew&mod=<?php echo "$mod"?>&op=edit&id=<?php echo "$id"?>&inner=1";
                                                                        $('#preview2').append('<a title="clicca per modificare" href="' + link_modify + '"><img src="' + src + '" width="200px;" height="200px"></a>');
                                                                    }
                                                                    if (!exists)
                                                                    {
                                                                        $('#preview2').html("<p style='color:red'>Nessuna foto attualmente presente</p>");
                                                                    }

                                                                }
                                                            });
                                                        });
    </script>
</html>