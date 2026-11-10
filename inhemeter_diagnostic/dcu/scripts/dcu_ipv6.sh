echo "=== redis keys (AUTH inheroot) ==="
printf '*2\r\n$4\r\nAUTH\r\n$8\r\ninheroot\r\n*2\r\n$4\r\nKEYS\r\n$1\r\n*\r\n' | nc -w 3 127.0.0.1 6239 | tr '\r' ' ' | head -60
echo
echo "=== redis GET plus_routine ==="
printf '*2\r\n$4\r\nAUTH\r\n$8\r\ninheroot\r\n*2\r\n$3\r\nGET\r\n$12\r\nplus_routine\r\n' | nc -w 3 127.0.0.1 6239 | tr '\r' ' ' | head -20
echo
echo "=== redis GET ipv6-related keys ==="
for k in ipv6 ipv6_status term_ipv6 ipv6_addr ipv6_setup; do
  printf "*2\r\n\$4\r\nAUTH\r\n\$8\r\ninheroot\r\n*2\r\n\$3\r\nGET\r\n\$$(echo -n $k | wc -c)\r\n$k\r\n" | nc -w 2 127.0.0.1 6239 | tr '\r' ' '
  echo
done
echo "=== web/config ipv6 ==="
grep -rni "ipv6" /home/httpd/html/pc /home/httpd/html/config 2>/dev/null | head -40
echo "=== ttymxc2 users ==="
for p in /proc/[0-9]*; do ls -l $p/fd 2>/dev/null | grep -q ttymxc2 && echo "$p $(tr '\0' ' ' < $p/cmdline)"; done
echo "=== /tmp/renesas ==="
ls -la /tmp/renesas 2>&1
