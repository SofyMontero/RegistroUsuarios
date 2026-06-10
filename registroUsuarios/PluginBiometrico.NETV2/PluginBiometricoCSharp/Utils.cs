using System;
using System.IO;
using Microsoft.Win32;
using Newtonsoft.Json.Linq;

namespace PluginBiometricoCSharp
{
    public static class Utils
    {
        private const string StartupValueName = "PluginBiometricoCSharp";
        private const string StartupRunKey = @"Software\Microsoft\Windows\CurrentVersion\Run";
        public static string AppConfigPath;

        static Utils()
        {
            AppConfigPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Config.json");
        }

        public static string GetConfigValue(string key)
        {
            if (!File.Exists(AppConfigPath)) return string.Empty;
            var txt = File.ReadAllText(AppConfigPath);
            if (string.IsNullOrWhiteSpace(txt)) return string.Empty;
            var obj = JObject.Parse(txt);
            return obj.ContainsKey(key) ? obj[key].ToString() : string.Empty;
        }

        public static void SetConfigValue(string key, string value)
        {
            JObject obj = File.Exists(AppConfigPath) ? JObject.Parse(File.ReadAllText(AppConfigPath)) : new JObject();
            obj[key] = value;
            File.WriteAllText(AppConfigPath, obj.ToString());
        }

        public static bool IsStartupEnabled()
        {
            using (var key = Registry.CurrentUser.OpenSubKey(StartupRunKey, false))
            {
                return key?.GetValue(StartupValueName) != null;
            }
        }

        public static void EnableStartup()
        {
            using (var key = Registry.CurrentUser.OpenSubKey(StartupRunKey, true) ?? Registry.CurrentUser.CreateSubKey(StartupRunKey))
            {
                key.SetValue(StartupValueName, GetExecutablePath(), RegistryValueKind.String);
            }
        }

        public static void DisableStartup()
        {
            using (var key = Registry.CurrentUser.OpenSubKey(StartupRunKey, true))
            {
                key?.DeleteValue(StartupValueName, false);
            }
        }

        public static string GetExecutablePath()
        {
            var exe = System.Diagnostics.Process.GetCurrentProcess().MainModule.FileName;
            if (!string.IsNullOrWhiteSpace(exe) && File.Exists(exe))
            {
                return exe;
            }

            return Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "PluginBiometricoCSharp.exe");
        }

        public static void RestartApplication()
        {
            try
            {
                var current = AppDomain.CurrentDomain.BaseDirectory;
                var exe = GetExecutablePath();
                if (!File.Exists(exe)) exe = Path.Combine(current, "PluginBiometricoCSharp.exe");
                if (!File.Exists(exe)) exe = Path.Combine(current, "PluginBiometricoV3.exe");
                if (!File.Exists(exe)) exe = Path.Combine(current, "PluginBiometricoV3.jar");
                System.Diagnostics.Process.Start(new System.Diagnostics.ProcessStartInfo("cmd", $"/c start /min \"\" \"{exe}\" ^& exit") { CreateNoWindow = true, UseShellExecute = false });
                Environment.Exit(0);
            }
            catch (Exception ex)
            {
                Console.WriteLine("Error reiniciando: " + ex);
            }
        }
    }
}
