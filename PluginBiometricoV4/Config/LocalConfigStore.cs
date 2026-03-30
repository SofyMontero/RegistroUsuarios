using Microsoft.Data.Sqlite;

namespace PluginBiometricoV4.Config;

/// <summary>
/// Persistencia local equivalente a Config.db del plugin Java (urlHabSensor, urlRestApi, uniqueId, browser).
/// </summary>
public sealed class LocalConfigStore
{
    public const string KeyUrlHabSensor = "urlHabSensor";
    public const string KeyUrlRestApi = "urlRestApi";
    public const string KeyUniqueId = "uniqueId";
    public const string KeyBrowser = "browser";

    private readonly string _dbPath;

    public LocalConfigStore()
    {
        var dir = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "PluginBiometricoV4");
        Directory.CreateDirectory(dir);
        _dbPath = Path.Combine(dir, "Config.db");
    }

    public string DatabasePath => _dbPath;

    public void EnsureCreated()
    {
        using var conn = Open();
        using var cmd = conn.CreateCommand();
        cmd.CommandText = """
            CREATE TABLE IF NOT EXISTS config (
                key TEXT PRIMARY KEY NOT NULL,
                value TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS service_flags (
                id INTEGER PRIMARY KEY CHECK (id = 1),
                windows_service_registered INTEGER NOT NULL DEFAULT 0
            );
            INSERT OR IGNORE INTO service_flags (id, windows_service_registered) VALUES (1, 0);
            """;
        cmd.ExecuteNonQuery();
    }

    public bool IsComplete()
    {
        EnsureCreated();
        foreach (var key in new[] { KeyUrlHabSensor, KeyUrlRestApi, KeyUniqueId })
        {
            if (string.IsNullOrWhiteSpace(Get(key)))
                return false;
        }
        return true;
    }

    public string? Get(string key)
    {
        EnsureCreated();
        using var conn = Open();
        using var cmd = conn.CreateCommand();
        cmd.CommandText = "SELECT value FROM config WHERE key = $k LIMIT 1";
        cmd.Parameters.AddWithValue("$k", key);
        var o = cmd.ExecuteScalar();
        return o is string s ? s : null;
    }

    public void Set(string key, string value)
    {
        EnsureCreated();
        using var conn = Open();
        using var cmd = conn.CreateCommand();
        cmd.CommandText = """
            INSERT INTO config (key, value) VALUES ($k, $v)
            ON CONFLICT(key) DO UPDATE SET value = excluded.value;
            """;
        cmd.Parameters.AddWithValue("$k", key);
        cmd.Parameters.AddWithValue("$v", value);
        cmd.ExecuteNonQuery();
    }

    public bool IsWindowsServiceFlagSet()
    {
        EnsureCreated();
        using var conn = Open();
        using var cmd = conn.CreateCommand();
        cmd.CommandText = "SELECT windows_service_registered FROM service_flags WHERE id = 1";
        var o = cmd.ExecuteScalar();
        return o is long l && l != 0;
    }

    public void SetWindowsServiceFlag(bool registered)
    {
        EnsureCreated();
        using var conn = Open();
        using var cmd = conn.CreateCommand();
        cmd.CommandText = "UPDATE service_flags SET windows_service_registered = $v WHERE id = 1";
        cmd.Parameters.AddWithValue("$v", registered ? 1 : 0);
        cmd.ExecuteNonQuery();
    }

    private SqliteConnection Open()
    {
        var conn = new SqliteConnection($"Data Source={_dbPath}");
        conn.Open();
        return conn;
    }
}
