function getParameterByName(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
    var results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

/** Token de la URL o, si falta, el guardado en localStorage (srnPc). */
function obtenerTokenSesion() {
    return getParameterByName("token") || localStorage.getItem("srnPc") || "";
}

/**
 * Garantiza que la pagina tenga ?token= en la URL y en localStorage.
 * Si no hay token en ningun lado, redirige a index.php.
 */
function asegurarTokenSesion() {
    var token = getParameterByName("token");
    var sede = getParameterByName("sede");
    var almacenado = localStorage.getItem("srnPc");

    if (token) {
        if (token !== almacenado) {
            localStorage.setItem("srnPc", token);
        }
        return;
    }

    if (almacenado) {
        var pagina = location.pathname.split("/").pop();
        var destino = pagina + "?token=" + encodeURIComponent(almacenado);
        if (sede) {
            destino += "&sede=" + encodeURIComponent(sede);
        }
        location.replace(destino);
        return;
    }

    var inicio = "index.php";
    if (sede) {
        inicio += "?sede=" + encodeURIComponent(sede);
    }
    location.replace(inicio);
}

function srnPc() {
    var d = new Date();
    var dateint = d.getTime();
    var letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    var total = letters.length;
    var keyTemp = "";
    for (var i = 0; i < 6; i++) {
        keyTemp += letters[parseInt((Math.random() * (total - 1) + 1))];
    }
    keyTemp += dateint;
    return keyTemp;
}

function saveSrnPc() {
    localStorage.setItem("srnPc", srnPc());
//    saveToken();
//    localStorage.removeItem("srnPc");
}

$("#refrescar").click(function () {
    window.location = "index.php"
})


function saveToken() {
    var data = new FormData();
    data.append("token", localStorage.getItem("srnPc"));
    $.ajax({
        async: true,
        type: "POST",
        url: "Model/saveToken.php",
        data: data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function (data) {
            console.log(data);
            var json = JSON.parse(data);
            if (json["filas"] === 1) {
                console.log("token Generado")
            }
        }
    });
}





