echo "=== config dir ==="
ls -la /home/httpd/config 2>/dev/null
echo "=== diagnosticsMenu-conf.json ==="
cat /home/httpd/config/diagnosticsMenu-conf.json 2>/dev/null
echo "=== configurationMenu-conf.json ==="
cat /home/httpd/config/configurationMenu-conf.json 2>/dev/null
echo "=== dbus tools ==="
which dbus-send gdbus busctl dbus-monitor 2>/dev/null
echo "=== dbus service names ==="
ls /usr/share/dbus-1/system-services 2>/dev/null
ls /etc/dbus-1/system.d 2>/dev/null
echo "=== system.d files ==="
for f in /etc/dbus-1/system.d/*; do echo "--- $f"; cat "$f"; done 2>/dev/null | head -200
