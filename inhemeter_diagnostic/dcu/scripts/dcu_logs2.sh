echo "=== landis in decompressed logs ==="
for f in /tmp/ram_log/archive/traff/*.gz; do
  gzip -dc "$f" 2>/dev/null | grep -i "landis\|LGZ\|L+G" && echo "  ^ in $f"
done | head -40
echo "=== current traff.log: g3/hplc/pan/ipv6/3762 ==="
grep -i "g3\|hplc\|pan\|ipv6\|3762\|renesas\|ti_g3\|protocol" /tmp/ram_log/traff/traff.log | tail -60
echo "=== plugin.log module/plc/rf ==="
grep -i "module\|plc\|rf\|dual\|protocol\|version" /tmp/ram_log/plugin/plugin.log | tail -40
