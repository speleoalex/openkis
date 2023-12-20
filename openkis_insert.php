<?php
require_once "include/flatnux.php";
header('cache-control: no-cache,no-store,must-revalidate');
header('pragma: no-cache');
header('expires: 0');
$mod=$_FN['mod'];
$title=$_FN['sections'][$_FN['mod']]['title'];
?><!doctype html>
<html lang="it">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo $_FN['sitename'];?></title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    </head>
    <body class="m-2">
        <?php
        if ($_FN['user']== "")
        {
            echo "<div class=\"m-3\">";
            echo "<h3>".FN_Translate("login")."</h3>";
            echo FN_HtmlLoginForm();
            echo "</div>";
        }
        elseif (empty($_GET['mod']))
        {
            echo "<div class=\"m-3\">";
            echo "<a class=\"btn btn-primary m-2\" href=\"?mod=artificials\">Inserimento cavità artificiale</a>";
            echo "<a class=\"btn btn-primary m-2\" href=\"?mod=artificials\">Inserimento cavità naturale</a>";
            echo "<a class=\"btn btn-primary m-2\" href=\"?mod=springs\">Inserimento sorgente</a>";
            echo "</div>";
        }
        else
        {
            ?>

            <nav class="navbar navbar-dark bg-dark"><a class="navbar-brand" href="#">Inserimento rapido <?php echo $title;?></a></nav>
            <div  class="alert alert-info" ><em>Usa un buon telefono, attendi che l' accuratezza del GPS scenda almeno sotto i 10 metri, 
                    fai la foto dell'ingresso e solo a quel punto 
                    clicca su "invia a <?php echo $_FN['sitename']?>". <br />Per evitare doppioni verifica il <a class="btn btn-secondary" onclick="GetNear();" href="#">record più vicina alla tua posizione</a></em></div>
            <form action="<?php echo $_FN['siteurl'];?>index.php?mod=<?php echo $mod?>&op=new" method="post" enctype="multipart/form-data" >
                <button class="btn btn-primary" type="button" onclick="getLocation()">Autocompila con la tua posizione GPS, accuratezza: <span id="precisioneattuale" style="color:red">N.D.</span></button>            
                <div></div>
                <div class="form-group row">
                    <label class="col-sm-4 col-form-label" for="lat">Nome:</label><div class="col-sm-8"><input required="required" class="form-control" id="name" name="name" value="" /></div>
                    <label class="col-sm-4 col-form-label" for="lat">Latitudine:</label><div class="col-sm-8"><input class="form-control" id="lat" name="latitude_txt" /></div>
                    <label class="col-sm-4 col-form-label" for="lon">Longitudine:</label><div class="col-sm-8"><input class="form-control" id="lon" name="longitude_txt" /></div>
                    <label class="col-sm-4 col-form-label" for="elevation">Quota:</label><div class="col-sm-8"><input class="form-control" id="elevation" name="elevation" /></div>
                    <label class="col-sm-4 col-form-label" for="location_evaluation">Valutazione dato:</label><div class="col-sm-8"><input class="form-control" id="location_evaluation" name="location_evaluation" /></div>
                    <!--<label for="regione">Regione:</label><div class="col-sm-8"><input class="form-control" id="regione" name="regione" />-->
                    <label class="col-sm-4 col-form-label" for="provincia">Provincia:</label><div class="col-sm-8"><input class="form-control" id="provincia" name="provincia" /></div>
                    <label class="col-sm-4 col-form-label" for="comune">Comune:</label><div class="col-sm-8"><input class="form-control" id="comune" name="comune" /></div>
                    <label class="col-sm-4 col-form-label" for="localita">Località:</label><div class="col-sm-8"><input class="form-control" id="localita" name="localita" /></div>
                    <label class="col-sm-4 col-form-label" for="photo1">Immagine Ingresso:</label><div class="col-sm-8"><input required="required" class="" id="photo1" type="file" name="photo1" /></div>
                    <input type="hidden" name="xmldbsave" value="1"/>
                    <input type="hidden" name="coordinates_type"  value="GPS Geografiche WGS84" readonly="readonly"/>        
                </div>
                <button class="btn btn-primary" type="submit">invia dati a <?php echo $_FN['sitename']?></button>
                <pre id="risposta"></pre>
            </form>
            <a class="btn btn-dark" href="openkis_insert.php?mod=artificials">Vai a cavità artificiali</a>
            <a class="btn btn-dark" href="openkis_insert.php?mod=caves">Vai a cavità naturali</a>
            <a class="btn btn-dark" href="openkis_insert.php?mod=springs">Vai sorgenti</a>
            <?php
        }
        ?>

        <script>
            var x = document.getElementById("demo");
            function getLocation() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(showPosition);
                } else {
                    x.innerHTML = "Geolocation is not supported by this browser.";
                }
            }
            function GetNear()
            {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function (position) {


                        $.getJSON({
                            url: "openkis_API.php",
                            type: "GET",
                            data: {
                                mod: "<?php echo $mod?>",
                                op: "near",
                                lat: position.coords.latitude,
                                lon: position.coords.longitude
                            },
                            success: function (result) {
                                console.log(result);
                                alert("La cavità più vicina risulta a " + result.methers + " metri \nNome:" + result.cave.name + " \nNumero di catasto:" + result.cave.code);
                            },
                            error: function (richiesta, stato, errori) {
                                alert("Non riesco a trovare la cavità più vicina");
                            }
                        });



                    });

                } else {
                    alert("Geolocation is not supported by this browser.");
                }

            }

            function aggiornaPrecisione() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function (position) {
                        if (position.coords.accuracy) {
                            $("#precisioneattuale").html(Math.round(position.coords.accuracy) + "m");
                            if (position.coords.accuracy > 10)
                            {
                                $("#precisioneattuale").css('color', 'red');
                            } else
                            {
                                $("#precisioneattuale").css('color', 'green');

                            }
                            window.setTimeout("aggiornaPrecisione();", 1000);
                        }
                    });
                }
            }
            function showPosition(position) {
                if (position.coords.accuracy > 10)
                {
                    alert("Attenzione, la precisione attuale del GPS è superiore a 10 metri !\nRimani fermo qualche secondo e clicca nuovamente su \"Compila automaticamente\" per migliorare la posizione");
                }
                document.getElementById("lat").value = position.coords.latitude;
                document.getElementById("lon").value = position.coords.longitude;
                document.getElementById("elevation").value = position.coords.altitude;
                document.getElementById("location_evaluation").value = "posizionata con GPS telefono, accurateza rilevata:" + Math.round(position.coords.accuracy) + " m";
                if (position.coords.altitudeAccuracy !== null)
                {
                    document.getElementById("location_evaluation").value += " Precisione quota: " + Math.round(position.coords.altitudeAccuracy);
                } else
                {
                    document.getElementById("location_evaluation").value += " Quota non rilevata";
                }
                $.getJSON({
                    url: "bs_request.php",
                    type: "GET",
                    data: {
                        lat: position.coords.latitude,
                        lon: position.coords.longitude
                    },
                    success: function (result) {
                        console.log(result);
                        if (result.regione)
                        {
                            var regione = result.regione[0] + result.regione[1];
                            regione = regione.toString().toUpperCase();
                            if (regione !== "LI")
                            {
                                alert("Il punto non sembra essere in Liguria");
                            } else
                            {
                                $("#regione").val(regione);
                            }
                        }
                        if (result.comune)
                        {
                            $("#COMUNE").val(result.comune.toUpperCase());
                        }
                        if (result.provincia)
                        {
                            var provincia = result.provincia.toUpperCase();
                            switch (result.provincia.toUpperCase())
                            {
                                case "GENOVA":
                                    provincia = "GE";
                                    break;
                                case "SAVONA":
                                    provincia = "GE";
                                    break;
                                case "LA SPEZIA":
                                    provincia = "SP";
                                    break;
                                case "IMPERIA":
                                    provincia = "IM";
                                    break;
                            }
                            $("#PROV").val(provincia);

                        }

                    },
                    error: function (richiesta, stato, errori) {
                        $("pre#risposta").html("<strong>Chiamata fallita:</strong>" + stato + " " + errori);
                    }
                });
            }
        </script>
        <script src="https://code.jquery.com/jquery-3.3.1.min.js" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
        <script>
            $(function () {
                window.setTimeout("aggiornaPrecisione();", 1000);

            });
        </script>
    </body>
</html>



