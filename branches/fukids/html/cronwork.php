<?php

/*************************************************************
*  TorrentFlux - PHP Torrent Manager
*  www.torrentflux.com
**************************************************************/
/*
    This file is part of TorrentFlux.

    TorrentFlux is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    TorrentFlux is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with TorrentFlux; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
Ignore_User_Abort(True);
set_time_limit(0);
include_once('include/functions.php');
include_once("AliasFile.php");
define('FILE_APPEND', 1);
$maxdietime=6;
$interval=5;
CronworkLog('');
CronworkLog('Started Cron Work');

//check if the pid file exist, 
	if(file_exists($thispid)){
		$lastupdate=filemtime($thispid);
			if((time()-$lastupdate) <$maxdietime){
				CronworkLog('another instant is running');
				exit();
			}
	}
touch($thispid);
//MainLoop
	CronworkLog('Start running main loop');
	while(1){
		checkDieCall();
		torrentUpdate();
		checkrssFeed();
		check_Transfer_Limit();
		checkHung();
		touch($thispid);
		sleep($interval);
	}
unlink($thispid);
unlink($dieCall);
	////////////////////////////////////////////////////////////////////////////////
	// update torrent
	/////////////////////////////////////////////////////////////////////////////////
	// this function grab the information from .stat which is made by bittornado , then update it to sql
	function torrentUpdate(){
		global $db,$cfg;
		$userCount=array();
		$totalupload=$totaldownload=$totalseeding=$totalfinished=$totalpeers=$totalseed=$totaldownloading=$totalactive=$totalinactive=0;
		$sql = "SELECT * FROM `tf_torrents`";
		$result = $db->Execute($sql);
			while($torrent = $result->FetchRow()){
				$owner_id=$torrent['owner_id'];
				//grab the .stat file made by bittornado
				$af = new AliasFile($cfg["torrent_file_path"].torrent2stat($torrent['torrent']), $torrent['owner_id']);
				//grab the pid from .pid file
				$haspid=GetPid(torrent2stat($torrent['torrent']))=='-1'?false:true;
				//grab the current status of the torrent
					if($af->time_left=='Download Failed!'){
						$status='-1';
						$status_text=_ERRORSREPORTED;
					}else{
						list($status,$status_text)=grabbingStatus($af->running,$af->percent_done,$haspid);
					}
					if($torrent['statusid']==2 && ($status==4 || $status==5)){
						// if it is just finished
						AuditAction($cfg["constants"]["tor_completed"], "Torrent: ".$torrent['file_name']."Download Completed");
						$timestamp=date("Y-m-d H:i:s");
						$owner=Uid2Username($torrent['owner_id']);
						$dlpath=$cfg['force_dl_in_home_dir']?($cfg["path"].$owner):($cfg["path"]);
						$dlpath.=$torrent['location'].$torrent['file_name'];
						$command=str_replace(
							array('%finish_time%','%path%','%file_name%','%hash%','%owner%','%uptotal%','%downtotal%','%size%')
						,	array($timestamp,$dlpath,$torrent['file_name'],$torrent['hash'],$owner,$torrent['uptotal'],$torrent['downtotal'],$torrent['size'])
						,$cfg['global_finished_command']);
						shell_exec($command);
					}
					if(($torrent['statusid']==2 ||$torrent['statusid']==4) && ($status==3 || $status==5)){
						// if the process is stopped automatically (not through Torrentflux)
						//possible because of reaching share limit
						AuditAction($cfg["constants"]["tor_stopped"],"Torrent: ".$torrent['file_name']."Stopped(possible because of reaching share limit)");
						StartRunQueue();
					}
				$estTime = ($af->time_left != "" && $af->time_left != "0")? $af->time_left:'';
				$estTime = $estTime=='Download Succeeded!'?'':$estTime;
				$percent_done=$af->percent_done;
					if($percent_done<0){
						$percent_done=($percent_done*-1)-100;
					}
				//remove the speed unit
				$downspeed=GetSpeedValue($af->down_speed);
				$upspeed=GetSpeedValue($af->up_speed);
				// total static
				$totalupload+=$upspeed;
				$totaldownload+=$downspeed;
				$totalseed+=$af->seeds;
				$totalpeers+=$af->peers;
				//creat user's update static
					if(!array_key_exists($owner_id,$userCount)){
						$userCount[$owner_id]=array(
							'DownloadSpeed'=>0,
							'UploadSpeed'=>0,
							'downloadingtorrent'=>0,
							'seedingtorrent'=>0,
							'activetorrent'=>0
						);
					}
					if(in_array($status,array(4,5))){
						//finished count
						$totalfinished++;
					}elseif($status==2){
						//downloading count
						$totaldownloading++;
						$userCount[$owner_id]['downloadingtorrent']++;
					}
					if($status==4){
						//seeding count
						$totalseeding++;
						$userCount[$owner_id]['seedingtorrent']++;
					}
					if(in_array($status,array(2,4))){
						//active count
						$totalactive++;
						$userCount[$owner_id]['activetorrent']++;
					}else{
						$totalinactive++;
					}
				//user's speed static
				$userCount[$owner_id]['UploadSpeed']=$userCount[$owner_id]['UploadSpeed']+$upspeed;
				$userCount[$owner_id]['DownloadSpeed']=$userCount[$owner_id]['DownloadSpeed']+$downspeed;
				
				//get new speed log
				$speedlog=unserialize($torrent['speedlog']);
					if(is_array($speedlog) && count($speedlog['down'])>300){
						array_shift($speedlog['down']);
						array_shift($speedlog['up']);
					}
				$speedlog['up'][]=round($af->up_speed);
				$speedlog['down'][]=round($af->down_speed);
				$sqlspeedlog=serialize($speedlog);
				//update it into the sql
				$table='tf_torrents';
				$record=array(
					'statusid'=>$status,
					'speedlog'=>$sqlspeedlog,
					'estTime'=>$estTime,
					'size'=>$af->size,
					'percent_done'=>$percent_done,
					'down_speed'=>$downspeed,
					'up_speed'=>$upspeed,
					'seeds'=>$af->seeds,
					'peers'=>$af->peers,
					'uptotal'=>$af->uptotal,
					'downtotal'=>$af->downtotal,
					'size'=>$af->size,
					'haspid'=>($haspid?1:0),
				);
				$db->AutoExecute($table,$record,'UPDATE', "`id`='".$torrent['id']."'");
				showError($db,$sql);
				unset($af);
			}
		//update data to users' database
			foreach($userCount as $uid => $userStatic){
				$db->AutoExecute($cfg['table']['users'], $userStatic, 'UPDATE', " `uid`='$uid' ");
			}
		// unest to release memory
		unset($torrent,$result,$haspid,$status,$status_text,$estTime,$percent_done,$down_speed,$up_speed,$seeds,$peers,$uptotal,$downtotal,$size,$id);
		updateSetting('totalupload',$totalupload);
		updateSetting('totaldownload',$totaldownload);
		updateSetting('totalseed',$totalseed);
		updateSetting('totalpeers',$totalpeers);
		updateSetting('totalfinished',$totalfinished);
		updateSetting('totaldownloading',$totaldownloading);
		updateSetting('totalseeding',$totalseeding);
		updateSetting('totalactive',$totalactive);
		updateSetting('totalinactive',$totalinactive);
		unset($totalupload,$totaldownload,$totalseed,$totalpeers,$totalfinished,$totaldownloading,$totalactive,$totalinactive);
	}
	
	////////////////////////////////////////////////////////////////////////////////
	// transfer limit
	/////////////////////////////////////////////////////////////////////////////////
	function check_Transfer_Limit(){
		//CronworkLog("checking if any user overflow transfer limit");
		//check global transfer limit
		checkTransferLimit();
		//check user transfer limit
		$userarray=GetUserList();
			foreach($userarray as $index=>$user){
					if(!checkTransferLimit($user['uid'])){
						//if its transfer limit overflow
						CronworkLog("uid: ".$user['uid']." overflow his transfer limit");
						All('Kill',$user['uid']);
					}
			}
		unset($userarray);
	}

	////////////////////////////////////////////////////////////////////////////////
	// global
	/////////////////////////////////////////////////////////////////////////////////
	function checkDieCall(){
		global $dieCall,$thispid;
		//CronworkLog('checking if there is any diecall');
		//Check if any die call
			if(file_exists($dieCall)){
				unlink($thispid);
				unlink($dieCall);
				CronworkLog('Die Call Received');
				exit();
			}
	}
	

function CronworkLog($msg){
	global $cfg;
	$msg=is_array($msg)?implode('|',$msg):$msg;
	$timestamp=date('Y-m-d H:i:s');
	$msg=$timestamp.' :'.$msg."\n";
	//echo $msg;
	//ob_flush();
    //flush();
	
    $f = @fopen($cfg['cronwork_log'], 'a');
    if ($f === false) {
        return 0;
    } else {
        fwrite($f, $msg);
        fclose($f);
    }
}
?>
