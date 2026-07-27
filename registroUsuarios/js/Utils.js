function getParameterByName(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
    var results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

var SEDE_STORAGE_KEY = "srnSede";

/** Token de la URL o, si falta, el guardado en localStorage (srnPc). */
function obtenerTokenSesion() {
    var token = getParameterByName("token").replace(/^\s+|\s+$/g, "");
    if (token) {
        return token;
    }
    var almacenado = localStorage.getItem("srnPc");
    return almacenado ? almacenado.replace(/^\s+|\s+$/g, "") : "";
}

/** Sede de la URL o, si falta, la guardada en localStorage. */
function obtenerSedeSesion() {
    var sede = getParameterByName("sede").replace(/^\s+|\s+$/g, "");
    if (sede) {
        localStorage.setItem(SEDE_STORAGE_KEY, sede);
        return sede;
    }
    var almacenada = localStorage.getItem(SEDE_STORAGE_KEY);
    return almacenada ? almacenada.replace(/^\s+|\s+$/g, "") : "";
}

function guardarSedeSesion(sede) {
    sede = (sede || "").replace(/^\s+|\s+$/g, "");
    if (sede) {
        localStorage.setItem(SEDE_STORAGE_KEY, sede);
    } else {
        localStorage.removeItem(SEDE_STORAGE_KEY);
    }
}

/**
 * Garantiza que la pagina tenga ?token= y ?sede= en la URL (y en localStorage).
 * Si no hay token en ningun lado, redirige a index.php.
 * Nota: si la URL trae token pero no sede, se rehidrata sede desde localStorage
 * solo cuando existe valor guardado (permite "Todas" borrando srnSede).
 */
function asegurarTokenSesion() {
    var token = getParameterByName("token").replace(/^\s+|\s+$/g, "");
    var sede = getParameterByName("sede").replace(/^\s+|\s+$/g, "");
    var tokenAlmacenado = localStorage.getItem("srnPc");
    var sedeAlmacenada = localStorage.getItem(SEDE_STORAGE_KEY);
    if (tokenAlmacenado) {
        tokenAlmacenado = tokenAlmacenado.replace(/^\s+|\s+$/g, "");
    }
    if (sedeAlmacenada) {
        sedeAlmacenada = sedeAlmacenada.replace(/^\s+|\s+$/g, "");
    }

    if (sede) {
        localStorage.setItem(SEDE_STORAGE_KEY, sede);
    }

    if (token) {
        if (token !== tokenAlmacenado) {
            localStorage.setItem("srnPc", token);
        }
        // Solo reinyectar sede si falta en URL y hay valor guardado.
        // Si el usuario eligio "Todas", srnSede ya fue borrado.
        if (!sede && sedeAlmacenada && location.search.indexOf("sede=") === -1) {
            var paginaConSede = location.pathname.split("/").pop() || "index.php";
            var params = [];
            var raw = location.search.replace(/^\?/, "");
            if (raw) {
                raw.split("&").forEach(function (par) {
                    if (par && par.indexOf("token=") !== 0 && par.indexOf("sede=") !== 0) {
                        params.push(par);
                    }
                });
            }
            var destinoConSede = paginaConSede + "?token=" + encodeURIComponent(token)
                + "&sede=" + encodeURIComponent(sedeAlmacenada);
            if (params.length) {
                destinoConSede += "&" + params.join("&");
            }
            destinoConSede += location.hash;
            location.replace(destinoConSede);
        }
        return;
    }

    if (tokenAlmacenado) {
        var pagina = location.pathname.split("/").pop() || "index.php";
        var destino = pagina + "?token=" + encodeURIComponent(tokenAlmacenado);
        var sedeFinal = sede || sedeAlmacenada;
        if (sedeFinal) {
            destino += "&sede=" + encodeURIComponent(sedeFinal);
        }
        location.replace(destino);
        return;
    }

    var inicio = "index.php";
    var sedeInicio = sede || sedeAlmacenada;
    if (sedeInicio) {
        inicio += "?sede=" + encodeURIComponent(sedeInicio);
    }
    location.replace(inicio);
}

/** Cambia la sede en la URL actual y recarga (mantiene token y demas params). */
function cambiarSedeSesion(nuevaSede) {
    guardarSedeSesion(nuevaSede);
    var params = new URLSearchParams(location.search);
    var token = params.get("token") || obtenerTokenSesion();
    if (token) {
        params.set("token", token);
    }
    if (nuevaSede) {
        params.set("sede", nuevaSede);
    } else {
        params.delete("sede");
    }
    var query = params.toString();
    var pagina = location.pathname.split("/").pop() || "index.php";
    location.assign(pagina + (query ? "?" + query : ""));
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
}

function enlaceRefrescarIndex() {
    if (typeof jQuery === "undefined") {
        return;
    }
    jQuery("#refrescar").click(function () {
        var sede = obtenerSedeSesion();
        var destino = "index.php";
        if (sede) {
            destino += "?sede=" + encodeURIComponent(sede);
        }
        window.location = destino;
    });
}

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
                console.log("token Generado");
            }
        }
    });
}
