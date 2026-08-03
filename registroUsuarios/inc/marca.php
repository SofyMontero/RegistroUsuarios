<?php
/**
 * Marca Monteblanco — helpers de UI (sutiles).
 */

function marca_head_assets()
{
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/svg+xml" href="imagenes/marca/isotipo.svg" />
    <?php
}

/**
 * Solo isotipo + copy de marca (sin textos de producto arriba).
 */
function marca_product_badge($producto = 'Ingreso Usuarios')
{
    ?>
    <div class="brand-lockup">
        <img class="brand-isotipo" src="imagenes/marca/isotipo.svg" width="48" height="28" alt="Monteblanco" />
        <div class="brand-lockup-text">
            <p class="brand-copy">Tecnología que hace crecer tu negocio.</p>
        </div>
    </div>
    <?php
}

/**
 * Pie de página: solo copyright.
 */
function marca_footer()
{
    $anio = date('Y');
    ?>
    <footer class="brand-footer">
        <div class="brand-footer-inner">
            <p class="brand-footer-copy">&copy; <?php echo $anio; ?> Monteblanco. Todos los derechos reservados.</p>
        </div>
    </footer>
    <?php
}
