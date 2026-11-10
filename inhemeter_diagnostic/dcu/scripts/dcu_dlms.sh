echo "=== rss_g3 version/dbg ==="
cat /opt/home/work/rss_g3/version.txt
echo "---"
cat /opt/home/work/rss_g3/dbg_cmd.sh
echo "=== dlms dirs ==="
ls -la /opt/home/work/dlms/profile /opt/home/work/dlms/event /opt/home/work/dlms/upgrade
echo "=== dlms profile files ==="
find /opt/home/work/dlms -type f -exec ls -la {} \;
echo "=== libDataFormat strings landis/lgz/profile ==="
strings /opt/home/work/dlms/libDataFormat.so | grep -i "landis\|lgz\|l+g\|gyr\|profile\|manufacturer\|obis" | sort -u | head -60
echo "=== running procs 3762/renesas ==="
ps | grep -i "3762\|renesas\|p3762"
