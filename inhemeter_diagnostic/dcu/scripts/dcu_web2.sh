echo "=== controllers ==="
find /home/httpd/html/pc/controller -type f | head -80
echo "=== view ==="
find /home/httpd/html/pc/view -type f | head -120
echo "=== grep pan (case-insens) in controller/view/public ==="
grep -rni "pan" /home/httpd/html/pc/controller /home/httpd/html/pc/view /home/httpd/html/pc/public 2>/dev/null | head -60
echo "=== grep topology/neighbor ==="
grep -rni "topolog\|neighbor\|nbr\|node_list\|network" /home/httpd/html/pc/controller /home/httpd/html/pc/view 2>/dev/null | head -60
echo "=== redis-cli? ==="
which redis-cli; ls -la /usr/bin/redis* /sbin/redis* 2>/dev/null
echo "=== mainFrom ==="
sed -n '1,120p' /home/httpd/html/pc/index.php
