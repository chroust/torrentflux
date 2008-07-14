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
include_once('include/simplepie.inc');
include_once("AliasFile.php");
$maxdietime=6;
$interval=5;
$thispid=$cfg['torrent_file_path'].'.cronwork';
$dieCall=$cfg['torrent_file_path'].'.Killcron';
$cfg['cronwork_log']=$cfg['torrent_file_path'].'.cronwork.log';
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
		checkrss();
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
		$totalfinished=$totaldownloading=$totalactive=$totalinactive=0;
		$sql = "SELECT * FROM `tf_torrents`";
		$result = $db->Execute($sql);
			while($torrent = $result->FetchRow()){
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
						$owner=Uid2Username($this->owner);
						$dlpath=$cfg['force_dl_in_home_dir']?($cfg["path"].$owner):($cfg["path"]);
						$dlpath.=$torrent['location'].$torrent['file_name'];
						$command=str_replace(
							array('%finish_time%','%path%','%file_name%','%hash%','%owner%','%uptotal%','%downtotal%','%size%')
						,	array($timestamp,$dlpath,$torrent['file_name'],$torrent['hash'],$owner,$torrent['uptotal'],$torrent['downtotal'],$torrent['size'])
						,$cfg['global_finished_command']);
						shell_exec($command);
					}
					if(($torrent['statusid']==2 ||$torrent['statusid']==4) && ($status==3 || $status==5)){
						// if the process is stopped
						//possible because of reaching share limit
						AuditAction($cfg["constants"]["tor_stopped"],"Torrent: ".$torrent['file_name']."Stopped(possible because of reaching share limit)");
						StartRunQueue();
					}
				$estTime = ($af->time_left != "" && $af->time_left != "0")? $af->time_left:'';
				$estTime = $estTime=='Download Succeeded!'?'':$estTime;
				$percent_done=$af->percent_done;
				$down_speed=$af->down_speed+0;
				$up_speed=$af->up_speed+0;
				$seeds=$af->seeds;
				$peers=$af->peers;
				$uptotal=$af->uptotal;
				$downtotal=$af->downtotal;
				$size=$af->size;
				$id=$torrent['id'];
				
				// total static
				$totalupload+=$up_speed;
				$totaldownload+=$down_speed;
				$totalseed+=$seeds;
				$totalpeers+=$peers;
					if(in_array($status,array(4,5))){
						//finished count
						$totalfinished++;
					}elseif($status==2){
						//downloading count
						$totaldownloading++;
					}
					if(in_array($status,array(2,4))){
						$totalactive++;
					}else{
						$totalinactive++;
					}
				
				//update it into the sql
				$sql="UPDATE `tf_torrents` SET `statusid`='$status',`estTime`='$estTime',`size`='$size',`percent_done`='$percent_done',`down_speed`='$down_speed',`up_speed`='$up_speed',`seeds`='$seeds',`peers`='$peers',`uptotal`='$uptotal',`downtotal`='$downtotal',`haspid`='$haspid' WHERE `id`='$id'";
				$db->Execute($sql);
				unset ($af);
			}
		// unest to release memory
		unset($torrent,$result,$haspid,$status,$status_text,$estTime,$percent_done,$down_speed,$up_speed,$seeds,$peers,$uptotal,$downtotal,$size,$id);
		updateSetting('totalupload',$totalupload);
		updateSetting('totaldownload',$totaldownload);
		updateSetting('totalseed',$totalseed);
		updateSetting('totalpeers',$totalpeers);
		updateSetting('totalfinished',$totalfinished);
		updateSetting('totaldownloading',$totaldownloading);
		updateSetting('totalactive',$totalactive);
		updateSetting('totalinactive',$totalinactive);
		unset($totalupload,$totaldownload,$totalseed,$totalpeers,$totalfinished,$totaldownloading,$totalactive,$totalinactive);
	}
	
	////////////////////////////////////////////////////////////////////////////////
	// transfer limit
	/////////////////////////////////////////////////////////////////////////////////
	function check_Transfer_Limit(){
		//CronworkLog("checking if any user overflow transfer limit");
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
	// rss
	/////////////////////////////////////////////////////////////////////////////////
	function checkrss(){
		//function for checking rss update
		global $db;
		//check if rss have new feed
		// Initialize new feed
		//CronworkLog("checking if any new rss update");
		$feed = new SimplePie();
		$sql = 'select url,timestamp,uid from tf_rss';
		$result = $db->Execute($sql);
		showError($db,$sql);
		while($row = $result->FetchRow()){
			$feed->set_feed_url($row['url']);
			$feed->init();
				foreach($feed->get_items() as $item) {
						if ($item->get_date('U') > $row['timestamp']) {
							newrss($item,$row['uid']);
						}
				}
		}
		// update timestamp to current time
		$sql = 'update tf_rss SET  `timestamp`=\''.time().'\' ';
		$db->Execute($sql);
		showError($db,$sql);
		unset($feed,$sql);
	}
	function newrss($item,$uid){
		//function for update when there is a new rss
		//title: $item->get_title()
		//time: $item->get_date('j M Y, H:i:s O') 
		//description : $item->get_description()
		CronworkLog("new torrent from the rss, uid: $uid,url: $item");
		$GLOBALS['cfg']['uid']=$uid;
		$GLOBALS['myuid']=$uid;
		$torrentid=UrlTorrent($item->get_title());
			if($cfg['rssautostart']){
				$Bt= new BtControl($torrentid);
				$Bt->Start();
			}
		unset($Bt,$torrentid);
		$GLOBALS['cfg']['uid']=0;
		$GLOBALS['myuid']=0;
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
	$timestamp=date("Y-m-d H:i:s");
	$msg=$timestamp.' :'.$msg."\n";
	echo $msg;
	unset($timestamp,$msg);
}
?>
