import { useState } from 'react';
import { SampleFormat } from '@digitalpersona/devices';
import { useStationToken } from './hooks/useStationToken';
import { useFingerprint } from './hooks/useFingerprint';
import './App.css';

const API_BASE =
  import.meta.env.VITE_API_BASE ?? 'https://registrousuarios.edmaramericas.com';

export default function App() {
  const token = useStationToken();
  const {
    status,
    message,
    preview,
    templateData,
    deviceName,
    startCapture,
    stopCapture,
  } = useFingerprint();

  const [documento, setDocumento] = useState('');
  const [nombre, setNombre] = useState('');
  const [apiMsg, setApiMsg] = useState('');

  async function activarCapturaLegacy() {
    if (!token) return;
    setApiMsg('Activando sensor (API PHP legacy)...');
    try {
      const body = new URLSearchParams({ token });
      const res = await fetch(`${API_BASE}/Model/ActivarSensorAdd.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body,
      });
      const data = await res.json();
      setApiMsg(
        data.filas === 1
          ? 'Comando capturar enviado. Si usa plugin .NET, debe estar en publish/.'
          : `Respuesta: ${JSON.stringify(data)}`,
      );
    } catch (e) {
      setApiMsg(e instanceof Error ? e.message : 'Error de red');
    }
  }

  async function guardarEnrollment() {
    if (!documento.trim() || !nombre.trim()) {
      setApiMsg('Complete documento y nombre.');
      return;
    }
    if (!templateData) {
      setApiMsg('Capture una huella antes de guardar.');
      return;
    }

    const imgHuella = preview?.replace(/^data:image\/\w+;base64,/, '') ?? templateData;

    setApiMsg('Guardando en servidor...');
    try {
      const res = await fetch(`${API_BASE}/Model/EnrollWebSdk.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          documento: documento.trim(),
          nombre: nombre.trim(),
          huella: templateData,
          imgHuella,
        }),
      });
      const data = await res.json();
      setApiMsg(data.filas >= 1 ? data.message : `Error: ${data.message}`);
    } catch (e) {
      setApiMsg(e instanceof Error ? e.message : 'Error de red');
    }
  }

  return (
    <div className="shell">
      <header className="header">
        <span className="badge">PoC React + HID WebSDK</span>
        <h1>Captura de huella — U.are.U 4500</h1>
        <p className="subtitle">
          Vía nueva (navegador + Lite Client). En paralelo sigue disponible el plugin .NET.
        </p>
      </header>

      <div className="grid">
        <section className="card">
          <h2>Estación</h2>
          <label className="field">
            Token (copiar al plugin .NET)
            <input readOnly value={token} />
          </label>
          <p className="hint">
            El mismo token debe estar en Configurar → ID único PC del plugin.
          </p>
        </section>

        <section className="card">
          <h2>Colaborador</h2>
          <label className="field">
            Documento
            <input value={documento} onChange={(e) => setDocumento(e.target.value)} />
          </label>
          <label className="field">
            Nombre
            <input value={nombre} onChange={(e) => setNombre(e.target.value)} />
          </label>
        </section>

        <section className="card scanner">
          <h2>Lector HID WebSDK</h2>
          <p className={`status status-${status}`}>{message}</p>
          {deviceName && <p className="device">Dispositivo: {deviceName}</p>}

          <div className="actions">
            <button
              type="button"
              className="btn primary"
              onClick={() => startCapture(SampleFormat.Intermediate)}
            >
              Capturar huella (WebSDK)
            </button>
            <button
              type="button"
              className="btn"
              onClick={() => startCapture(SampleFormat.PngImage)}
            >
              Vista previa PNG
            </button>
            <button type="button" className="btn" onClick={stopCapture}>
              Detener
            </button>
            <button
              type="button"
              className="btn accent"
              onClick={guardarEnrollment}
              disabled={!templateData}
            >
              Guardar usuario (API B2)
            </button>
            <button type="button" className="btn secondary" onClick={activarCapturaLegacy}>
              Activar plugin .NET (legacy)
            </button>
          </div>

          <div className="preview">
            {preview ? (
              <img src={preview} alt="Huella capturada" />
            ) : (
              <div className="preview-placeholder">Vista previa de huella</div>
            )}
          </div>
        </section>

        {apiMsg && (
          <section className="card api-msg">
            <p>{apiMsg}</p>
          </section>
        )}
      </div>

      <footer className="footer">
        <strong>Requisitos:</strong> Windows 10+, Chrome/Edge, lector U.are.U 4500 (no WBF),
        HID Lite Client, archivo WebSdk en <code>public/websdk/</code>.
      </footer>
    </div>
  );
}
