using System;
using System.Windows.Forms;

namespace PluginBiometricoCSharp
{
    static class Program
    {
        public static long Timestamp = DateTimeOffset.UtcNow.ToUnixTimeSeconds();
        public static string UniqueId;

        [STAThread]
        static void Main()
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);

            // Check config db file (we'll use simple JSON config for now)
            var cfgPath = System.IO.Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Config.json");
            if (!System.IO.File.Exists(cfgPath))
            {
                var cfgForm = new ConfigForm(cfgPath);
                cfgForm.StartPosition = FormStartPosition.CenterScreen;
                Application.Run(cfgForm);
                return;
            }

            // load unique id
            UniqueId = Utils.GetConfigValue("uniqueId");

            // Show tray/main form
            Application.Run(new MainForm());
        }
    }
}
