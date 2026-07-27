/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package Servicios;

import Logging.RegistroArchivo;
import java.util.Timer;
import java.util.TimerTask;
import java.util.concurrent.atomic.AtomicInteger;

/**
 * Vigilancia de sensor "atascado", compartida por CapturarHuella y
 * LecturaHuella: si no se detecta una muestra en 25s, reinicia la sesión de
 * captura; si eso ocurre 3 veces seguidas sin ninguna muestra exitosa de por
 * medio, sube a nivel de error mientras sigue reintentando (nunca aborta),
 * replicando la heurística de recuperación de hardware del plugin .NET
 * (ServicioCaptura/ServicioVerificacion).
 *
 * @author Mauricio Herrera
 */
public class VigilanteSensor {

    private static final long VENTANA_SIN_RESPUESTA_MS = 25_000L;
    private static final int INTENTOS_ANTES_DE_ESCALAR = 3;

    private final String nombre;
    private final Runnable alReiniciar;
    private final Timer timer = new Timer(true);
    private final AtomicInteger intentosReinicio = new AtomicInteger(0);
    private volatile long ultimaActividad;
    private TimerTask tareaActual;

    public VigilanteSensor(String nombre, Runnable alReiniciar) {
        this.nombre = nombre;
        this.alReiniciar = alReiniciar;
    }

    public void marcarActividad() {
        ultimaActividad = System.currentTimeMillis();
        intentosReinicio.set(0);
    }

    public synchronized void iniciar() {
        detener();
        ultimaActividad = System.currentTimeMillis();
        tareaActual = new TimerTask() {
            @Override
            public void run() {
                if (System.currentTimeMillis() - ultimaActividad < VENTANA_SIN_RESPUESTA_MS) {
                    return;
                }
                int intentos = intentosReinicio.incrementAndGet();
                if (intentos <= INTENTOS_ANTES_DE_ESCALAR) {
                    RegistroArchivo.warn(nombre + ": sin respuesta del sensor en 25s, reiniciando sesión ("
                            + intentos + "/" + INTENTOS_ANTES_DE_ESCALAR + ")");
                } else {
                    RegistroArchivo.error(nombre + ": el sensor lleva " + intentos
                            + " reinicios sin responder, posible falla de hardware; se sigue reintentando");
                }
                ultimaActividad = System.currentTimeMillis();
                alReiniciar.run();
            }
        };
        timer.scheduleAtFixedRate(tareaActual, VENTANA_SIN_RESPUESTA_MS, VENTANA_SIN_RESPUESTA_MS);
    }

    public synchronized void detener() {
        if (tareaActual != null) {
            tareaActual.cancel();
            tareaActual = null;
        }
    }
}
