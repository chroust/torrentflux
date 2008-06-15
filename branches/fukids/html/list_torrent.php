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

include_once('db.php');
include_once("functions.php");
include_once("AliasFile.php");
session_name("TorrentFlux");

session_start();

// Create Connection.
$db = getdb();
loadSettings();
$dirName=$cfg["torrent_file_path"];
$lastUser = "";
$arList=$output=$arListTorrent=$arUserTorrent = array();
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
	$sql = "SELECT t.id,t.file_name,t.torrent,t.hash,t.owner_id,w.user_id FROM tf_torrents t,tf_users w WHERE w.uid=t.owner_id";
	$result = $db->SelectLimit($sql, 50,0);
	while(list($id, $file_name,$torrent,$hash, $owner_id,$owner) = $result->FetchRow()){
		// get torrent info
		$alias = torrent2stat($torrent);
		$af = new AliasFile($dirName.$alias, $owner_id);
		$timeStarted = "";
		$haspid=GetPid(torrent2stat($torrent))=='-1'?0:1;
		list($status,$status_text)=grabbingStatus($af->running,$af->percent_done,$haspid);
		$totaldownloading+= ($status==2)?1:0;
		$totalfinished+= ($status==4 || $status ==5)?1:0;
		$totalactive+=($haspid==1)?1:0;
		$total++;
			if(in_array('0',$Requiredstatus)){
				//if no specific status required
				$in_filter=1;
			}elseif($status==2 && in_array('1',$Requiredstatus)){
				//if required status is "downloading"
				$in_filter=1;
			}elseif(($status==4 || $status ==5) && in_array('2',$Requiredstatus)){
				//if required status is "finished"
				$in_filter=1;
			}elseif($haspid==1 && in_array('3',$Requiredstatus)){
				//if required status is "active"
				$in_filter=1;
			}elseif($haspid==0 && in_array('4',$Requiredstatus)){
				//if required status is "active"
				$in_filter=1;
			}
			if($in_filter){
				$return = array(
					'id'		=>$id,
					'title'	=>$file_name,
					'owner'	=>$owner_id,
					'status'	=>$status_text
				);
					if($tatus=="0"){
						// it is new
					}elseif($tatus=="1"){
						//queue
					}else{
						$estTime = ($af->time_left != "" && $af->time_left != "0")? $af->time_left:'';
						$estTime = $estTime=='Download Succeeded!'?'':$estTime;
						$timeStarted =  ($haspid)?strval(filectime($dirName.$alias.".pid")):'';
						$endtime=$af->percent_done >= 100  ?  strval(filemtime($dirName.$alias)): 0;
						$reutrn_add = array(
							'down_speed'=>$af->down_speed+0,
							'up_speed'=>$af->up_speed+0,
							'percent'=>$af->percent_done,
  					     	'size'	=>formatBytesToKBMGGB($af->size),
						//	'sharing'=>$sharing,
							'seeds'=>$af->seeds,
							'peers'=>$af->peers,
						//	'uploaddate'=>$uploaddate,
						//	'timeStarted'=>$timeStarted,
						//	'endtime'=>$endtime,
							'estTime'=>$estTime,
						//	'uptotal'=>$af->uptotal,
						//	'downtotal'=>$af->downtotal,
						//	'haspid'=>$haspid
						);
					}
				$return = array_merge($reutrn_add, $return);
				$output['torrents'][]=$return;
				$totalUpSpeed=$totalUpSpeed+$af->up_speed;
				$totalDownSpeed=$totalDownSpeed+$af->down_speed;
			}
		$totalinactive=$total-$totalactive;
		$output['global']=array(
			'totalUpSpeed'=>$totalUpSpeed,
			'totalDownSpeed'=>$totalDownSpeed,
			'totaldownloading'=>$totaldownloading,
			'totalfinished'=>$totalfinished,
			'totalactive'=>$totalactive,
			'totalinactive'=>$totalinactive,
		);
	}
echo json_encode($output);
?>
