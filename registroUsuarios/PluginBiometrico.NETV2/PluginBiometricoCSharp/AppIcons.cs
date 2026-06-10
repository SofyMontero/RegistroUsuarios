using System;
using System.Drawing;
using System.IO;

namespace PluginBiometricoCSharp
{
    public static class AppIcons
    {
        private static Icon _icon;

        public static Icon MainIcon
        {
            get
            {
                if (_icon != null)
                {
                    return _icon;
                }

                var path = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Assets", "PluginIcon.ico");
                _icon = File.Exists(path) ? new Icon(path) : SystemIcons.Application;
                return _icon;
            }
        }
    }
}
