echo "=== getSystemInfo ==="
gdbus call --system --dest inhe.dcu.traff --object-path /inhe/dcu/traff --method inhe.dcu.traff.getSystemInfo 2>&1
echo "=== getTermInfo ==="
gdbus call --system --dest inhe.dcu.traff --object-path /inhe/dcu/traff --method inhe.dcu.traff.getTermInfo 2>&1
echo "=== getOnlineList ==="
gdbus call --system --dest inhe.dcu.traff --object-path /inhe/dcu/traff --method inhe.dcu.traff.getOnlineList 2>&1
echo "=== getDownLinkInfo_RS485 ==="
gdbus call --system --dest inhe.dcu.traff --object-path /inhe/dcu/traff --method inhe.dcu.traff.getDownLinkInfo_RS485 2>&1
echo "=== getUpLinkInfo_ETH ==="
gdbus call --system --dest inhe.dcu.traff --object-path /inhe/dcu/traff --method inhe.dcu.traff.getUpLinkInfo_ETH 2>&1
echo "=== IPv6 addrs ==="
cat /proc/net/if_inet6 2>/dev/null
echo "=== ip -6 ==="
ip -6 addr 2>&1
echo "=== 6lowpan modules ==="
lsmod 2>/dev/null | grep -i "lowpan\|ieee802154\|g3\|plc"
echo "=== /proc/modules ==="
cat /proc/modules | head -40
