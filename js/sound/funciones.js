


var timestamp = null;

var aux ='';


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
    $.ajax({
        async: true,
        type: "POST",
        url: "Model/ActivarSensorAdd.php",
        data: "&token=" + srn,
        dataType: "json",
        success: function (data) {
            var json = JSON.parse(data);
            console.log(json);
            if (json["filas"] === 1) {
                $("#activeSensorLocal").attr("disabled", true);
                $("#fingerPrint").css("display", "block");
            }
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
//            console.log(data);
            var json = JSON.parse(data);        
            if (json["filas"] === 1) {
                console.log(srn)
                $("#" + srn).attr("src", "imagenes/finger.png");
                $("#" + srn + "_texto").text("El sensor esta activado");
               
                $("#fingerPrint").css("display", "none");
            }

        }
    });
     alert("Usuario creado con exito");

}


function cargar_push(sede) {  

// if (true) {
var sed = sede;

    
// }else{

//     var estado}
    

var token = getParameterByName('token');
    $.ajax({
        async: true,
        type: "POST",
        url: "Model/httpush.php",
        data: "&tipo=" + aux +"&timestamp=" + timestamp + "&token=" + getParameterByName('token')+"&sede="+sede,
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
                    if (json["nombre"]!="------") {
                     showMessageBox("Hola, su registro se ha guardado  "+json["nombre"], "success");


                        var sound = new Howl({
                       src: ['sound/bermu.mp3'],
                    volume: 1.0,
                    onend: function () {
                     // alert('ok!');
                      }
                     });
                     sound.play()
                     }else{showMessageBox("NO EXISTE "+json["nombre"], "success");

     
                 
                 }
                  borrartemp(token);
                }
            }
            setTimeout("cargar_push(sed)", 1000);

                     // 
        }
    });
    
    // alert(aux);
}



function cargar_push1() {  

// if (true) {


    
// }else{

//     var estado}
    

var token = getParameterByName('token');
    $.ajax({
        async: true,
        type: "POST",
        url: "Model/httpush1.php",
        data: "&tipo=" + aux +"&timestamp=" + timestamp + "&token=" + getParameterByName('token'),
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
                 //    if (json["nombre"]!="------") {
                 //     showMessageBox("Hola, su registro se ha guardado  "+json["nombre"], "success");


                 //        var sound = new Howl({
                 //       src: ['sound/bermu.mp3'],
                 //    volume: 1.0,
                 //    onend: function () {
                 //     // alert('ok!');
                 //      }
                 //     });
                 //     sound.play()
                 //     }else{showMessageBox("NO EXISTE "+json["nombre"], "success");

     
                 
                 // }
                  borrartemp(token);
                }
            }
            setTimeout("cargar_push1()", 1000);

                     // 
        }
    });
    
    // alert(aux);
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







