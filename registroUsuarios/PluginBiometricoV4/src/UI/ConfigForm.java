/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package UI;

import Ayudantes.ConstructorUrlsServidor;
import Ayudantes.GeneradorIdEstacion;
import Ayudantes.ProbadorConexionServidor;
import Config.AlmacenConfiguracionJson;
import Config.ConfiguracionLocal;
import com.jtattoo.plaf.acryl.AcrylLookAndFeel;
import java.awt.BorderLayout;
import java.awt.CardLayout;
import java.awt.Color;
import java.awt.Component;
import java.awt.Cursor;
import java.awt.Dimension;
import java.awt.Font;
import java.awt.Image;
import java.util.Properties;
import java.util.Timer;
import java.util.TimerTask;
import javax.swing.BorderFactory;
import javax.swing.Box;
import javax.swing.BoxLayout;
import javax.swing.ImageIcon;
import javax.swing.JButton;
import javax.swing.JCheckBox;
import javax.swing.JComboBox;
import javax.swing.JComponent;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JPasswordField;
import javax.swing.JScrollPane;
import javax.swing.JTextArea;
import javax.swing.JTextField;
import javax.swing.SwingUtilities;
import javax.swing.SwingWorker;
import javax.swing.UIManager;
import javax.swing.UnsupportedLookAndFeelException;
import javax.swing.border.EmptyBorder;
import javax.swing.border.MatteBorder;

/**
 * Ventana de configuración del plugin, con el mismo diseño (panel lateral
 * oscuro + formulario claro) y las mismas ayudas de autocompletado
 * (URL base -&gt; URLs completas, generar ID de estación, probar conexión)
 * que VentanaConfiguracion del plugin PluginBiometrico.NET.
 *
 * @author Mauricio Herrera
 */
public class ConfigForm extends javax.swing.JFrame {

    private static final Color COLOR_SIDEBAR = new Color(0x22, 0x28, 0x32);
    private static final Color COLOR_FORM_BG = new Color(0xF4, 0xF6, 0xF8);
    private static final Color COLOR_LABEL = new Color(0x37, 0x41, 0x51);
    private static final Color COLOR_BORDE_NORMAL = new Color(0xD1, 0xD5, 0xDB);
    private static final Color COLOR_BORDE_ERROR = new Color(0xEE, 0x13, 0x13);
    private static final Color COLOR_TEXTO_CLARO = new Color(0xD1, 0xD5, 0xDB);
    private static final Color COLOR_TEXTO_TENUE = new Color(0x9C, 0xA3, 0xAF);
    private static final Color COLOR_TEXTO_MUY_TENUE = new Color(0x6B, 0x72, 0x80);
    private static final Color COLOR_TEXTO_RUTA = new Color(0xE5, 0xE7, 0xEB);
    private static final Color COLOR_TITULO_FORM = new Color(0x11, 0x18, 0x27);
    private static final Color COLOR_ERROR_BG = new Color(0xFE, 0xF2, 0xF2);
    private static final Color COLOR_ERROR_BORDE = new Color(0xFE, 0xCA, 0xCA);
    private static final Color COLOR_ERROR_TEXTO = new Color(0xB9, 0x1C, 0x1C);
    private static final Color COLOR_EXITO_BG = new Color(0xEC, 0xFD, 0xF5);
    private static final Color COLOR_EXITO_BORDE = new Color(0xA7, 0xF3, 0xD0);
    private static final Color COLOR_EXITO_TEXTO = new Color(0x04, 0x78, 0x57);

    private static final int ANCHO_CAMPO_COMPLETO = 440;

    // Se mantienen públicos porque UI.TrayClass ya los usa para precargar valores.
    public JTextField txtHblSensor;
    public JTextField txtRestApi;
    public JComboBox<String> cboBrowser;

    private JTextField txtUrlBase;
    private JPasswordField txtToken;
    private JTextField txtTokenVisible;
    private JButton btnMostrarToken;
    private JCheckBox chkWebSocket;
    private JTextField txtPuertoWebSocket;
    private JCheckBox chkModoRapido;
    private JPanel panelMensajeError;
    private JTextArea lblMensajeError;
    private JPanel panelMensajeExito;
    private JTextArea lblMensajeExito;
    private JButton btnProbarConexion;
    private JButton btnGuardar;

    private boolean tokenVisible;

    public ConfigForm() {
        setTitle("Configuración | Plugin Biométrico");
        setDefaultCloseOperation(javax.swing.WindowConstants.EXIT_ON_CLOSE);
        setResizable(false);
        ImageIcon icono = new ImageIcon(getClass().getResource("/Imagenes/tryicon.png"));
        setIconImage(icono.getImage());

        setLayout(new BorderLayout());
        add(construirPanelLateral(icono), BorderLayout.WEST);
        add(construirPanelFormulario(), BorderLayout.CENTER);

        setSize(840, 660);
        cargarValoresActuales();
    }

    // ---- panel lateral ----

    private JPanel construirPanelLateral(ImageIcon icono) {
        JPanel panel = new JPanel();
        panel.setBackground(COLOR_SIDEBAR);
        panel.setPreferredSize(new Dimension(260, 640));
        panel.setLayout(new BoxLayout(panel, BoxLayout.Y_AXIS));
        panel.setBorder(new EmptyBorder(32, 28, 32, 28));

        JLabel logo = new JLabel(new ImageIcon(icono.getImage().getScaledInstance(56, 56, Image.SCALE_SMOOTH)));
        logo.setAlignmentX(Component.LEFT_ALIGNMENT);
        panel.add(logo);
        panel.add(Box.createVerticalStrut(16));

        panel.add(etiqueta("Plugin Biométrico", Color.WHITE, Font.BOLD, 16f));
        panel.add(Box.createVerticalStrut(6));
        panel.add(etiqueta("Versión 4.0", COLOR_TEXTO_TENUE, Font.PLAIN, 12f));
        panel.add(Box.createVerticalStrut(20));

        panel.add(etiquetaMultilinea(
                "Configure esta estación para conectar el lector Digital Persona con la aplicación web de registro.",
                COLOR_TEXTO_CLARO, 12f, 195));

        panel.add(Box.createVerticalStrut(24));
        JPanel separador = new JPanel();
        separador.setBackground(new Color(0x37, 0x41, 0x51));
        separador.setAlignmentX(Component.LEFT_ALIGNMENT);
        separador.setMaximumSize(new Dimension(204, 1));
        separador.setPreferredSize(new Dimension(204, 1));
        panel.add(separador);
        panel.add(Box.createVerticalStrut(16));

        panel.add(etiqueta("Archivo de configuración (en esta PC)", COLOR_TEXTO_TENUE, Font.BOLD, 11f));
        panel.add(Box.createVerticalStrut(4));
        panel.add(etiquetaMultilinea(
                AlmacenConfiguracionJson.archivoConfiguracion().getAbsolutePath(),
                COLOR_TEXTO_RUTA, 10f, 195));

        panel.add(Box.createVerticalStrut(6));
        panel.add(etiquetaMultilinea(
                "Las URLs apuntan al servidor web (producción). Este archivo solo guarda los datos en su equipo.",
                COLOR_TEXTO_MUY_TENUE, 9f, 195));

        panel.add(Box.createVerticalGlue());
        return panel;
    }

    // ---- panel de formulario ----

    private JScrollPane construirPanelFormulario() {
        JPanel contenido = new JPanel();
        contenido.setLayout(new BoxLayout(contenido, BoxLayout.Y_AXIS));
        contenido.setBackground(COLOR_FORM_BG);
        contenido.setBorder(new EmptyBorder(24, 28, 16, 28));

        agregarAlineadoIzquierda(contenido, etiqueta("Datos de conexión", COLOR_TITULO_FORM, Font.BOLD, 18f));
        contenido.add(Box.createVerticalStrut(4));
        agregarAlineadoIzquierda(contenido, etiquetaMultilinea(
                "Complete los campos obligatorios. Puede usar la URL base del servidor para autocompletar las rutas.",
                COLOR_TEXTO_MUY_TENUE, 12f, ANCHO_CAMPO_COMPLETO));
        contenido.add(Box.createVerticalStrut(20));

        agregarAlineadoIzquierda(contenido, etiquetaCampo("URL base del servidor (opcional)"));
        txtUrlBase = campoTexto(300);
        JButton btnAutocompletar = botonSecundario("Autocompletar");
        btnAutocompletar.addActionListener(e -> autocompletarUrls());
        agregarAlineadoIzquierda(contenido, filaConBoton(txtUrlBase, btnAutocompletar));
        contenido.add(Box.createVerticalStrut(14));

        agregarAlineadoIzquierda(contenido, etiquetaCampo("URL habilitar sensor *"));
        txtHblSensor = campoTexto();
        agregarAlineadoIzquierda(contenido, txtHblSensor);
        contenido.add(Box.createVerticalStrut(14));

        agregarAlineadoIzquierda(contenido, etiquetaCampo("URL API REST de huellas *"));
        txtRestApi = campoTexto();
        agregarAlineadoIzquierda(contenido, txtRestApi);
        contenido.add(Box.createVerticalStrut(14));

        agregarAlineadoIzquierda(contenido, etiquetaCampo("ID único de esta PC (token) *"));
        agregarAlineadoIzquierda(contenido, filaToken());
        contenido.add(Box.createVerticalStrut(14));

        agregarAlineadoIzquierda(contenido, etiquetaCampo("Navegador de referencia"));
        cboBrowser = new JComboBox<>(new String[]{"Seleccione", "Chrome", "Mozilla", "Edge", "Explorer"});
        cboBrowser.setSelectedIndex(1);
        cboBrowser.setMaximumSize(new Dimension(200, 30));
        cboBrowser.setPreferredSize(new Dimension(200, 30));
        agregarAlineadoIzquierda(contenido, cboBrowser);
        contenido.add(Box.createVerticalStrut(20));

        agregarAlineadoIzquierda(contenido, construirPanelOpcionesAvanzadas());
        contenido.add(Box.createVerticalStrut(16));

        panelMensajeError = panelMensaje(COLOR_ERROR_BG, COLOR_ERROR_BORDE);
        lblMensajeError = areaMensaje(COLOR_ERROR_TEXTO);
        panelMensajeError.add(lblMensajeError);
        panelMensajeError.setVisible(false);
        agregarAlineadoIzquierda(contenido, panelMensajeError);
        contenido.add(Box.createVerticalStrut(8));

        panelMensajeExito = panelMensaje(COLOR_EXITO_BG, COLOR_EXITO_BORDE);
        lblMensajeExito = areaMensaje(COLOR_EXITO_TEXTO);
        panelMensajeExito.add(lblMensajeExito);
        panelMensajeExito.setVisible(false);
        agregarAlineadoIzquierda(contenido, panelMensajeExito);
        contenido.add(Box.createVerticalStrut(8));

        agregarAlineadoIzquierda(contenido, construirFilaBotones());
        contenido.add(Box.createVerticalGlue());

        JScrollPane scroll = new JScrollPane(contenido);
        scroll.setBorder(BorderFactory.createEmptyBorder());
        scroll.getVerticalScrollBar().setUnitIncrement(16);
        scroll.getViewport().setBackground(COLOR_FORM_BG);
        return scroll;
    }

    private static void agregarAlineadoIzquierda(JPanel contenedor, JComponent componente) {
        componente.setAlignmentX(Component.LEFT_ALIGNMENT);
        contenedor.add(componente);
    }

    private JPanel filaConBoton(JTextField campo, JButton boton) {
        JPanel fila = new JPanel();
        fila.setOpaque(false);
        fila.setLayout(new BoxLayout(fila, BoxLayout.X_AXIS));
        fila.setAlignmentX(Component.LEFT_ALIGNMENT);
        campo.setAlignmentX(Component.LEFT_ALIGNMENT);
        fila.add(campo);
        fila.add(Box.createHorizontalStrut(8));
        fila.add(boton);
        return fila;
    }

    private JPanel filaToken() {
        txtToken = new JPasswordField();
        estilizarCampo(txtToken);
        txtToken.setPreferredSize(new Dimension(240, 30));
        txtTokenVisible = campoTexto(240);

        JPanel campoApilado = new JPanel(new CardLayout());
        campoApilado.setOpaque(false);
        campoApilado.add(txtToken, "oculto");
        campoApilado.add(txtTokenVisible, "visible");
        campoApilado.setMaximumSize(new Dimension(240, 30));
        campoApilado.setPreferredSize(new Dimension(240, 30));

        JButton btnGenerar = botonSecundario("Generar");
        btnGenerar.addActionListener(e -> {
            establecerToken(GeneradorIdEstacion.generar());
            mostrarExito("Se generó un nuevo ID para esta estación.");
        });
        btnMostrarToken = botonSecundario("Mostrar");
        btnMostrarToken.addActionListener(e -> alternarVisibilidadToken(campoApilado));

        JPanel fila = new JPanel();
        fila.setOpaque(false);
        fila.setLayout(new BoxLayout(fila, BoxLayout.X_AXIS));
        fila.setAlignmentX(Component.LEFT_ALIGNMENT);
        fila.add(campoApilado);
        fila.add(Box.createHorizontalStrut(8));
        fila.add(btnGenerar);
        fila.add(Box.createHorizontalStrut(8));
        fila.add(btnMostrarToken);
        return fila;
    }

    private JPanel construirPanelOpcionesAvanzadas() {
        JPanel panel = new JPanel();
        panel.setLayout(new BoxLayout(panel, BoxLayout.Y_AXIS));
        panel.setBackground(Color.WHITE);
        panel.setBorder(BorderFactory.createCompoundBorder(
                new MatteBorder(1, 1, 1, 1, new Color(0xE5, 0xE7, 0xEB)),
                new EmptyBorder(10, 12, 10, 12)));
        panel.setMaximumSize(new Dimension(ANCHO_CAMPO_COMPLETO, Short.MAX_VALUE));

        agregarAlineadoIzquierda(panel, etiqueta("Opciones avanzadas", COLOR_LABEL, Font.BOLD, 12f));
        panel.add(Box.createVerticalStrut(10));

        chkWebSocket = new JCheckBox("Habilitar WebSocket local", true);
        chkWebSocket.setOpaque(false);
        agregarAlineadoIzquierda(panel, chkWebSocket);
        agregarAlineadoIzquierda(panel, etiquetaMultilinea(
                "Eventos instantáneos en la web, sin esperar el poll de 1 s.",
                COLOR_TEXTO_MUY_TENUE, 10.5f, 380));
        panel.add(Box.createVerticalStrut(10));

        JPanel filaPuerto = new JPanel();
        filaPuerto.setOpaque(false);
        filaPuerto.setLayout(new BoxLayout(filaPuerto, BoxLayout.X_AXIS));
        JLabel lblPuerto = new JLabel("Puerto WebSocket");
        lblPuerto.setForeground(COLOR_LABEL);
        filaPuerto.add(lblPuerto);
        filaPuerto.add(Box.createHorizontalStrut(12));
        txtPuertoWebSocket = campoTexto(90);
        txtPuertoWebSocket.setText("17890");
        filaPuerto.add(txtPuertoWebSocket);
        agregarAlineadoIzquierda(panel, filaPuerto);
        panel.add(Box.createVerticalStrut(10));

        chkModoRapido = new JCheckBox("Modo comunicación rápida", true);
        chkModoRapido.setOpaque(false);
        agregarAlineadoIzquierda(panel, chkModoRapido);
        agregarAlineadoIzquierda(panel, etiquetaMultilinea(
                "Sin espera de 1 s entre consultas cuando no hay nada que hacer.",
                COLOR_TEXTO_MUY_TENUE, 10.5f, 380));

        return panel;
    }

    private JPanel construirFilaBotones() {
        JPanel fila = new JPanel();
        fila.setOpaque(false);
        fila.setLayout(new BoxLayout(fila, BoxLayout.X_AXIS));
        fila.setMaximumSize(new Dimension(ANCHO_CAMPO_COMPLETO, 40));

        fila.add(Box.createHorizontalGlue());

        btnProbarConexion = botonSecundario("Probar conexión");
        btnProbarConexion.addActionListener(e -> probarConexion());
        fila.add(btnProbarConexion);
        fila.add(Box.createHorizontalStrut(8));

        JButton btnCancelar = botonSecundario("Cancelar");
        btnCancelar.addActionListener(e -> Helper.Utils.restartApplication());
        fila.add(btnCancelar);
        fila.add(Box.createHorizontalStrut(8));

        btnGuardar = botonPrimario("Guardar");
        btnGuardar.addActionListener(e -> guardarConfiguracion());
        fila.add(btnGuardar);

        return fila;
    }

    // ---- carga / guardado ----

    private void cargarValoresActuales() {
        ConfiguracionLocal cfg = AlmacenConfiguracionJson.cargar();
        if (cfg != null) {
            txtHblSensor.setText(cfg.getUrlHabilitarSensor());
            txtRestApi.setText(cfg.getUrlApiRest());
            establecerToken(cfg.getIdUnicoPc());
            seleccionarNavegador(cfg.getNavegador());
            chkWebSocket.setSelected(cfg.isHabilitarWebSocketLocal());
            chkModoRapido.setSelected(cfg.isModoComunicacionRapida());
            txtPuertoWebSocket.setText(String.valueOf(cfg.getPuertoWebSocketLocal()));
            return;
        }

        establecerToken(GeneradorIdEstacion.generar());
    }

    private void seleccionarNavegador(String navegador) {
        if (navegador == null) {
            return;
        }
        for (int i = 0; i < cboBrowser.getItemCount(); i++) {
            if (navegador.equals(cboBrowser.getItemAt(i))) {
                cboBrowser.setSelectedIndex(i);
                return;
            }
        }
    }

    private void autocompletarUrls() {
        ocultarMensajes();
        String urlBase = txtUrlBase.getText().trim();
        if (urlBase.isEmpty()) {
            mostrarError("Indique la URL base del servidor para autocompletar.");
            resaltarCampo(txtUrlBase, true);
            return;
        }
        if (!ConstructorUrlsServidor.esUrlValida(urlBase)) {
            mostrarError("La URL base no es válida.");
            resaltarCampo(txtUrlBase, true);
            return;
        }
        String[] urls = ConstructorUrlsServidor.desdeUrlBase(urlBase);
        txtHblSensor.setText(urls[0]);
        txtRestApi.setText(urls[1]);
        resaltarCampo(txtUrlBase, false);
        mostrarExito("URLs generadas a partir de la URL base.");
    }

    private void probarConexion() {
        ocultarMensajes();
        String urlSensor = txtHblSensor.getText().trim();
        String urlApi = txtRestApi.getText().trim();
        String token = obtenerToken().trim();

        btnProbarConexion.setEnabled(false);
        btnGuardar.setEnabled(false);

        new SwingWorker<ProbadorConexionServidor.Resultado, Void>() {
            @Override
            protected ProbadorConexionServidor.Resultado doInBackground() {
                return ProbadorConexionServidor.probar(urlSensor, urlApi, token);
            }

            @Override
            protected void done() {
                btnProbarConexion.setEnabled(true);
                btnGuardar.setEnabled(true);
                try {
                    ProbadorConexionServidor.Resultado resultado = get();
                    if (resultado.exito) {
                        mostrarExito(resultado.mensaje);
                    } else {
                        mostrarError(resultado.mensaje);
                    }
                } catch (Exception ex) {
                    mostrarError("Error probando la conexión: " + ex.getMessage());
                }
            }
        }.execute();
    }

    private void guardarConfiguracion() {
        ocultarMensajes();
        limpiarResaltados();

        String urlSensor = txtHblSensor.getText().trim();
        String urlApi = txtRestApi.getText().trim();
        String idPc = obtenerToken().trim();
        String navegador = (String) cboBrowser.getSelectedItem();
        int errores = 0;

        if (urlSensor.isEmpty() || !ConstructorUrlsServidor.esUrlValida(urlSensor)) {
            resaltarCampo(txtHblSensor, true);
            errores++;
        }
        if (urlApi.isEmpty() || !ConstructorUrlsServidor.esUrlValida(urlApi)) {
            resaltarCampo(txtRestApi, true);
            errores++;
        }
        if (idPc.isEmpty()) {
            resaltarCampo(tokenVisible ? txtTokenVisible : txtToken, true);
            errores++;
        }
        int puerto;
        try {
            puerto = Integer.parseInt(txtPuertoWebSocket.getText().trim());
            if (puerto < 1024 || puerto > 65535) {
                throw new NumberFormatException();
            }
        } catch (NumberFormatException ex) {
            puerto = 0;
            resaltarCampo(txtPuertoWebSocket, true);
            errores++;
        }

        if (errores > 0) {
            mostrarError("Complete los campos obligatorios marcados en rojo.");
            return;
        }
        if (navegador == null || "Seleccione".equals(navegador)) {
            mostrarError("Seleccione un navegador de referencia.");
            return;
        }

        ConfiguracionLocal cfgAnterior = AlmacenConfiguracionJson.cargar();
        ConfiguracionLocal cfg = ConfiguracionLocal.porDefecto();
        cfg.setIdUnicoPc(idPc);
        cfg.setUrlHabilitarSensor(urlSensor);
        cfg.setUrlApiRest(urlApi);
        cfg.setNavegador(navegador);
        cfg.setAutoInicioConfigurado(cfgAnterior != null && cfgAnterior.isAutoInicioConfigurado());
        cfg.setPuertoWebSocketLocal(puerto);
        cfg.setHabilitarWebSocketLocal(chkWebSocket.isSelected());
        cfg.setModoComunicacionRapida(chkModoRapido.isSelected());

        boolean guardado = AlmacenConfiguracionJson.guardar(cfg);
        if (guardado) {
            ds.desktop.notify.DesktopNotify.showDesktopMessage("Aviso..!",
                    "Configuración generada o actualizada correctamente."
                    + "\nLa aplicación se reiniciara para que la configuración tenga efecto",
                    ds.desktop.notify.DesktopNotify.INFORMATION, 6000L);
            Timer timer = new Timer();
            timer.schedule(new TimerTask() {
                @Override
                public void run() {
                    Helper.Utils.restartApplication();
                }
            }, 2500);
        } else {
            mostrarError("Ocurrió un error al guardar la configuración.");
        }
    }

    // ---- token show/hide ----

    private String obtenerToken() {
        return tokenVisible ? txtTokenVisible.getText() : new String(txtToken.getPassword());
    }

    private void establecerToken(String valor) {
        txtToken.setText(valor);
        txtTokenVisible.setText(valor);
    }

    private void alternarVisibilidadToken(JPanel campoApilado) {
        tokenVisible = !tokenVisible;
        CardLayout layout = (CardLayout) campoApilado.getLayout();
        if (tokenVisible) {
            txtTokenVisible.setText(new String(txtToken.getPassword()));
            layout.show(campoApilado, "visible");
            btnMostrarToken.setText("Ocultar");
        } else {
            txtToken.setText(txtTokenVisible.getText());
            layout.show(campoApilado, "oculto");
            btnMostrarToken.setText("Mostrar");
        }
    }

    // ---- mensajes / resaltado ----

    private static final int ANCHO_MENSAJE = 400;

    private void mostrarError(String mensaje) {
        establecerTextoAjustado(lblMensajeError, mensaje, ANCHO_MENSAJE);
        panelMensajeError.setVisible(true);
        panelMensajeExito.setVisible(false);
        revalidate();
        repaint();
    }

    private void mostrarExito(String mensaje) {
        establecerTextoAjustado(lblMensajeExito, mensaje, ANCHO_MENSAJE);
        panelMensajeExito.setVisible(true);
        panelMensajeError.setVisible(false);
        revalidate();
        repaint();
    }

    private static void establecerTextoAjustado(JTextArea area, String texto, int anchoPx) {
        area.setText(texto);
        area.setSize(anchoPx, Short.MAX_VALUE);
        Dimension tamano = new Dimension(anchoPx, area.getPreferredSize().height);
        area.setPreferredSize(tamano);
        area.setMaximumSize(tamano);
    }

    private void ocultarMensajes() {
        panelMensajeError.setVisible(false);
        panelMensajeExito.setVisible(false);
    }

    private static void resaltarCampo(JComponent campo, boolean error) {
        campo.setBorder(new MatteBorder(1, 1, 1, 1, error ? COLOR_BORDE_ERROR : COLOR_BORDE_NORMAL));
    }

    private void limpiarResaltados() {
        resaltarCampo(txtHblSensor, false);
        resaltarCampo(txtRestApi, false);
        resaltarCampo(txtToken, false);
        resaltarCampo(txtTokenVisible, false);
        resaltarCampo(txtPuertoWebSocket, false);
        resaltarCampo(txtUrlBase, false);
    }

    // ---- fábricas de componentes ----

    private static JLabel etiqueta(String texto, Color color, int estilo, float tamano) {
        JLabel etiqueta = new JLabel(texto);
        etiqueta.setForeground(color);
        etiqueta.setFont(etiqueta.getFont().deriveFont(estilo, tamano));
        return etiqueta;
    }

    private static JTextArea etiquetaMultilinea(String texto, Color color, float tamano, int anchoPx) {
        JTextArea area = new JTextArea(texto);
        area.setEditable(false);
        area.setFocusable(false);
        area.setOpaque(false);
        area.setLineWrap(true);
        area.setWrapStyleWord(true);
        area.setForeground(color);
        area.setFont(area.getFont().deriveFont(tamano));
        area.setBorder(null);
        area.setSize(anchoPx, Short.MAX_VALUE);
        Dimension tamanoFinal = new Dimension(anchoPx, area.getPreferredSize().height);
        area.setPreferredSize(tamanoFinal);
        area.setMaximumSize(tamanoFinal);
        area.setMinimumSize(tamanoFinal);
        return area;
    }

    private static JTextArea areaMensaje(Color color) {
        JTextArea area = new JTextArea();
        area.setEditable(false);
        area.setFocusable(false);
        area.setOpaque(false);
        area.setLineWrap(true);
        area.setWrapStyleWord(true);
        area.setForeground(color);
        area.setFont(area.getFont().deriveFont(12f));
        area.setBorder(null);
        return area;
    }

    private static JLabel etiquetaCampo(String texto) {
        return etiqueta(texto, COLOR_LABEL, Font.BOLD, 12f);
    }

    private static JTextField campoTexto() {
        return campoTexto(ANCHO_CAMPO_COMPLETO);
    }

    private static JTextField campoTexto(int ancho) {
        JTextField campo = new JTextField();
        campo.setPreferredSize(new Dimension(ancho, 30));
        campo.setMaximumSize(new Dimension(ancho, 30));
        estilizarCampo(campo);
        return campo;
    }

    private static void estilizarCampo(JComponent campo) {
        campo.setBackground(Color.WHITE);
        campo.setBorder(new MatteBorder(1, 1, 1, 1, COLOR_BORDE_NORMAL));
    }

    private static JButton botonSecundario(String texto) {
        JButton boton = new JButton(texto);
        boton.setBackground(Color.WHITE);
        boton.setForeground(COLOR_SIDEBAR);
        boton.setBorder(BorderFactory.createCompoundBorder(
                new MatteBorder(1, 1, 1, 1, COLOR_BORDE_NORMAL),
                new EmptyBorder(4, 14, 4, 14)));
        boton.setContentAreaFilled(false);
        boton.setOpaque(true);
        boton.setFocusPainted(false);
        boton.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        return boton;
    }

    private static JButton botonPrimario(String texto) {
        JButton boton = new JButton(texto);
        boton.setBackground(COLOR_SIDEBAR);
        boton.setForeground(Color.WHITE);
        boton.setFont(boton.getFont().deriveFont(Font.BOLD));
        boton.setBorder(BorderFactory.createEmptyBorder(6, 18, 6, 18));
        boton.setContentAreaFilled(false);
        boton.setOpaque(true);
        boton.setFocusPainted(false);
        boton.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        return boton;
    }

    private static JPanel panelMensaje(Color fondo, Color borde) {
        JPanel panel = new JPanel();
        panel.setBackground(fondo);
        panel.setBorder(BorderFactory.createCompoundBorder(
                new MatteBorder(1, 1, 1, 1, borde),
                new EmptyBorder(10, 12, 10, 12)));
        panel.setMaximumSize(new Dimension(ANCHO_CAMPO_COMPLETO, 200));
        return panel;
    }

    /**
     * @param args the command line arguments
     */
    public static void main(String args[]) {
        try {
            Properties props = new Properties();
            props.put("logoString", "M-Systems");
            AcrylLookAndFeel.setCurrentTheme(props);
            UIManager.setLookAndFeel("com.jtattoo.plaf.acryl.AcrylLookAndFeel");
        } catch (ClassNotFoundException | InstantiationException | IllegalAccessException | UnsupportedLookAndFeelException ex) {
            System.out.println("error confiig form");
        }

        SwingUtilities.invokeLater(() -> new ConfigForm().setVisible(true));
    }

}
