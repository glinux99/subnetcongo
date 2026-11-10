echo "=== files containing ipv6 ==="
grep -ril "ipv6" /home/work /home/plc /opt/home/httpd/store /etc 2>/dev/null | head -30
echo "=== ex_para.bin strings ==="
strings -n 4 /opt/home/httpd/store/param/ex_para.bin | head -80
echo "=== manu_item_statedb.bin strings ==="
strings -n 4 /opt/home/httpd/store/param/manu_item_statedb.bin | head -100
echo "=== mstatedb.bin strings ==="
strings -n 4 /opt/home/httpd/store/param/mstatedb.bin | head -100
echo "=== traff ipv6 config strings ==="
strings /home/work/traff | grep -i "ipv6" | sort -u | head -60
