cmd() {
  printf '*%d\r\n' $#
  for a in "$@"; do printf '$%d\r\n%s\r\n' "${#a}" "$a"; done
}
{
  cmd AUTH inheroot
  cmd TYPE plus_routine
  cmd TYPE plus_online_list
  cmd TYPE plugin_static
  cmd TYPE networking_time
  cmd TYPE network_scale
  cmd TYPE whitelist_switch
  cmd TYPE PLC_INSERT_S
  cmd TYPE mixed_switch
  cmd TYPE module_name
  cmd TYPE module_software_ver
  cmd TYPE coprocessor_version
  cmd TYPE module_max_node_num
} | nc -w 3 127.0.0.1 6239 | tr -d '\r'
echo "---- GET/LRANGE/HGETALL ----"
{
  cmd AUTH inheroot
  cmd GET plus_online_list
  cmd GET plugin_static
  cmd GET networking_time
  cmd GET network_scale
  cmd GET whitelist_switch
  cmd GET PLC_INSERT_S
  cmd GET mixed_switch
  cmd GET module_name
  cmd GET module_software_ver
  cmd GET coprocessor_version
  cmd GET module_max_node_num
  cmd LRANGE plus_routine 0 -1
  cmd HGETALL plus_routine
  cmd GET plus_routine
} | nc -w 3 127.0.0.1 6239 | tr -d '\r'
