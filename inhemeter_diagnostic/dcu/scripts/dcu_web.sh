echo "=== /tmp/ram_log ==="
ls -laR /tmp/ram_log | head -80
echo "=== /tmp/dlms ==="
ls -laR /tmp/dlms | head -60
echo "=== httpd html pc dirs ==="
ls -la /home/httpd/html/pc
echo "=== grep PAN_ID usage ==="
grep -rn "u-PAN_ID\|PAN_ID\|pan_id\|panid" /home/httpd/html/pc 2>/dev/null | head -40
echo "=== grep g3 in php ==="
grep -rln "g3\|G3" /home/httpd/html/pc 2>/dev/null | head -40
echo "=== sqlite tables ==="
sqlite3 /tmp/work/dcu_status_statistics.db ".tables" 2>/dev/null || echo "no sqlite3"
