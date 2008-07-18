<?php
/*
 *  list_torrent.php
 *  
 *  Copyright 2008 fukid <fukid@siulok.com>
 *  
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *  MA 02110-1301, USA.
 */
// Create Connection.

$dirName=$cfg["torrent_file_path"];
$lastUser = "";
$arList=$arListTorrent=$arUserTorrent =$tmp_usertotal= array();
		$output['global']=array(
			'totalUpSpeed'=>0,
			'totalDownSpeed'=>0,
			'totaldownloading'=>0,
			'totalfinished'=>0,
			'totalactive'=>0,
			'totalinactive'=>0,
		);
$totalUpSpeed=$totalDownSpeed=$total=$totacactive=$totalinactive=$totaldownloading=$totalinactive=0;
$Requiredstatus=getRequestVar('status');
$Requiredusers=getRequestVar('users');

$output['torrents']=Listtorrent($Requiredusers,$Requiredstatus);
$cronworking=CheckCronRobot()?1:0;
$output['global']=array(
	'totalUpSpeed'=>$cfg['totalupload'],
	'totalDownSpeed'=>$cfg['totaldownload'],
	'totaldownloading'=>$cfg['totaldownloading'],
	'totalfinished'=>$cfg['totalfinished'],
	'totalactive'=>$cfg['totalactive'],
	'totalinactive'=>$cfg['totalinactive'],
	'cronworking'=>$cronworking
);
//javascript
if(!$cronworking){
	// alert when cronrobot is not running
	$javascript.="	roar.alert('"._WARNING."','"._WARNING_Cronrobot_Is_Not_Running."');
					roar.items.getLast().addEvent('click', function() {
						OpenWindow('_Admin_Setting','"._Admin_Setting."','icon','','','');
					});
				";
}
if($newpm){
	// alert when have new pm
	$javascript.="	roar.alert('','"._NEW_PM_RECEIVED."');
					roar.items.getLast().addEvent('click', function() {
						OpenWindow('_VIEW_PM','"._VIEW_PM."','rightclick','uid=".$myuid."','','');
					});
				";
}

$output['global']['javascript']=$javascript;
// grab user data
$comma=$usrtotal_str='';
$sql = "SELECT `uid`,`runningtorrent` FROM `tf_users`";
$result = $db->Execute($sql);
	while(list($uid,$runningtorrent)= $result->FetchRow()){
		$usrtotal_str.=$comma.$uid.':'.$runningtorrent;
		$comma=',';
	}
$output['global']['usrtotal_str']=$usrtotal_str;
echo json_encode($output);
?>
