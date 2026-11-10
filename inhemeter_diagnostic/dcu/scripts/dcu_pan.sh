echo "=== /tmp ==="
ls -la /tmp
echo "=== /tmp/renesas ==="
ls -la /tmp/renesas 2>/dev/null
echo "=== /var/log ==="
ls -la /var/log 2>/dev/null
echo "=== grep PAN in configs ==="
grep -rni "pan" /home/plc /home/work --include="*.conf" --include="*.json" --include="*.cfg" --include="*.txt" 2>/dev/null | head -40
echo "=== grep panid ==="
grep -rni "panid\|pan_id" /home /etc /tmp 2>/dev/null | head -40
echo "=== cmdline all ==="
for p in /proc/[0-9]*; do echo "$p: $(tr '\0' ' ' < $p/cmdline)"; done | grep -iv "kernel\|^/proc/1:\|ksoftirq\|kworker\|migration\|watchdog\|dhd_\|irq/" | head -60
echo "=== net ==="
ip addr 2>/dev/null || cat /proc/net/dev
echo "=== redis keys ==="
redis-cli -p 6239 info keyspace 2>/dev/null
redis-cli -p 6239 keys "*" 2>/dev/null | head -60
