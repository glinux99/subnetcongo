echo "=== /home/plc/plc.conf ==="
cat /home/plc/plc.conf
echo "=== /home/work/rss_g3/version.txt ==="
cat /home/work/rss_g3/version.txt
echo "=== /home/work/rss_g3/config/g3_conf.conf ==="
cat /home/work/rss_g3/config/g3_conf.conf
echo "=== /home/work/rss_g3/dbg_cmd.sh ==="
cat /home/work/rss_g3/dbg_cmd.sh
echo "=== dcu_config.json ==="
cat /home/work/dcu_config.json
echo "=== submodule_info.json ==="
cat /home/work/submodule_info.json
echo "=== con_config.cfg ==="
cat /home/work/con_config.cfg
echo "=== /tmp/work/g3_panid_mode_config.bin (hex) ==="
od -An -tx1 /tmp/work/g3_panid_mode_config.bin
echo "=== /tmp/work/error.txt ==="
cat /tmp/work/error.txt
echo "=== interfaces ==="
ifconfig | head -60
