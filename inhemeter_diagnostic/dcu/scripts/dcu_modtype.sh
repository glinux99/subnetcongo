echo "=== plugin_module g3/plc/rf/dual ==="
strings /home/work/plugin_module 2>/dev/null | grep -i "g3\|hplc\|plc\|rf_\|dual\|module_type\|3762\|ofdm" | sort -u | head -80
echo "=== update g3/plc ==="
strings /home/work/update 2>/dev/null | grep -i "g3\|hplc\|plc\|rf\b\|dual" | sort -u | head -60
echo "=== plugin.log head ==="
head -40 /tmp/ram_log/plugin/plugin.log
echo "=== plugin.log grep uart/module/version ==="
grep -i "uart\|module\|version\|hplc\|g3\|rf" /tmp/ram_log/plugin/plugin.log | head -40
