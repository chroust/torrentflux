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
include_once("settingsfunctions.php");
include_once("functions.php");
include_once("AliasFile.php");
include_once("RunningTorrent.php");
session_name("TorrentFlux");

session_start();

// Create Connection.
$db = getdb();
loadSettings();

///////////////// tmp
$dirName=$cfg["torrent_file_path"];
////////////////////////// tmp end


$lastUser = "";
$arList=$output=$arListTorrent=$arUserTorrent = array();
$totalUpSpeed=$totalDownSpeed=0;
$file_filter = getFileFilter($cfg["file_types_array"]);
$cfg["total_upload"] = $cfg["total_download"] = 0;

    $runningTorrents = getRunningTorrents();

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
	while(list($id, $file_name,$torrent,$hash, $owner_id) = $result->FetchRow()){

// get torrent info
	$show_run = true;
	$torrentowner = $owner_id;
	$kill_id = "";
	$estTime = "&nbsp;";
	$alias = getAliasName($torrent).".stat";
	$af = new AliasFile($dirName.$alias, $torrentowner);
	$timeStarted = "";
	$torrentfilelink = "";
		// find out if any screens are running and take their PID and make a KILL option
		foreach ($runningTorrents as $key => $value){
		    $rt = new RunningTorrent($value);
			    if ($rt->statFile == $alias) {
					$kill_id = ($kill_id == "")?$rt->processId:"|".$rt->processId;
	    		}
		}
        // Check to see if we have a pid without a process.
        if (is_file($cfg["torrent_file_path"].$alias.".pid") && empty($kill_id)){
 			// died outside of tf and pid still exists.
         @unlink($cfg["torrent_file_path"].$alias.".pid");
     		    // The file is not running and the percent done needs to be changed
				if(($af->percent_done < 100) && ($af->percent_done >= 0)){
					$af->percent_done = ($af->percent_done+100)*-1;
				}
			$af->running = "0";
			$af->time_left = "Torrent Died";
			$af->up_speed = "";
			$af->down_speed = "";
			// write over the status file so that we can display a new status
			$af->WriteFile();
        }
        
	$haspid=GetPid(torrent2stat($torrent))=='-1'?0:1;
	list($status,$status_text)=grabbingStatus($af->running,$af->percent_done,$haspid);
		
	
	//if($_GET['status'] == "0" OR ($_GET['status'] == "1" AND ($status == "2" OR $status == "3")) OR ($_GET['status'] == "2"  AND ($status == "4" OR $status == "5"))){
		$in_filter=1;
	//}
	if($in_filter){
	
	$return = array(
		'id'		=>$id,
       	'title'	=>$file_name,
       	'timeleft'	=>$af->time_left,
       	'owner'	=>$torrentowner,
       	'status'	=>$status_text
	);
    	if($tatus=="0"){
			// it is new
		}elseif($tatus=="1"){
			//queue
		}else{
			$estTime = ($af->time_left != "" && $af->time_left != "0")? $af->time_left:'';
			$estTime = $estTime=='Download Succeeded!'?'':$estTime;
			$timeStarted =  (is_file($dirName.$alias.".pid"))?strval(filectime($dirName.$alias.".pid")):'';
			$sql_search_time = "Select time from tf_log where action like '%Upload' and file like '".$entry."%' LIMIT 1";
			$result_search_time = $db->Execute($sql_search_time);
			list($uploaddate) = $result_search_time->FetchRow();
			$endtime=$af->percent_done >= 100  ?  strval(filemtime($dirName.$alias)): 0;

			$reutrn_add = array(
				'down_speed'=>$af->down_speed+0,
				'up_speed'=>$af->up_speed+0,
				'percent'=>$af->percent_done,
  		     	'size'	=>formatBytesToKBMGGB($af->size),
				'sharing'=>$sharing,
				'seeds'=>$af->seeds,
				'peers'=>$af->peers,
				'uploaddate'=>$uploaddate,
				'timeStarted'=>$timeStarted,
				'endtime'=>$endtime,
				'estTime'=>$estTime,
				'uptotal'=>$af->uptotal,
				'downtotal'=>$af->downtotal,
				'haspid'=>$haspid
			);
			//total upload& download speed
		}
		
	$return = array_merge($reutrn_add, $return);
	$output['torrents'][]=$return;
	$totalUpSpeed=$totalUpSpeed+$af->up_speed;
	$totalDownSpeed=$totalDownSpeed+$af->down_speed;
}
	$output['global']['totalUpSpeed']=$totalUpSpeed;
	$output['global']['totalDownSpeed']=$totalDownSpeed;
	
}

echo json_encode($output);

?>
