echo "=== traff ipv6/6lowpan/3762 ==="
strings /home/work/traff 2>/dev/null | grep -i "ipv6\|6lowpan\|6low\|3762\|hplc\|g3_\|g3plc" | sort -u | head -60
echo "=== metering dbus/meter methods ==="
strings /home/work/metering 2>/dev/null | grep -i "GetMeter\|MeterInfo\|manufacturer\|MeterList\|getOnline\|Protocol" | sort -u | head -60
echo "=== task meter strings ==="
strings /home/work/task 2>/dev/null | grep -i "meter\|manufacturer\|LGZ" | sort -u | head -60
echo "=== dmesg plc/uart ==="
dmesg 2>/dev/null | grep -i "ifxahd\|ttymxc\|plc\|uart\|modem" | tail -40
echo "=== redis via nc ==="
printf '*2\r\n$4\r\nAUTH\r\n$7\r\ninheroot\r\n*2\r\n$4\r\nKEYS\r\n$1\r\n*\r\n' | nc -w 3 127.0.0.1 6239 | head -80
