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
	$status_text=array(
		'0'=>_show_status_New,
		'1'=>_show_status_Queue,
		'2'=>_show_status_Downloading,
		'3'=>_show_status_Stopped,
		'4'=>_show_status_Seeding,
		'5'=>_show_status_Finished,
	);
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
$Requiredstatus=split(',',getRequestVar('status'),4);
    if (is_dir($dirName)){
        $handle = opendir($dirName);
    }else{
		echo IsAdmin() ? 
		"<b>ERROR:</b> ".$dirName." Path is not valid. Please edit <a href='admin.php?op=configSettings'>settings</a><br>":
		"<b>ERROR:</b> Contact an admin the Path is not valid.<br>";
        return;
    }
$Requiredusers=getRequestVar('users');

	if(!$allow_view_other_torrent){
		//restrict other user's torrent are invisable
		$sqladd=" AND t.owner_id='".$myuid."'";
	}elseif($Requiredusers!=='0'){
		$sqladd=" AND t.owner_id IN('".$Requiredusers."')";
	}else{
		$sqladd='';
	}
$statusadd='';
	if(in_array('1',$Requiredstatus)){
		//if required status is "downloading"
		$statusadd.=$OR." `statusid`='2' ";
		$OR=' OR ';
	}elseif(in_array('2',$Requiredstatus)){
		//if required status is "finished"
		$statusadd.=$OR." `statusid`='4' OR `statusid`='5' ";
		$OR=' OR ';
	}
$statusadd=$statusadd?$statusadd:'1';
$OR='';
$haspidadd='';
	if(in_array('3',$Requiredstatus)){
		//if required status is "active"
		$haspidadd.=$OR." `haspid`='1' ";
		$OR=' OR ';
	}elseif(in_array('4',$Requiredstatus)){
		//if required status is "inactive"
		$haspidadd.=$OR." `haspid`='0' ";
		$OR=' OR ';
	}
$haspidadd=$haspidadd?$haspidadd:'1';
$sql = "SELECT w.hide_offline,w.last_visit,t.id,t.file_name,t.torrent,t.hash,t.owner_id,w.user_id,t.statusid,t.estTime,t.timeStarted,t.endtime,t.percent_done,t.down_speed,t.up_speed,t.size,t.seeds,t.peers,t.uptotal,t.downtotal,t.haspid FROM tf_torrents t,tf_users w WHERE w.uid=t.owner_id $sqladd AND ( $statusadd) AND ($haspidadd) ORDER BY `id` ASC ";
$result = $db->Execute($sql);
	while(list($hide_offline,$list_visit,$id, $file_name,$torrent,$hash, $owner_id,$owner,$statusid,$estTime,$timeStarted,$endtime,$percent_done,$down_speed,$up_speed,$size,$seeds,$peers,$uptotal,$downtotal,$haspid) = $result->FetchRow()){
		$return = array(
			'id'		=>$id,
			'title'	=>str_replace('.','-',$file_name),
			'owner'	=>$owner_id,
			'status'	=>$status_text[$statusid],
			'statusid'=>$statusid,
		);
			if($status=="1"){
				//queue
			}else{
				$reutrn_add = array(
					'down_speed'=>$down_speed,
					'up_speed'=>$up_speed,
					'percent'=>$percent_done,
  			     	'size'	=>formatBytesToKBMGGB($size),
				//	'sharing'=>$sharing,
					'seeds'=>$seeds,
					'peers'=>$peers,
				//	'uploaddate'=>$uploaddate,
				//	'timeStarted'=>$timeStarted,
				//	'endtime'=>$endtime,
					'estTime'=>$estTime,
				//	'uptotal'=>$uptotal,
				//	'downtotal'=>$downtotal,
				//	'haspid'=>$haspid
				);
			}
		$return = array_merge($reutrn_add, $return);
		$output['torrents'][]=$return;
	}
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
