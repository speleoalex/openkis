updateComuni = function ()
{
    var provincia = $("#select_provincia").val();
    //alert(provincia);
    //console.log(comuni);
    var html = "\n<option value=\"\"></option>";
    for (var i in comuni)
    {
        if (comuni[i]['provincia'] == provincia)
        {
            html += "\n<option value=\"" + comuni[i]['comune'] + "\">" + comuni[i]['comune'] + "</option>";
        }
    }
    if (html != "")
    {
        //     document.getElementById("select_comune").innerHTML=html;
        $("#select_comune").html(html);
    }
    //console.log(html);
};
updateProvince = function ()
{
    var regione = $("#select_regione").val();
    //alert(regione);
    //console.log(province);
    var html = "\n<option value=\"\"></option>";
    for (var i in province)
    {
        if (province[i]['regione'] == regione)
        {
            html += "\n<option value=\"" + province[i]['name'] + "\">" + province[i]['name'] + "</option>";
        }
    }
    if (html != "")
    {
        //     document.getElementById("select_comune").innerHTML=html;
        $("#select_provincia").html(html);
        updateComuni();
    }
    console.log(html);
};


window.addEventListener('load', (event) => {
    var regione = $("#select_regione").val();
    var provincia = $("#select_provincia").val();
    if (regione != "" && provincia == null)
    {
        updateProvince();
    }
});