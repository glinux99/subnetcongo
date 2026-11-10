echo "=== php ==="
which php; php -v 2>&1 | head -3; php -m 2>/dev/null | grep -i "redis\|pdo\|sqlite"
echo "=== getDownLinkPara ==="
gdbus call --system --dest inhe.dcu.traff --object-path /inhe/dcu/traff --method inhe.dcu.traff.getDownLinkPara 2>&1
echo "=== getSystemPara ==="
gdbus call --system --dest inhe.dcu.traff --object-path /inhe/dcu/traff --method inhe.dcu.traff.getSystemPara 2>&1
echo "=== getUpLinkConfigure ==="
gdbus call --system --dest inhe.dcu.traff --object-path /inhe/dcu/traff --method inhe.dcu.traff.getUpLinkConfigure 2>&1
echo "=== dmesg plc/g3 ==="
dmesg 2>/dev/null | grep -i "plc\|g3\|ttymxc2\|ifxahd\|hplc" | tail -40
echo "=== dev ttymxc ==="
ls -la /dev/ttymxc*
echo "=== strings renesas_dcu ==="
strings /home/work/rss_g3/renesas_dcu 2>/dev/null | grep -i "G3\|hplc\|renesas\|version\|pan" | head -40
echo "=== find store/data ==="
find /opt/home/httpd/store -maxdepth 4 -type f 2>/dev/null | head -60
echo "=== grep LGZ/Landis ==="
grep -rli "LGZ\|landis\|l+g\|l%2Bg" /home /opt /tmp 2>/dev/null | head -30
