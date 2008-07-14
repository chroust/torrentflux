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

//**************************************************************************
//function for checking if it exist global thread limit
//return false when it is over limit
function checkGlobalQueueLimit(){
	global $cfg;
	return getActiveTorrentsCount()>$cfg['maxServerThreads']?false:true;
}
//**************************************************************************
//function for checking if it exist user thread limit
//return false when it is over limit
function checkUserQueueLimit($uid=0){
	global $cfg;
	$uid=(intval($uid)==0)?$cfg['uid']:intval($uid);
	return getActiveTorrentsCount()>$cfg['maxUserThreads']?false:true;
}
function NewQueue($torrentid,$uid){
	global $cfg,$db;
		if(!is_numeric($torrentid)){
			showmessage('torrentid error',1,1);
		}
		if(!$cfg['AllowQueing']){
			return;
		}
	$table='tf_queue';
	$sql="INSERT INTO `tf_queue` (`torrentid`,`uid`) VALUES ('$torrentid','$uid')";
	$db->Execute($sql);
	$sql="UPDATE `tf_torrents` SET `statusid`='1' WHERE `id`='$torrentid'";
	$db->Execute($sql);
	AuditAction($cfg["constants"]["queued_torrent"],"Torrentid: ".$torrentid." Queued");
}
function StartRunQueue(){
	global $cfg,$db;
		if(!$cfg['AllowQueing']){
			return;
		}
		if(!checkGlobalQueueLimit()){
			return;
		}
	$sql="SELECT MIN(`torrentid`),`uid` FROM `tf_queue`  group by `uid` ORDER BY `qid` ASC";
	$result = $db->Execute($sql);
	$runed =0;
	while(list($torrentid,$uid) = $result->FetchRow()){
		if(checkUserQueueLimit($uid) && $runed==0){
			include_once("include/BtControl_Tornado.class.php");
			$Bt= new BtControl($torrentid);
			$Bt->Start();
			unset($Bt);
			$sql="DELETE  FROM `tf_queue` WHERE `torrentid`='$torrentid'";
			$db->Execute($sql);
			showError($db,$sql);
			$runed=1;
		}
	}
	unset($sql,$result,$runed);
}
StartRunQueue();
?>
