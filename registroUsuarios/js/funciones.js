


var timestamp = null;

var aux ='';
var enrollPollingEnabled = false;
var enrollPollingTimer = null;
var stationToken = null;

function setStationToken(token) {
    stationToken = (token || '').replace(/^\s+|\s+$/g, '');
}

function getStationToken() {
    if (stationToken) {
        return stationToken;
    }
    return typeof obtenerTokenSesion === 'function' ? obtenerTokenSesion() : '';
}

function mostrarPanelCaptura() {
    $("#fingerPrint").css("display", "block");
    $("#sensorPlaceholder").hide();
}

function actualizarVistaCaptura(json) {
    var id = json["id"] || getStationToken();
    if (!id) {
        return;
    }

    mostrarPanelCaptura();
    $("#" + id + "_status").text(json["statusPlantilla"] || '');
    $("#" + id + "_texto").text(json["texto"] || '');

    var imageHuella = json["imgHuella"];
    if (imageHuella && String(imageHuella).length > 20) {
        var src = String(imageHuella).indexOf('data:image') === 0
            ? imageHuella
            : "data:image/jpeg;base64," + imageHuella;
        $("#" + id).attr("src", src);
    }
}


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
    setStationToken(srn);
    timestamp = 0;

    $.ajax({
        async: true,
        type: "POST",
        url: "Model/ActivarSensorAdd.php",
        data: { token: srn },
        dataType: "json",
        success: function (data) {
            var json = (typeof data === "string") ? JSON.parse(data) : data;
            console.log(json);
            if (json["filas"] === 1) {
                $("#activeSensorLocal").attr("disabled", true);
                mostrarPanelCaptura();
                startEnrollPolling();
            } else {
                showMessageBox("No se pudo activar el sensor. Revise la conexion con el servidor.", "warning");
            }
        },
        error: function () {
            showMessageBox("Error al activar el sensor. Verifique que ActivarSensorAdd.php responda en el servidor.", "danger");
        }
    });
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
                $("#" + srn).attr("src", "imagenes/finger.png");
                $("#" + srn + "_texto").text("El sensor esta activado");
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
                    
                    if (usuarioEncontrado) {
                        showMessageBox("Bienvenido: " + json["nombre"], "success");
                        var sound = new Howl({
                            src: ['sound/bermu.mp3'],
                            volume: 1.0
                        });
                        sound.play();
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

    var token = getStationToken();
    $.ajax({
        async: true,
        type: "POST",
        url: "Model/httpush1.php",
        data: {
            tipo: aux,
            timestamp: timestamp || 0,
            token: token
        },
        dataType: "json",
        timeout: 15000,
        success: function (data) {
            var json = (typeof data === "string") ? JSON.parse(data) : data;
            if (json["timestamp"] !== false && json["timestamp"] !== null && json["timestamp"] !== undefined) {
                timestamp = json["timestamp"];
            }
            actualizarVistaCaptura(json);

            var tipo = json["tipo"];
            if (tipo === "leer") {
                $("#documento").val(json["documento"]);
                $("#nombre").val(json["nombre"]);
                borrartemp(token);
                timestamp = 0;
            }

            if (enrollPollingEnabled) {
                enrollPollingTimer = setTimeout(function () {
                    cargar_push1();
                }, 1000);
            }
        },
        error: function () {
            if (enrollPollingEnabled) {
                enrollPollingTimer = setTimeout(function () {
                    cargar_push1();
                }, 2000);
            }
        }
    });
}

function startEnrollPolling() {
    if (enrollPollingEnabled) {
        return;
    }
    enrollPollingEnabled = true;
    clearTimeout(enrollPollingTimer);
    cargar_push1();
}

function stopEnrollPolling() {
    enrollPollingEnabled = false;
    clearTimeout(enrollPollingTimer);
    enrollPollingTimer = null;
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




