echo "=== redis LRANGE plus_routine ==="
printf '*2\r\n$4\r\nAUTH\r\n$8\r\ninheroot\r\n*4\r\n$6\r\nLRANGE\r\n$12\r\nplus_routine\r\n$1\r\n0\r\n$2\r\n-1\r\n' | nc -w 3 127.0.0.1 6239 | tr '\r' '\n'
echo
echo "=== redis TYPE/other keys ==="
printf '*2\r\n$4\r\nAUTH\r\n$8\r\ninheroot\r\n*2\r\n$4\r\nTYPE\r\n$12\r\nplus_online_list\r\n' | nc -w 3 127.0.0.1 6239 | tr '\r' '\n'
echo "=== ttymxc1 users ==="
for p in /proc/[0-9]*; do ls -l $p/fd 2>/dev/null | grep -q "ttymxc1" && echo "$p $(tr '\0' ' ' < $p/cmdline)"; done
echo "=== /dev/ttymxc* ==="
ls -la /dev/ttymxc* 2>&1
echo "=== version.txt ti_g3 ==="
cat /home/work/ti_g3/* 2>/dev/null | head -20
cat /home/tiplc/ti_g3/* 2>/dev/null | head -20
