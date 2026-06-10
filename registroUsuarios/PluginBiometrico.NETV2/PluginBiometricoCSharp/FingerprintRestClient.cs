using System;
using System.Net.Http;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace PluginBiometricoCSharp
{
    public class FingerprintPayload
    {
        [JsonProperty("serial")]
        public string Serial { get; set; }

        [JsonProperty("huella")]
        public string Huella { get; set; }

        [JsonProperty("imageHuella")]
        public string ImageHuella { get; set; }

        [JsonProperty("texto")]
        public string Texto { get; set; }

        [JsonProperty("statusPlantilla")]
        public string StatusPlantilla { get; set; }

        [JsonProperty("documento")]
        public string Documento { get; set; }

        [JsonProperty("nombre")]
        public string Nombre { get; set; }

        [JsonProperty("dedo")]
        public string Dedo { get; set; }

        [JsonProperty("option")]
        public string Option { get; set; }
    }

    public class FingerprintRestClient
    {
        private static readonly HttpClient Http = new HttpClient();
        private readonly string _serverPath;

        public FingerprintRestClient()
        {
            _serverPath = Utils.GetConfigValue("urlRestApi");
        }

        public Task<bool> AsociarHuellaAsync(FingerprintPayload payload)
        {
            return SendJsonAsync(HttpMethod.Post, payload);
        }

        public Task<bool> ActualizarHuellaAsync(FingerprintPayload payload)
        {
            return SendJsonAsync(HttpMethod.Put, payload);
        }

        public async Task<string> ListaHuellasAsync(string serial, int desde, int hasta)
        {
            if (string.IsNullOrWhiteSpace(_serverPath))
            {
                throw new InvalidOperationException("No esta configurada la Url Rest Api.");
            }

            var uri = string.Format(
                "{0}?token={1}&desde={2}&hasta={3}&_={4}",
                _serverPath,
                Uri.EscapeDataString(serial ?? string.Empty),
                desde,
                hasta,
                DateTimeOffset.UtcNow.ToUnixTimeMilliseconds());

            using (var request = new HttpRequestMessage(HttpMethod.Get, uri))
            {
                request.Headers.UserAgent.ParseAdd("Mozilla/5.0");
                request.Headers.CacheControl = new System.Net.Http.Headers.CacheControlHeaderValue { NoCache = true };

                var response = await Http.SendAsync(request).ConfigureAwait(false);
                return await response.Content.ReadAsStringAsync().ConfigureAwait(false);
            }
        }

        private async Task<bool> SendJsonAsync(HttpMethod method, FingerprintPayload payload)
        {
            if (string.IsNullOrWhiteSpace(_serverPath))
            {
                throw new InvalidOperationException("No esta configurada la Url Rest Api.");
            }

            var uri = string.Format("{0}?_={1}", _serverPath, DateTimeOffset.UtcNow.ToUnixTimeMilliseconds());
            var json = JsonConvert.SerializeObject(payload);

            using (var request = new HttpRequestMessage(method, uri))
            {
                request.Headers.UserAgent.ParseAdd("Mozilla/5.0");
                request.Content = new StringContent(json, Encoding.UTF8, "application/json");

                var response = await Http.SendAsync(request).ConfigureAwait(false);
                return response.IsSuccessStatusCode;
            }
        }
    }
}
