using System.Windows;
using System.Windows.Controls;
using PluginBiometrico.App.Ayudantes;
using PluginBiometrico.Core.Interfaces;
using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.App.Ventanas;

/// <summary>
/// Ventana inicial de configuración. Reemplaza ConfigForm.java del plugin Java.
/// </summary>
public partial class VentanaConfiguracion : Window
{
    private readonly IAlmacenConfiguracion _almacen;
    private readonly bool _permitirCancelar;

    public VentanaConfiguracion(IAlmacenConfiguracion almacen, bool permitirCancelar = true)
    {
        InitializeComponent();
        _almacen = almacen;
        _permitirCancelar = permitirCancelar;

        CargarValoresActuales();
    }

    private void CargarValoresActuales()
    {
        var config = _almacen.Cargar();
        if (config is not null)
        {
            TxtUrlHabilitarSensor.Text = config.UrlHabilitarSensor;
            TxtUrlApiRest.Text = config.UrlApiRest;
            TxtIdUnicoPc.Password = config.IdUnicoPc;
            SeleccionarNavegador(config.Navegador);
            return;
        }

        var (urlSensor, urlApi, navegador) = CargadorValoresPorDefecto.Cargar();
        TxtUrlHabilitarSensor.Text = urlSensor;
        TxtUrlApiRest.Text = urlApi;
        SeleccionarNavegador(navegador);
    }

    private void SeleccionarNavegador(string navegador)
    {
        foreach (var item in CboNavegador.Items)
        {
            if (item is ComboBoxItem combo && combo.Content?.ToString() == navegador)
            {
                CboNavegador.SelectedItem = item;
                return;
            }
        }
    }

    private void BtnGuardar_Click(object sender, RoutedEventArgs e)
    {
        OcultarMensaje();

        var urlSensor = TxtUrlHabilitarSensor.Text.Trim();
        var urlApi = TxtUrlApiRest.Text.Trim();
        var idPc = TxtIdUnicoPc.Password.Trim();
        var navegador = (CboNavegador.SelectedItem as ComboBoxItem)?.Content?.ToString() ?? "Chrome";

        if (string.IsNullOrWhiteSpace(urlSensor)
            || string.IsNullOrWhiteSpace(urlApi)
            || string.IsNullOrWhiteSpace(idPc))
        {
            MostrarMensaje("Complete todos los campos obligatorios.");
            return;
        }

        var configAnterior = _almacen.Cargar();

        var configuracion = new ConfiguracionLocal
        {
            IdUnicoPc = idPc,
            UrlHabilitarSensor = urlSensor,
            UrlApiRest = urlApi,
            Navegador = navegador,
            AutoInicioConfigurado = configAnterior?.AutoInicioConfigurado ?? false,
            PuertoWebSocketLocal = configAnterior?.PuertoWebSocketLocal ?? 17890,
            HabilitarWebSocketLocal = configAnterior?.HabilitarWebSocketLocal ?? true,
            ModoComunicacionRapida = configAnterior?.ModoComunicacionRapida ?? true
        };

        try
        {
            _almacen.Guardar(configuracion);
            DialogResult = true;
            Close();
        }
        catch (Exception ex)
        {
            MostrarMensaje(ex.Message);
        }
    }

    private void BtnCancelar_Click(object sender, RoutedEventArgs e)
    {
        if (!_permitirCancelar)
        {
            MostrarMensaje("Debe guardar la configuración para continuar.");
            return;
        }

        DialogResult = false;
        Close();
    }

    private void MostrarMensaje(string mensaje)
    {
        TxtMensaje.Text = mensaje;
        TxtMensaje.Visibility = Visibility.Visible;
    }

    private void OcultarMensaje()
    {
        TxtMensaje.Visibility = Visibility.Collapsed;
        TxtMensaje.Text = string.Empty;
    }
}
