using System.Windows;
using System.Windows.Threading;
using PluginBiometrico.App.Ventanas;
using PluginBiometrico.Core.Interfaces;

namespace PluginBiometrico.App.Presentadores;

/// <summary>
/// Muestra VentanaEstadoCaptura en el hilo de UI de WPF.
/// </summary>
public sealed class PresentadorCapturaVentana : IPresentadorCaptura
{
    private readonly Dispatcher _dispatcher;
    private VentanaEstadoCaptura? _ventana;

    public PresentadorCapturaVentana(Dispatcher dispatcher)
    {
        _dispatcher = dispatcher;
    }

    public Task AbrirAsync(string tituloVentana = "Sensor en modo captura.")
    {
        return _dispatcher.InvokeAsync(() =>
        {
            _ventana ??= new VentanaEstadoCaptura();
            _ventana.EstablecerTitulo(tituloVentana);
            _ventana.Show();
            _ventana.Activate();
        }).Task;
    }

    public void Actualizar(string mensaje, string estadoPlantilla)
    {
        _dispatcher.BeginInvoke(() =>
        {
            if (_ventana is null)
            {
                return;
            }

            _ventana.AgregarMensaje(mensaje);
            _ventana.ActualizarEstado(estadoPlantilla);
        });
    }

    public Task CerrarAsync()
    {
        return _dispatcher.InvokeAsync(() =>
        {
            if (_ventana is null)
            {
                return;
            }

            _ventana.Hide();
        }).Task;
    }
}
