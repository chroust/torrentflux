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
$maxdietime=5;
$interval=5;
$thispid=$cfg['torrent_file_path'].'.cronwork';
$dieCall=$cfg['torrent_file_path'].'.Killcron';
$cfg['cronwork_log']=$cfg['torrent_file_path'].'.cronwork.log';
CronworkLog('');
CronworkLog('Started Cron Work');
	////////////////////////////////////////////////////////////////////////////////
	// CheckHung
	/////////////////////////////////////////////////////////////////////////////////
	function CheckAllHung(){
		global $db;
		CronworkLog("checking if any hung process");
		$sql = "SELECT `torrent` FROM `tf_torrents`";
		$result = $db->Execute($sql);
		while(list($torrent) = $result->FetchRow()){
			CheckHung($torrent);
		}
	}


	////////////////////////////////////////////////////////////////////////////////
	// transfer limit
	/////////////////////////////////////////////////////////////////////////////////
	function check_Transfer_Limit(){
		CronworkLog("checking if any user overflow transfer limit");
		$userarray=GetUserList();
			foreach($userarray as $index=>$user){
					if(!checkTransferLimit($user['uid'])){
						//if its transfer limit overflow
						CronworkLog("uid: ".$user['uid']." overflow his transfer limit");
						All('Kill',$user['uid']);
					}
			}
	}


	////////////////////////////////////////////////////////////////////////////////
	// rss
	/////////////////////////////////////////////////////////////////////////////////
	function checkrss(){
		//function for checking rss update
		global $db;
		//check if rss have new feed
		// Initialize new feed
		CronworkLog("checking if any new rss update");
		$feed = new SimplePie();
		$sql = 'select url,timestamp,uid from tf_rss';
		$result = $db->Execute($sql);
		showError($db,$sql);
		$rssarray=array();
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
		$result = $db->Execute($sql);
		showError($db,$sql);
	}
	function newrss($item,$uid){
		//function for update when there is a new rss
		//title: $item->get_title()
		//time: $item->get_date('j M Y, H:i:s O') 
		//description : $item->get_description()
		CronworkLog("new torrent from the rss, uid: $uid,url: $item");
		$GLOBALS['cfg']['uid']=$uid;
		$torrentid=UrlTorrent($item->get_title());
			if($cfg['rssautostart']){
				$Bt= new BtControl($torrentid);
				$Bt->Start();
			}
		$GLOBALS['cfg']['uid']=0;
	}
	

	////////////////////////////////////////////////////////////////////////////////
	// global
	/////////////////////////////////////////////////////////////////////////////////
	function RunLoop(){
		// the main loop
		checkDieCall();
		checkrss();
		check_Transfer_Limit();
		CheckAllHung();
	}
	
	function checkDieCall(){
		global $dieCall,$thispid;
		CronworkLog('checking if there is any diecall');
		//Check if any die call
			if(file_exists($dieCall)){
				unlink($thispid);
				unlink($dieCall);
				CronworkLog('Die Call Received');
				exit();
			}
	}
	

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
		RunLoop();
		touch($thispid);
		sleep($interval);
	}
unlink($thispid);
unlink($dieCall);

function CronworkLog($msg){
	global $cfg;
	$timestamp=date("Y-m-d H:i:s");
	$msg=$timestamp.' :'.$msg."\n";
	echo $msg;
}
?>
