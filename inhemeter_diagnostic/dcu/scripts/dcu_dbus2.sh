echo "=== find Menu-conf.json ==="
find / -name "*Menu-conf.json" 2>/dev/null
echo "=== find diagnosticsMenu ==="
find / -name "diagnosticsMenu*" 2>/dev/null
echo "=== dbus names ==="
dbus-send --system --print-reply --dest=org.freedesktop.DBus /org/freedesktop/DBus org.freedesktop.DBus.ListNames
echo "=== dbus objects root ==="
dbus-send --system --print-reply --dest=org.freedesktop.DBus / org.freedesktop.DBus.ListNames 2>/dev/null | head -40
