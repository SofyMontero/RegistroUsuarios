using System;
using System.Net.Http;
using System.Threading;
using System.Threading.Tasks;
using System.Windows.Forms;
using Newtonsoft.Json.Linq;

namespace PluginBiometricoCSharp
{
    public class PollingService
    {
        private readonly HttpClient _http = new HttpClient();
        private readonly FingerprintModeCoordinator _coordinator;
        private readonly SemaphoreSlim _gate = new SemaphoreSlim(1, 1);
        private string _serverPath;

        public PollingService(FingerprintModeCoordinator coordinator)
        {
            _coordinator = coordinator;
            _serverPath = Utils.GetConfigValue("urlHabSensor");
        }

        public async Task CheckAsync()
        {
            if (!await _gate.WaitAsync(0))
            {
                return;
            }

            try
            {
                if (string.IsNullOrWhiteSpace(_serverPath) || string.IsNullOrWhiteSpace(Program.UniqueId))
                {
                    return;
                }

                var ts = Program.Timestamp;
                var uri = $"{_serverPath}?timestamp={Uri.EscapeDataString(ts.ToString())}&token={Uri.EscapeDataString(Program.UniqueId)}&_={DateTimeOffset.UtcNow.ToUnixTimeMilliseconds()}";
                var res = await _http.GetAsync(uri);
                var text = await res.Content.ReadAsStringAsync();
                if (res.IsSuccessStatusCode)
                {
                    var obj = JObject.Parse(text);
                    Program.Timestamp = obj.Value<long>("fecha_creacion");
                    var opc = obj.Value<string>("opc");
                    if (opc == "capturar")
                    {
                        _coordinator.ActivateCapture();
                    }
                    else if (opc == "leer")
                    {
                        _coordinator.ActivateRead();
                    }
                }
            }
            catch (Exception ex)
            {
                Console.WriteLine("Error polling: " + ex.Message);
            }
            finally
            {
                _gate.Release();
            }
        }
    }
}
