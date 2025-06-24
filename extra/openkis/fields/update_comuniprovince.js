updateComuni = function () {
    var provincia = document.getElementById("select_provincia").value;
    var html = "\n<option value=\"\"></option>";
    for (var i in comuni) {
        if (comuni[i]['provincia'] === provincia) {
            html += "\n<option value=\"" + comuni[i]['comune'] + "\">" + comuni[i]['comune'] + "</option>";
        }
    }
    if (html !== "") {
        document.getElementById("select_comune").innerHTML = html;
    }
};

updateProvince = function () {
    var regione = document.getElementById("select_regione").value;
    var html = "\n<option value=\"\"></option>";
    for (var i in province) {
        if (province[i]['regione'] === regione) {
            html += "\n<option value=\"" + province[i]['name'] + "\">" + province[i]['name'] + "</option>";
        }
    }
    if (html !== "") {
        document.getElementById("select_provincia").innerHTML = html;
        updateComuni();
    }
    console.log(html);
};

window.addEventListener('load', (event) => {
    var regione = document.getElementById("select_regione").value;
    var provincia = document.getElementById("select_provincia").value;
    if (regione !== "" && provincia === null) {
        updateProvince();
    }
});
