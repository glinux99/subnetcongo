echo "=== dlms tree ==="
find /opt/home/work/dlms -maxdepth 3 | head -60
echo "=== ti_g3 / ti_prime / rss_g3 ==="
ls -la /opt/home/work/ti_g3 /opt/home/work/ti_prime /opt/home/work/rss_g3
echo "=== con_config.cfg ==="
cat /opt/home/work/con_config.cfg
echo "=== submodule_info.json ==="
cat /opt/home/work/submodule_info.json
echo "=== error.txt ==="
cat /opt/home/work/error.txt
echo "=== dcu_config.json ==="
cat /opt/home/work/dcu_config.json
echo "=== cmcu_event ==="
cat /opt/home/work/cmcu_event
