


var timestamp = null;

var aux ='';
var enrollPollingEnabled = false;
var enrollPollingTimer = null;


function enviar_valores(valor){

 var  a = valor ;
    aux = a; 

}



function borrartemp(tok) {   



    $.ajax({
        async: true,
        type: "POST",
        url: "borrartemp.php",
        data: "token=" + tok,
        dataType: "json",
        success: function (data) {


       
 }
    });
}

function activarSensor(srn) {
    var token = (srn || obtenerTokenSesion()).replace(/^\s+|\s+$/g, "");
    if (!token) {
        showMessageBox("No hay token de sesion. Abra index.php primero.", "warning");
        return;
    }

    $("#fingerPrint").css("display", "block");
    $("#sensorPlaceholder").hide();
    $("#activeSensorLocal").attr("disabled", true);

    $.ajax({
        async: true,
        type: "POST",
        url: "Model/ActivarSensorAdd.php",
        data: { token: token },
        dataType: "json",
        success: function (data) {
            var json = (typeof data === "string") ? JSON.parse(data) : data;
            console.log("ActivarSensorAdd:", json);
            if (Number(json.filas) >= 1) {
                startEnrollPolling();
            } else {
                showMessageBox("No se pudo activar el sensor en el servidor.", "warning");
                $("#activeSensorLocal").attr("disabled", false);
            }
        },
        error: function (xhr) {
            console.error("ActivarSensorAdd error", xhr.status, xhr.responseText);
            showMessageBox("Error al activar el sensor. Revise la consola (F12).", "danger");
            $("#activeSensorLocal").attr("disabled", false);
        }
    });
}

function selectorPorToken(token) {
    return "[id='" + String(token).replace(/'/g, "\\'") + "']";
}

function uriImagenHuella(base64) {
    if (base64 == null || String(base64).length === 0) {
        return "";
    }
    var mime = String(base64).indexOf("/9j/") === 0 ? "jpeg" : "png";
    return "data:image/" + mime + ";base64," + base64;
}

function actualizarVistaCaptura(json) {
    var id = json.id || obtenerTokenSesion();
    var imageHuella = json.imgHuella;
    var $img = $(selectorPorToken(id));
    var $status = $(selectorPorToken(id + "_status"));
    var $texto = $(selectorPorToken(id + "_texto"));

    if ($status.length) {
        $status.text(json.statusPlantilla || "");
    }
    if ($texto.length) {
        $texto.val(json.texto || "");
    }

    if (imageHuella != null && String(imageHuella).length > 0) {
        $("#fingerPrint").css("display", "block");
        $("#sensorPlaceholder").hide();
        if ($img.length) {
            $img.attr("src", uriImagenHuella(imageHuella));
        }
    }
}


function addUser(srn) {
    var data = new FormData();
    var inputFile = document.getElementById("foto");
    var file = inputFile.files[0];
    if (file !== undefined) {
        data.append("foto", file);
    }
    data.append("token", srn);
    data.append("documento", $("#documento").val());
    data.append("nombre", $("#nombre").val());
    var sedeValor = $("#sedeSelect").length ? $("#sedeSelect").val() : "";
    if (!sedeValor && typeof obtenerSedeSesion === "function") {
        sedeValor = obtenerSedeSesion();
    }
    if (!sedeValor) {
        sedeValor = getParameterByName("sede") || "";
    }
    data.append("sede", sedeValor);
    // data.append("telefono", $("#tel").val());
    $.ajax({
        async: true,
        type: "POST",
        url: "Model/CrearUsuario.php",
        data: data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function (data) {
            var json = (typeof data === "string") ? JSON.parse(data) : data;
            if (json["filas"] === 1) {
                console.log(srn)
                $(selectorPorToken(srn)).attr("src", "imagenes/finger.png");
                $(selectorPorToken(srn + "_texto")).val("El sensor esta activado");
                $("#fingerPrint").css("display", "none");
                stopEnrollPolling();
                showMessageBox(json["message"] || "Usuario creado con exito", "success");
            } else {
                showMessageBox(json["message"] || "No fue posible guardar el usuario", "warning");
            }

        },
        error: function (xhr) {
            var mensaje = "No fue posible guardar el usuario";
            try {
                var respuesta = JSON.parse(xhr.responseText);
                if (respuesta.message) {
                    mensaje = respuesta.message;
                }
            } catch (e) {}
            showMessageBox(mensaje, "danger");
        }
    });
}


function cargar_push(sede) {  

// if (true) {
var sed = sede;

    
// }else{

//     var estado}
    

var token = obtenerTokenSesion();
    if (sede === undefined || sede === null) {
        sede = typeof obtenerSedeSesion === "function" ? obtenerSedeSesion() : (getParameterByName("sede") || "");
    }
    sede = String(sede);
    $.ajax({
        async: true,
        type: "POST",
        url: "Model/httpush.php",
        data: "&tipo=" + aux +"&timestamp=" + timestamp + "&token=" + token +"&sede="+sede,
        dataType: "json",
        success: function (data) {

            var json = JSON.parse(JSON.stringify(data));
            timestamp = json["timestamp"];
            imageHuella = json["imgHuella"];
            tipo = json["tipo"];
            id = json["id"];
            $("#" + id + "_status").text(json["statusPlantilla"]);
            $("#" + id + "_texto").text(json["texto"]);
            if (imageHuella !== null) {
                $("#" + id).attr("src", "data:image/png;base64," + imageHuella);
                if (tipo === "leer") {
                    $("#documento").text(json["documento"]);
                    $("#nombre").text(json["nombre"]);
                    $("#imageUser").attr("src", "imagenes/"+json["foto_usu"]);
                    
                    // Verificar si usuario fue encontrado
                    var usuarioEncontrado = json["nombre"] && json["nombre"] !== "------" && json["documento"];
                    var sedeOk = json["sede_ok"] !== false;
                    
                    if (usuarioEncontrado && sedeOk) {
                        var excedido = json["excedido"] === true || json["excedido"] === 1 || json["excedido"] === "1";
                        var mensajeMarcacion = json["mensaje_marcacion"] || json["texto"] || ("Bienvenido: " + json["nombre"]);
                        if (excedido) {
                            showMessageBox(mensajeMarcacion, "danger");
                        } else {
                            showMessageBox(mensajeMarcacion + (mensajeMarcacion.indexOf(json["nombre"]) >= 0 ? "" : (" — " + json["nombre"])), "success");
                            var sound = new Howl({
                                src: ['sound/bermu.mp3'],
                                volume: 1.0
                            });
                            sound.play();
                        }
                    } else if (!sedeOk) {
                        showMessageBox(json["texto"] || "Usuario no pertenece a esta sede", "warning");
                    } else {
                        showMessageBox("No existe un usuario registrado con esta huella", "warning");
                    }
                    
                    borrartemp(token);
                    timestamp = 0;
                }
            }
            setTimeout(function () {
                cargar_push(sede);
            }, 1000);

                     // 
        }
    });
    
    // alert(aux);
}



function cargar_push1() {  
    if (!enrollPollingEnabled) {
        return;
    }

// if (true) {


    
// }else{

//     var estado}
    

var token = obtenerTokenSesion();
    $.ajax({
        async: true,
        type: "POST",
        url: "Model/httpush1.php",
        data: { tipo: aux, timestamp: timestamp, token: token },
        dataType: "json",
        success: function (data) {
            var json = (typeof data === "string") ? JSON.parse(data) : data;
            if (json.error) {
                console.error("httpush1:", json.error);
            } else {
                timestamp = json.timestamp;
                actualizarVistaCaptura(json);
                if (json.tipo === "leer") {
                    $("#documento").val(json.documento);
                    $("#nombre").val(json.nombre);
                    $("#imageUser").attr("src", "imagenes/" + json.foto_usu);
                    borrartemp(token);
                    timestamp = 0;
                }
            }
            if (enrollPollingEnabled) {
                enrollPollingTimer = setTimeout(function () {
                    cargar_push1();
                }, 1000);
            }
        },
        error: function (xhr) {
            console.error("httpush1 error", xhr.status, xhr.responseText);
            if (enrollPollingEnabled) {
                enrollPollingTimer = setTimeout(function () {
                    cargar_push1();
                }, 2000);
            }
        }
    });
    
    // alert(aux);
}

function startEnrollPolling() {
    if (enrollPollingEnabled) {
        return;
    }
    enrollPollingEnabled = true;
    clearTimeout(enrollPollingTimer);
    conectarPluginWebSocket();
    cargar_push1();
}

function conectarPluginWebSocket() {
    if (typeof PluginBiometricoWs === "undefined") {
        return;
    }
    PluginBiometricoWs.conectar(17890, function (evento) {
        if (!enrollPollingEnabled || !evento) {
            return;
        }
        if (evento.tipo === "captura_progreso" && evento.datos) {
            actualizarVistaCaptura({
                id: obtenerTokenSesion(),
                imgHuella: evento.datos.imagenHuella,
                statusPlantilla: evento.datos.estadoPlantilla,
                texto: evento.datos.mensaje
            });
        }
        if (evento.tipo === "captura_completada" && evento.datos) {
            actualizarVistaCaptura({
                id: obtenerTokenSesion(),
                imgHuella: evento.datos.imagenHuella,
                statusPlantilla: evento.datos.estadoPlantilla,
                texto: evento.datos.mensaje
            });
        }
    });
}

function stopEnrollPolling() {
    enrollPollingEnabled = false;
    clearTimeout(enrollPollingTimer);
    enrollPollingTimer = null;
    if (typeof PluginBiometricoWs !== "undefined") {
        PluginBiometricoWs.cerrar();
    }
}

function getParameterByName(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}


function showMessageBox(mensaje, type) {
    var clas = "";
    var icono = "";
    switch (type) {
        case "success":
            clas = "mensaje_success";
            icono = "imagenes/success_16.png";
            // alert("Huella detectada Bienvenido");
            break;
        case "warning":
            clas = "mensaje_warning";
            icono = "imagenes/warning_16.png";
            break;
        case "danger":
            clas = "mensaje_danger";
            icono = "imagenes/danger_16.png";
            break;
    }

    $("#mensaje").addClass(clas);
    $("#txtMensaje").html(mensaje);
    $("#imageMenssage").attr("src", icono);
    $("#mensaje").fadeIn(5);
    setTimeout(function () {
        $("#mensaje").fadeOut(1500);
    }, 3000);

}




