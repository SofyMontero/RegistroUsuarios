using System.Windows;

namespace PluginBiometrico.App.Ventanas;

/// <summary>
/// Ventana pequeña de estado durante la captura. Reemplaza CapturarHuella.java (UI).
/// </summary>
public partial class VentanaEstadoCaptura : Window
{
    private const int AlturaBarraTareas = 40;

    public VentanaEstadoCaptura()
    {
        InitializeComponent();
        PosicionarEsquinaInferiorDerecha();
    }

    public void EstablecerTitulo(string titulo)
    {
        TxtTitulo.Text = titulo;
        Title = titulo;
    }

    public void AgregarMensaje(string mensaje)
    {
        if (string.IsNullOrWhiteSpace(mensaje))
        {
            return;
        }

        if (TxtMensajes.LineCount > 3)
        {
            TxtMensajes.Clear();
        }

        TxtMensajes.AppendText(mensaje + Environment.NewLine);
    }

    public void ActualizarEstado(string estadoPlantilla)
    {
        TxtEstado.Text = string.IsNullOrWhiteSpace(estadoPlantilla)
            ? "Esperando..."
            : estadoPlantilla;
    }

    private void PosicionarEsquinaInferiorDerecha()
    {
        var areaTrabajo = SystemParameters.WorkArea;
        Left = areaTrabajo.Right - Width - 4;
        Top = areaTrabajo.Bottom - Height - AlturaBarraTareas;
    }

    private void BtnCerrar_Click(object sender, RoutedEventArgs e) => Hide();
}
