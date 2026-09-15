<?php
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/inc/token_sesion.php';
require_once __DIR__ . '/inc/auth.php';

use Huella\Core\Database;
use Huella\Repositories\BiometricRepository;

requerir_admin();
list($token, $sede) = requerir_token_sesion();
$repository = new BiometricRepository(new Database());
$listaSedes = $repository->getHeadquartersList();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sedes | Monteblanco</title>
    <link rel="shortcut icon" href="imagenes/marca/isotipo.svg" />
    <?php require_once __DIR__ . '/inc/marca.php'; marca_head_assets(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="Css/estilo.css?v=20260915k" rel="stylesheet" type="text/css" />
    <script src="js/jquery-1.7.2.min.js" type="text/javascript"></script>
    <script src="js/Utils.js?v=20260915k" type="text/javascript"></script>
    <script type="text/javascript">asegurarTokenSesion();</script>
</head>
<body class="biometric-body">
    <div class="biometric-shell">
        <div id="mensaje">
            <img id="imageMenssage" class="message-icon" alt="" />
            <div class="messageStyle">
                <p id="txtMensaje" class="mb-0"></p>
            </div>
        </div>

        <div class="container page-wrap">
            <div class="glass-card topbar-card mb-4">
                <div class="row g-4 align-items-center">
                    <div class="col-12 col-xl-5">
                        <?php marca_product_badge('Ingreso Usuarios'); ?>
                        <span class="eyebrow">Módulo administrativo</span>
                        <h1 class="page-title">Sedes</h1>
                    </div>
                    <div class="col-12 col-xl-7">
                        <?php auth_render_nav('sedes', $token, $sede); ?>
                    </div>
                </div>
            </div>

            <div class="glass-card section-card mb-4">
                <h2 class="section-title">Nueva sede</h2>
                <p class="section-copy mb-4">Agrega una sede para asociarla a colaboradores e ingresos.</p>
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="field-label" for="nuevaSede">Nombre</label>
                        <input class="form-control biometric-input" id="nuevaSede" type="text" placeholder="Nombre de la sede" />
                    </div>
                    <div class="col-md-4 d-grid">
                        <button class="btn btn-primary btn-lg rounded-4" id="agregarSede" type="button">Agregar sede</button>
                    </div>
                </div>
            </div>

            <div class="glass-card section-card">
                <h2 class="section-title">Sedes registradas</h2>
                <p class="section-copy mb-4">Solo se pueden borrar sedes que no tengan usuarios asignados.</p>
                <div id="sedesLista" class="d-grid gap-2">
                    <?php if (empty($listaSedes)) { ?>
                        <div class="empty-placeholder">No hay sedes registradas.</div>
                    <?php } else { ?>
                        <?php foreach ($listaSedes as $item) { ?>
                            <div class="d-flex justify-content-between align-items-center gap-3 border rounded-4 px-3 py-2">
                                <span><?php echo htmlspecialchars($item['nombre']); ?></span>
                                <button class="btn btn-sm btn-outline-danger rounded-4 btn-eliminar-sede" type="button" data-id="<?php echo htmlspecialchars($item['id']); ?>" data-nombre="<?php echo htmlspecialchars($item['nombre']); ?>">Borrar</button>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
            <?php marca_footer(); ?>
        </div>
    </div>
    <script>
        function escapeHtml(texto) {
            return $("<div>").text(texto || "").html();
        }

        function showMessageBox(mensaje, type) {
            var clas = "";
            switch (type) {
                case "success": clas = "mensaje_success"; break;
                case "danger": clas = "mensaje_danger"; break;
                default: clas = "mensaje_warning";
            }
            $("#mensaje").removeClass("mensaje_success mensaje_danger mensaje_warning").addClass(clas).show();
            $("#txtMensaje").text(mensaje);
            setTimeout(function () { $("#mensaje").fadeOut(); }, 3200);
        }

        function renderSedes(sedes) {
            var lista = [];
            if (!sedes || !sedes.length) {
                lista.push('<div class="empty-placeholder">No hay sedes registradas.</div>');
            } else {
                $.each(sedes, function (_, sedeItem) {
                    lista.push(
                        '<div class="d-flex justify-content-between align-items-center gap-3 border rounded-4 px-3 py-2">' +
                            '<span>' + escapeHtml(sedeItem.nombre) + '</span>' +
                            '<button class="btn btn-sm btn-outline-danger rounded-4 btn-eliminar-sede" type="button" data-id="' + escapeHtml(sedeItem.id) + '" data-nombre="' + escapeHtml(sedeItem.nombre) + '">Borrar</button>' +
                        '</div>'
                    );
                });
            }
            $("#sedesLista").html(lista.join(""));
        }

        $("#agregarSede").on("click", function () {
            $.ajax({
                type: "POST",
                url: "Model/SedesAdmin.php",
                dataType: "json",
                data: { action: "create", nombre: $("#nuevaSede").val() },
                success: function (data) {
                    if (data.success) {
                        $("#nuevaSede").val("");
                        renderSedes(data.sedes || []);
                        showMessageBox(data.message || "Sede creada con exito", "success");
                    } else {
                        showMessageBox(data.message || "No fue posible crear la sede", "warning");
                    }
                },
                error: function () {
                    showMessageBox("No fue posible crear la sede", "danger");
                }
            });
        });

        $(document).on("click", ".btn-eliminar-sede", function () {
            var sedeNombre = $(this).data("nombre");
            if (!confirm('Vas a borrar la sede "' + sedeNombre + '".')) {
                return;
            }
            $.ajax({
                type: "POST",
                url: "Model/SedesAdmin.php",
                dataType: "json",
                data: { action: "delete", sede: $(this).data("id") },
                success: function (data) {
                    if (data.success) {
                        renderSedes(data.sedes || []);
                        showMessageBox(data.message || "Sede eliminada con exito", "success");
                    } else {
                        showMessageBox(data.message || "No fue posible eliminar la sede", "warning");
                    }
                },
                error: function () {
                    showMessageBox("No fue posible eliminar la sede", "danger");
                }
            });
        });
    </script>
</body>
</html>
