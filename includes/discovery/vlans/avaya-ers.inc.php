<?php
echo 'RC-VLAN-MIB VLANs: ';
if ($device['os'] == 'avaya-ers') {

  $vtpdomain_id = '1';
  $vlans        = snmpwalk_cache_oid($device, 'rcVlanName', array(), 'RC-VLAN-MIB');
  $tagoruntag   = snmpwalk_cache_oid($device, 'rcVlanPortMembers', array(), 'RC-VLAN-MIB', null, '-OQUs --hexOutputLength=0');
  $port_pvids   = snmpwalk_cache_oid($device, 'rcVlanPortDefaultVlanId', array(), 'RC-VLAN-MIB');
  $port_mode    = snmpwalk_cache_oid($device, 'rcVlanPortPerformTagging', array(), 'RC-VLAN-MIB');
  
  foreach ($vlans as $vlan_id => $vlan) {
      d_echo(" $vlan_id");
      if (is_array($vlans_db[$vtpdomain_id][$vlan_id])) {
          echo '.';
      } else {
          dbInsert(array(
              'device_id' => $device['device_id'],
              'vlan_domain' => $vtpdomain_id,
              'vlan_vlan' => $vlan_id,
              'vlan_name' => $vlan['rcVlanName'],
              'vlan_type' => array('NULL')
          ), 'vlans');
          echo '+';
      }
      $device['vlans'][$vtpdomain_id][$vlan_id] = $vlan_id;    
      $egress_ids = q_bridge_bits2indices($tagoruntag[$vlan_id]['rcVlanPortMembers']);
      $untagged_ids = array();
      
      foreach ($port_pvids as $port => $port_num) {
          if ($port_num['rcVlanPortDefaultVlanId'] == $vlan_id && 
          ($port_mode[$port]['rcVlanPortPerformTagging'] == 'false' || $port_mode[$port]['rcVlanPortPerformTagging'] == 4 )) {              
              array_push($untagged_ids, $port);
          }          
      }
      foreach ($egress_ids as $port_id) {
          $ifIndex = $base_to_index[$port_id];
          $per_vlan_data[$vlan_id][$ifIndex]['untagged'] = (in_array($port_id, $untagged_ids) ? 1 : 0);
      }
  }
}
