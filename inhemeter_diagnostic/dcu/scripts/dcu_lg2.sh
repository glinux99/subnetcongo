DB=/opt/home/httpd/store/data/plugin_param.db
echo "=== NodeMaptbl rows ==="
sqlite3 "$DB" "select idx,mac,deviceDI,joinTime from NodeMaptbl;"
echo "=== meterdb.bin size ==="
ls -la /opt/home/httpd/store/param/meterdb.bin /opt/home/httpd/store/param/online_manage.bin /opt/home/httpd/store/param/ex_para.bin
echo "=== meterdb.bin strings ==="
strings -n 4 /opt/home/httpd/store/param/meterdb.bin | head -100
echo "=== grep LGZ/Landis/Gyr in all binaries ==="
for f in /home/work/metering /home/work/task /home/work/traff /home/work/plugin_module /home/work/daemon /home/work/test /home/work/update /home/work/dlms/libDataFormat.so; do
  echo "--- $f"
  strings "$f" 2>/dev/null | grep -i "LGZ\|landis\|l+g\|gyr" | head -10
done
