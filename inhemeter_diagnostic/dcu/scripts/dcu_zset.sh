cmd() {
  printf '*%d\r\n' $#
  for a in "$@"; do printf '$%d\r\n%s\r\n' "${#a}" "$a"; done
}
{
  cmd AUTH inheroot
  cmd ZRANGE plus_routine 0 -1 WITHSCORES
  cmd SMEMBERS plus_online_list
} | nc -w 3 127.0.0.1 6239 | tr -d '\r'
