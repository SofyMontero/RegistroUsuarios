using System;
using System.Reflection;

namespace PluginBiometricoCSharp
{
    public class FingerprintWrapper
    {
        private object _comObject;
        public bool IsAvailable { get; private set; }

        private readonly string[] _progIds = new[] {
            "DPFPCtlX.DPFPCtl",
            "DPFPCtlX.DPFPCtl.1",
            "DPFPDevX.DPFPDev",
            "DPFPDevX.DPFPDev.1",
            "DPFPEngX.DPFPEng",
        };

        public FingerprintWrapper()
        {
            // Try to create a COM ActiveX instance from known ProgIDs shipped with the SDK
            foreach (var pid in _progIds)
            {
                try
                {
                    var type = Type.GetTypeFromProgID(pid);
                    if (type != null)
                    {
                        _comObject = Activator.CreateInstance(type);
                        IsAvailable = _comObject != null;
                        if (IsAvailable)
                        {
                            Console.WriteLine($"Fingerprint SDK COM object created from ProgID: {pid}");
                            break;
                        }
                    }
                }
                catch (Exception ex)
                {
                    Console.WriteLine($"ProgID {pid} not available: {ex.Message}");
                }
            }
            if (!IsAvailable)
            {
                Console.WriteLine("Fingerprint SDK not available. Using stubs.");
            }
        }

        public bool Capture()
        {
            if (!IsAvailable) return false;
            // Try common method names used by ActiveX controls
            var candidates = new[] { "StartCapture", "Capture", "StartEnroll", "Enroll" };
            foreach (var m in candidates)
            {
                if (TryInvoke(m)) return true;
            }
            return false;
        }

        public bool Read()
        {
            if (!IsAvailable) return false;
            var candidates = new[] { "StartCapture", "Verify", "Identify", "StartVerify" };
            foreach (var m in candidates)
            {
                if (TryInvoke(m)) return true;
            }
            return false;
        }

        private bool TryInvoke(string methodName)
        {
            try
            {
                var t = _comObject.GetType();
                // Use InvokeMember to call methods on the COM object
                t.InvokeMember(methodName, BindingFlags.InvokeMethod, null, _comObject, new object[0]);
                Console.WriteLine($"Invoked method {methodName} on fingerprint COM object.");
                return true;
            }
            catch (MissingMethodException)
            {
                // method not found, ignore
                return false;
            }
            catch (TargetInvocationException tie)
            {
                Console.WriteLine($"Error invoking {methodName}: {tie.InnerException?.Message ?? tie.Message}");
                return false;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Error invoking {methodName}: {ex.Message}");
                return false;
            }
        }
    }
}
