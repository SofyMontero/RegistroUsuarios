import { useCallback, useEffect, useRef, useState } from 'react';
import {
  FingerprintReader,
  SampleFormat,
  SamplesAcquired,
  DeviceConnected,
  DeviceDisconnected,
  CommunicationFailed,
} from '@digitalpersona/devices';

export type FingerprintStatus =
  | 'idle'
  | 'need-websdk'
  | 'need-lite-client'
  | 'ready'
  | 'scanning'
  | 'sample'
  | 'error';

type WebSdkGlobal = {
  __isStub?: boolean;
  WebChannelClient?: unknown;
};

function getWebSdk(): WebSdkGlobal | undefined {
  return typeof WebSdk === 'undefined' ? undefined : (WebSdk as WebSdkGlobal);
}

/** True solo con el JS real del Lite Client (no el stub del repo). */
function hasRealWebSdk(): boolean {
  const sdk = getWebSdk();
  if (!sdk || sdk.__isStub) return false;
  return typeof sdk.WebChannelClient === 'function';
}

function base64UrlToDataUrl(data: string, mime = 'image/png'): string {
  const b64 = data.replace(/-/g, '+').replace(/_/g, '/');
  return `data:${mime};base64,${b64}`;
}

/** Convierte muestra PNG del WebSDK a data URL para `<img>`. */
function sampleToImageSrc(event: SamplesAcquired): string | null {
  try {
    const first = event.samples[0];
    if (!first?.Data) return null;
    return base64UrlToDataUrl(first.Data);
  } catch {
    return null;
  }
}

/** Serializa muestras Intermediate para enviar a la API PHP (B2). */
function samplesToTemplatePayload(event: SamplesAcquired): string {
  const parts = event.samples.map((s) => s.Data).filter(Boolean);
  return JSON.stringify(parts);
}

export function useFingerprint() {
  const readerRef = useRef<FingerprintReader | null>(null);
  const [status, setStatus] = useState<FingerprintStatus>('idle');
  const [message, setMessage] = useState('Inicializando lector...');
  const [preview, setPreview] = useState<string | null>(null);
  const [templateData, setTemplateData] = useState<string | null>(null);
  const [deviceName, setDeviceName] = useState<string | null>(null);

  useEffect(() => {
    if (!hasRealWebSdk()) {
      setStatus('need-websdk');
      setMessage(
        'Falta el WebSdk real. Copie websdk.client.ui.min.js desde HID Lite Client a public/websdk/ (reemplace el stub) y reinicie npm run dev.',
      );
      return;
    }

    let reader: FingerprintReader;
    try {
      reader = new FingerprintReader();
    } catch {
      setStatus('need-websdk');
      setMessage(
        'No se pudo iniciar FingerprintReader. Verifique que public/websdk/ tenga el JS real del Lite Client.',
      );
      return;
    }
    readerRef.current = reader;

    const onConnected = (event: DeviceConnected) => {
      setDeviceName(event.deviceId ?? 'Lector conectado');
      setStatus('ready');
      setMessage('Lector listo. Pulse "Capturar huella".');
    };

    const onDisconnected = (_event: DeviceDisconnected) => {
      setDeviceName(null);
      setStatus('need-lite-client');
      setMessage('Lector desconectado. Verifique USB y driver U.are.U 4500.');
    };

    const onFailed = (_event: CommunicationFailed) => {
      setStatus('need-lite-client');
      setMessage(
        'No hay comunicación con HID Lite Client. Instálelo: digitalpersona.hidglobal.com/lite-client',
      );
    };

    const onSample = (event: SamplesAcquired) => {
      setTemplateData(samplesToTemplatePayload(event));
      const img =
        event.sampleFormat === SampleFormat.PngImage
          ? sampleToImageSrc(event)
          : null;
      if (img) setPreview(img);
      setStatus('sample');
      setMessage('Huella capturada.');
    };

    reader.on('DeviceConnected', onConnected);
    reader.on('DeviceDisconnected', onDisconnected);
    reader.on('CommunicationFailed', onFailed);
    reader.on('SamplesAcquired', onSample);

    try {
      reader
        .enumerateDevices()
        .then((devices) => {
          if (devices.length === 0) {
            setStatus('need-lite-client');
            setMessage('No se detectó lector. Conecte U.are.U 4500 e instale HID Lite Client.');
          } else {
            setDeviceName(devices[0]);
            setStatus('ready');
            setMessage(`Lector: ${devices[0]}`);
          }
        })
        .catch(() => {
          setStatus('need-lite-client');
          setMessage('Instale HID Authentication Device Client (Lite Client) en Windows.');
        });
    } catch {
      setStatus('need-lite-client');
      setMessage('Instale HID Authentication Device Client (Lite Client) en Windows.');
    }

    return () => {
      reader.stopAcquisition().catch(() => undefined);
      reader.off();
      readerRef.current = null;
    };
  }, []);

  const startCapture = useCallback(async (format: SampleFormat = SampleFormat.Intermediate) => {
    const reader = readerRef.current;
    if (!reader) {
      setStatus('need-websdk');
      setMessage('Lector no disponible. Instale Lite Client y el WebSdk real.');
      return;
    }

    try {
      setPreview(null);
      setTemplateData(null);
      setStatus('scanning');
      setMessage('Coloque el dedo en el lector...');
      await reader.startAcquisition(format);
    } catch (err) {
      setStatus('error');
      setMessage(err instanceof Error ? err.message : 'Error al iniciar captura');
    }
  }, []);

  const stopCapture = useCallback(async () => {
    const reader = readerRef.current;
    if (!reader) return;
    await reader.stopAcquisition().catch(() => undefined);
    setStatus('ready');
    setMessage('Captura detenida.');
  }, []);

  return {
    status,
    message,
    preview,
    templateData,
    deviceName,
    startCapture,
    stopCapture,
  };
}
