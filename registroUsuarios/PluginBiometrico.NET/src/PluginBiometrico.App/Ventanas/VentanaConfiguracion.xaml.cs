using System.IO;
using System.Reflection;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using PluginBiometrico.App.Ayudantes;
using PluginBiometrico.Core.Interfaces;
using PluginBiometrico.Core.Modelos;

namespace PluginBiometrico.App.Ventanas;

/// <summary>
/// Ventana de configuración del plugin. Reemplaza ConfigForm.java del plugin Java.
/// </summary>
public partial class VentanaConfiguracion : Window
{
    private static readonly SolidColorBrush BordeError = new(System.Windows.Media.Color.FromRgb(0xEE, 0x13, 0x13));
    private static readonly SolidColorBrush BordeNormal = new(System.Windows.Media.Color.FromRgb(0xD1, 0xD5, 0xDB));

    private readonly IAlmacenConfiguracion _almacen;
    private readonly bool _permitirCancelar;
    private bool _tokenVisible;

    public VentanaConfiguracion(IAlmacenConfiguracion almacen, bool permitirCancelar = true)
    {
        InitializeComponent();
        _almacen = almacen;
        _permitirCancelar = permitirCancelar;

        TxtVersion.Text = $"Versión {ObtenerVersion()}";
        TxtRutaConfig.Text = _almacen.ObtenerRutaArchivo();
        CargarIcono();
        CargarValoresActuales();
    }

    private void CargarIcono()
    {
        var ruta = Path.Combine(AppContext.BaseDirectory, "Recursos", "tryicon.png");
        if (!File.Exists(ruta))
        {
            return;
        }

        var imagen = new BitmapImage(new Uri(ruta, UriKind.Absolute));
        Icon = imagen;
        ImgLogo.Source = imagen;
    }

    private static string ObtenerVersion()
    {
        var version = Assembly.GetExecutingAssembly().GetName().Version;
        return version is null ? "1.0.0" : $"{version.Major}.{version.Minor}.{version.Build}";
    }

    private void CargarValoresActuales()
    {
        var config = _almacen.Cargar();
        if (config is not null)
        {
            TxtUrlHabilitarSensor.Text = config.UrlHabilitarSensor;
            TxtUrlApiRest.Text = config.UrlApiRest;
            EstablecerToken(config.IdUnicoPc);
            SeleccionarNavegador(config.Navegador);
            ChkWebSocket.IsChecked = config.HabilitarWebSocketLocal;
            ChkModoRapido.IsChecked = config.ModoComunicacionRapida;
            TxtPuertoWebSocket.Text = config.PuertoWebSocketLocal.ToString();
            return;
        }

        var (urlBase, urlSensor, urlApi, navegador) = CargadorValoresPorDefecto.Cargar();
        TxtUrlBase.Text = urlBase;
        TxtUrlHabilitarSensor.Text = urlSensor;
        TxtUrlApiRest.Text = urlApi;
        SeleccionarNavegador(navegador);

        if (string.IsNullOrWhiteSpace(ObtenerToken()))
        {
            EstablecerToken(GeneradorIdEstacion.Generar());
        }
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

    private void BtnAutocompletarUrls_Click(object sender, RoutedEventArgs e)
    {
        OcultarMensajes();

        var urlBase = TxtUrlBase.Text.Trim();
        if (string.IsNullOrWhiteSpace(urlBase))
        {
            MostrarError("Indique la URL base del servidor para autocompletar.");
            ResaltarCampo(TxtUrlBase, true);
            return;
        }

        if (!ConstructorUrlsServidor.EsUrlValida(urlBase))
        {
            MostrarError("La URL base no es válida.");
            ResaltarCampo(TxtUrlBase, true);
            return;
        }

        var (urlSensor, urlApi) = ConstructorUrlsServidor.DesdeUrlBase(urlBase);
        TxtUrlHabilitarSensor.Text = urlSensor;
        TxtUrlApiRest.Text = urlApi;
        ResaltarCampo(TxtUrlBase, false);
        MostrarExito("URLs generadas a partir de la URL base.");
    }

    private void BtnGenerarId_Click(object sender, RoutedEventArgs e)
    {
        OcultarMensajes();
        EstablecerToken(GeneradorIdEstacion.Generar());
        MostrarExito("Se generó un nuevo ID para esta estación.");
    }

    private void BtnMostrarToken_Click(object sender, RoutedEventArgs e)
    {
        _tokenVisible = !_tokenVisible;

        if (_tokenVisible)
        {
            TxtIdUnicoPcVisible.Text = TxtIdUnicoPc.Password;
            TxtIdUnicoPcVisible.Visibility = Visibility.Visible;
            TxtIdUnicoPc.Visibility = Visibility.Collapsed;
            BtnMostrarToken.Content = "Ocultar";
        }
        else
        {
            TxtIdUnicoPc.Password = TxtIdUnicoPcVisible.Text;
            TxtIdUnicoPc.Visibility = Visibility.Visible;
            TxtIdUnicoPcVisible.Visibility = Visibility.Collapsed;
            BtnMostrarToken.Content = "Mostrar";
        }
    }

    private async void BtnProbarConexion_Click(object sender, RoutedEventArgs e)
    {
        OcultarMensajes();

        var config = ConstruirConfiguracionDesdeFormulario();
        if (config is null)
        {
            return;
        }

        BtnProbarConexion.IsEnabled = false;
        BtnGuardar.IsEnabled = false;

        try
        {
            var (exito, mensaje) = await ProbadorConexionServidor.ProbarAsync(config);
            if (exito)
            {
                MostrarExito(mensaje);
            }
            else
            {
                MostrarError(mensaje);
            }
        }
        finally
        {
            BtnProbarConexion.IsEnabled = true;
            BtnGuardar.IsEnabled = true;
        }
    }

    private void BtnGuardar_Click(object sender, RoutedEventArgs e)
    {
        OcultarMensajes();

        var config = ConstruirConfiguracionDesdeFormulario();
        if (config is null)
        {
            return;
        }

        try
        {
            _almacen.Guardar(config);
            DialogResult = true;
            Close();
        }
        catch (Exception ex)
        {
            MostrarError(ex.Message);
        }
    }

    private ConfiguracionLocal? ConstruirConfiguracionDesdeFormulario()
    {
        LimpiarResaltados();

        var urlSensor = TxtUrlHabilitarSensor.Text.Trim();
        var urlApi = TxtUrlApiRest.Text.Trim();
        var idPc = ObtenerToken().Trim();
        var navegador = (CboNavegador.SelectedItem as ComboBoxItem)?.Content?.ToString() ?? "Chrome";
        var errores = 0;

        if (string.IsNullOrWhiteSpace(urlSensor) || !ConstructorUrlsServidor.EsUrlValida(urlSensor))
        {
            ResaltarCampo(TxtUrlHabilitarSensor, true);
            errores++;
        }

        if (string.IsNullOrWhiteSpace(urlApi) || !ConstructorUrlsServidor.EsUrlValida(urlApi))
        {
            ResaltarCampo(TxtUrlApiRest, true);
            errores++;
        }

        if (string.IsNullOrWhiteSpace(idPc))
        {
            ResaltarCampo(TxtIdUnicoPc, true);
            ResaltarCampo(TxtIdUnicoPcVisible, true);
            errores++;
        }

        if (!int.TryParse(TxtPuertoWebSocket.Text.Trim(), out var puerto) || puerto is < 1024 or > 65535)
        {
            ResaltarCampo(TxtPuertoWebSocket, true);
            errores++;
        }

        if (errores > 0)
        {
            MostrarError("Complete los campos obligatorios marcados en rojo.");
            return null;
        }

        var configAnterior = _almacen.Cargar();

        return new ConfiguracionLocal
        {
            IdUnicoPc = idPc,
            UrlHabilitarSensor = urlSensor,
            UrlApiRest = urlApi,
            Navegador = navegador,
            AutoInicioConfigurado = configAnterior?.AutoInicioConfigurado ?? false,
            PuertoWebSocketLocal = puerto,
            HabilitarWebSocketLocal = ChkWebSocket.IsChecked == true,
            ModoComunicacionRapida = ChkModoRapido.IsChecked == true
        };
    }

    private void BtnCancelar_Click(object sender, RoutedEventArgs e)
    {
        if (!_permitirCancelar)
        {
            MostrarError("Debe guardar la configuración para continuar.");
            return;
        }

        DialogResult = false;
        Close();
    }

    private string ObtenerToken()
    {
        return _tokenVisible ? TxtIdUnicoPcVisible.Text : TxtIdUnicoPc.Password;
    }

    private void EstablecerToken(string valor)
    {
        TxtIdUnicoPc.Password = valor;
        TxtIdUnicoPcVisible.Text = valor;
    }

    private void MostrarError(string mensaje)
    {
        TxtMensaje.Text = mensaje;
        PanelMensaje.Visibility = Visibility.Visible;
        PanelExito.Visibility = Visibility.Collapsed;
    }

    private void MostrarExito(string mensaje)
    {
        TxtExito.Text = mensaje;
        PanelExito.Visibility = Visibility.Visible;
        PanelMensaje.Visibility = Visibility.Collapsed;
    }

    private void OcultarMensajes()
    {
        PanelMensaje.Visibility = Visibility.Collapsed;
        PanelExito.Visibility = Visibility.Collapsed;
        TxtMensaje.Text = string.Empty;
        TxtExito.Text = string.Empty;
    }

    private static void ResaltarCampo(System.Windows.Controls.Control control, bool error)
    {
        control.BorderBrush = error ? BordeError : BordeNormal;
        control.BorderThickness = new Thickness(1);
    }

    private void LimpiarResaltados()
    {
        ResaltarCampo(TxtUrlHabilitarSensor, false);
        ResaltarCampo(TxtUrlApiRest, false);
        ResaltarCampo(TxtIdUnicoPc, false);
        ResaltarCampo(TxtIdUnicoPcVisible, false);
        ResaltarCampo(TxtPuertoWebSocket, false);
        ResaltarCampo(TxtUrlBase, false);
    }
}
