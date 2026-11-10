echo "=== /opt/home/work ==="
ls -la /opt/home/work
echo "=== g3_fw_info.bin ==="
strings -n 3 /opt/home/work/g3_fw_info.bin 2>/dev/null | head -40
od -An -tx1 /opt/home/work/g3_fw_info.bin 2>/dev/null | head -10
echo "=== g3_topo_list.bin ==="
strings -n 3 /opt/home/work/g3_topo_list.bin 2>/dev/null | head -60
echo "=== g3_online_list_ExpTime.bin ==="
strings -n 3 /opt/home/work/g3_online_list_ExpTime.bin 2>/dev/null | head -40
echo "=== logs version/band ==="
grep -rh "Module version\|HPLC Band\|dual Module version\|G3 Module" /tmp/ram_log 2>/dev/null | tail -20
echo "=== plugin_param NodeMap count ==="
sqlite3 /opt/home/httpd/store/data/plugin_param.db "select count(*) from NodeMaptbl;"
