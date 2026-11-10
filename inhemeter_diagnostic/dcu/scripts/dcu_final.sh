echo "=== getDownLinkInfo_Communication (60s) ==="
dbus-send --system --print-reply --reply-timeout=60000 --dest=inhe.dcu.downlink /inhe/dcu/downlink inhe.dcu.downlink.getDownLinkInfo_Communication 2>&1
echo "=== logs grep LGZ/landis ==="
grep -rli "LGZ\|landis\|l+g\|gyr" /tmp/ram_log /home/httpd/store 2>/dev/null | head -20
echo "=== plugin log tail ==="
ls -la /tmp/ram_log/plugin /tmp/ram_log/traff 2>/dev/null
for f in /tmp/ram_log/plugin/* /tmp/ram_log/traff/*; do echo "--- $f"; tail -30 "$f" 2>/dev/null; done | head -120
echo "=== strings itemdb ==="
strings -n 4 /opt/home/httpd/store/param/itemdb.bin 2>/dev/null | head -60
