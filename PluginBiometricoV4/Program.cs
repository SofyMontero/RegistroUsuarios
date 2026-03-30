using PluginBiometricoV4.Config;

namespace PluginBiometricoV4;

static class Program
{
    /// <summary>
    ///  The main entry point for the application.
    /// </summary>
    [STAThread]
    static void Main()
    {
        ApplicationConfiguration.Initialize();

        var store = new LocalConfigStore();
        store.EnsureCreated();

        if (!store.IsComplete())
        {
            using var cfg = new ConfigurationForm();
            if (cfg.ShowDialog() != DialogResult.OK)
                return;
        }

        Application.Run(new Form1());
    }
}