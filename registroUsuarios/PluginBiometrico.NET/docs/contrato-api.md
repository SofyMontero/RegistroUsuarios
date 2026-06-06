# Contrato API — Plugin Biométrico

Este documento describe cómo el plugin de escritorio se comunica con el backend PHP.
El plugin .NET mantiene el mismo protocolo que el plugin Java original.

## Configuración local

Archivo: `%LocalAppData%\PluginBiometrico\config.json`

| Campo JSON | Equivalente Java | Uso |
|------------|------------------|-----|
| `idUnicoPc` | `uniqueId` | Token de la PC. Se envía como `token` o `serial`. |
| `urlHabilitarSensor` | `urlHabSensor` | URL de `HabilitarSensor.php` |
| `urlApiRest` | `urlRestApi` | URL de `UsuarioRestApi.php` |
| `navegador` | `browser` | Chrome, Mozilla, Edge, Explorer |
| `autoInicioConfigurado` | tabla `SERVICE` | Si ya se configuró auto-inicio |

---

## 1. HabilitarSensor.php (GET)

El plugin consulta periódicamente si la web pidió capturar o leer una huella.

**Request:**
```
GET {urlHabilitarSensor}?timestamp={unix}&token={idUnicoPc}&_={milisegundos}
```

**Response (JSON):**
```json
{
  "fecha_creacion": 1717680000,
  "opc": "capturar"
}
```

| `opc` | Acción del plugin |
|-------|-------------------|
| `capturar` | Iniciar enrollment (registro de huella) |
| `leer` | Iniciar verificación (identificar usuario) |
| `reintentar` | Sin cambios, seguir esperando |
| `stop` | Detener operación actual |

**Disparado desde la web por:** `Model/ActivarSensorAdd.php` (inserta en `huellas_temp`).

---

## 2. UsuarioRestApi.php

### GET — Obtener plantillas para verificación

```
GET {urlApiRest}?token={idUnicoPc}&desde={n}&hasta={m}
```

**Response:** array de objetos:
```json
[
  {
    "count": 150,
    "documento": "1234567890",
    "nombre_completo": "Juan Pérez",
    "nombre_dedo": "Indice derecho",
    "huella": "base64...",
    "imgHuella": "base64...",
    "foto_usu": "foto.jpg"
  }
]
```

### POST — Guardar plantilla final (captura completada)

**Body (JSON):**
```json
{
  "serial": "ABC123",
  "huella": "base64_plantilla",
  "imageHuella": "base64_imagen",
  "texto": "La plantilla ha sido creada",
  "statusPlantilla": "Muestras Restantes: 0",
  "foto_usu": ""
}
```

### PUT — Actualizar progreso o resultado de verificación

**Progreso de captura** (`option` distinto de `verificar`):
```json
{
  "serial": "ABC123",
  "imageHuella": "base64_imagen",
  "texto": "Huella capturada",
  "statusPlantilla": "Muestras Restantes: 2"
}
```

**Resultado de verificación** (`option = "verificar"`):
```json
{
  "serial": "ABC123",
  "imageHuella": "base64_imagen",
  "texto": "Huella capturada",
  "statusPlantilla": "Usuario Verificado",
  "option": "verificar",
  "documento": "1234567890",
  "nombre": "Juan Pérez",
  "dedo": "Indice derecho",
  "foto_usu": ""
}
```

---

## 3. Flujo web → plugin → web

```
1. Web llama ActivarSensorAdd.php  →  INSERT huellas_temp (opc=capturar)
2. Plugin recibe opc=capturar      →  inicia captura con lector
3. Plugin envía PUT (progreso)     →  UPDATE huellas_temp
4. Plugin envía POST (plantilla)   →  UPDATE huellas_temp con huella
5. Web hace long-poll httpush.php  →  muestra imagen y estado al operador
```

---

## Tabla temporal (MySQL)

`huellas_temp` almacena el estado en tiempo real entre la web y el plugin.

Campos clave: `pc_serial`, `opc`, `huella`, `imgHuella`, `texto`, `statusPlantilla`, `documento`, `nombre`, `update_time`.
