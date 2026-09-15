/**
 * DataTables global Monteblanco.
 * Cualquier tabla.report-table.js-datatable queda con el mismo criterio.
 */
(function (window, document) {
    var language = {
        decimal: ',',
        thousands: '.',
        search: '',
        searchPlaceholder: 'Buscar en la tabla',
        lengthMenu: '_MENU_',
        info: 'Mostrando _START_–_END_ de _TOTAL_',
        infoEmpty: 'Sin registros',
        infoFiltered: '(filtrados de _MAX_)',
        infoPostFix: '',
        zeroRecords: 'No hay coincidencias para esa búsqueda',
        emptyTable: 'No hay datos para mostrar',
        loadingRecords: 'Cargando…',
        processing: 'Procesando…',
        paginate: {
            first: '«',
            last: '»',
            next: '›',
            previous: '‹'
        },
        aria: {
            sortAscending: ': ordenar ascendente',
            sortDescending: ': ordenar descendente',
            paginate: {
                first: 'Primera página',
                last: 'Última página',
                next: 'Página siguiente',
                previous: 'Página anterior'
            }
        }
    };

    var defaults = {
        pageLength: 25,
        lengthMenu: [
            [10, 25, 50, 100, -1],
            ['10', '25', '50', '100', 'Todos']
        ],
        pagingType: 'simple_numbers',
        autoWidth: false,
        deferRender: true,
        order: [],
        layout: {
            topStart: 'pageLength',
            topEnd: 'search',
            bottomStart: 'info',
            bottomEnd: 'paging'
        },
        language: language
    };

    function merge(base, extra) {
        var out = {};
        var key;
        for (key in base) {
            if (Object.prototype.hasOwnProperty.call(base, key)) {
                out[key] = base[key];
            }
        }
        if (extra) {
            for (key in extra) {
                if (Object.prototype.hasOwnProperty.call(extra, key)) {
                    out[key] = extra[key];
                }
            }
        }
        return out;
    }

    function optionsFromTable(table) {
        var opts = {};
        if (table.getAttribute('data-page-length')) {
            opts.pageLength = parseInt(table.getAttribute('data-page-length'), 10);
        }
        if (table.getAttribute('data-order')) {
            try {
                opts.order = JSON.parse(table.getAttribute('data-order'));
            } catch (e) {
                opts.order = [];
            }
        }
        if (table.getAttribute('data-paging') === 'full') {
            opts.pagingType = 'full_numbers';
        }
        return opts;
    }

    function init(target, options) {
        if (typeof DataTable === 'undefined') {
            return null;
        }
        var table = typeof target === 'string' ? document.querySelector(target) : target;
        if (!table) {
            return null;
        }
        if (DataTable.isDataTable && DataTable.isDataTable(table)) {
            return DataTable.Api ? new DataTable.Api(table) : null;
        }

        var cfg = merge(defaults, optionsFromTable(table));
        cfg = merge(cfg, options || {});
        cfg.language = merge(language, (options && options.language) || {});

        return new DataTable(table, cfg);
    }

    window.MonteblancoTable = {
        language: language,
        defaults: defaults,
        init: init
    };

    if (typeof DataTable !== 'undefined') {
        DataTable.defaults.language = language;
        DataTable.defaults.pageLength = defaults.pageLength;
        DataTable.defaults.lengthMenu = defaults.lengthMenu;
        DataTable.defaults.pagingType = defaults.pagingType;
        DataTable.defaults.autoWidth = false;
        DataTable.defaults.deferRender = true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var tables = document.querySelectorAll('table.js-datatable');
        for (var i = 0; i < tables.length; i++) {
            init(tables[i]);
        }
    });
})(window, document);
