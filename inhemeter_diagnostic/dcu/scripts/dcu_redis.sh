echo "=== myredis.php ==="
cat /home/httpd/html/pc/controller/public/myredis.php
echo "=== dbqry.php ==="
cat /home/httpd/html/pc/controller/public/dbqry.php
echo "=== diagnostics/downlink.php ==="
cat /home/httpd/html/pc/controller/diagnostics/downlink.php
echo "=== grep u-PAN_ID everywhere ==="
grep -rn "u-PAN_ID\|PAN_ID\|pan_id\|panid\|PANID" /home/httpd 2>/dev/null | head -60
echo "=== redis config / data dir ==="
ls -la /opt/redis
cat /home/httpd/html/pc/controller/config/down.php
