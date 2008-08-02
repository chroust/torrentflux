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

$cfg['rsstable']='tf_rss';
//**************************************************************************
//function for checking rss update
function checkrssFeed(){
	global $db,$cfg;
	//check if rss have new feed
	// Initialize new feed
	include_once(ENGINE_ROOT.'include/simplepie.inc');
	$feed = new SimplePie();
	$sql = 'select rid,url,uid,lastitem from tf_rss';
	$result = $db->Execute($sql);
	while($row = $result->FetchRow()){
		$feed->set_feed_url($row['url']);
		$feed->init();
		$firstitem=$feed->get_item(0);
		$isnew=1;
		$hasupdate=0;
			foreach($feed->get_items() as $item) {
					if($isnew && $item->get_id()==$row['lastitem']){
						$isnew=0;
					}
					if ($isnew) {
						$hasupdate=1;
						DownloadNewFeed($item,$row['uid'],$row['autostart']);
						//update it to sql
						$record=array(
							'lastitem'=>$firstitem->get_id(),
							'timestamp'=>time()
						);
						$db->AutoExecute($cfg['rsstable'],$record,'UPDATE', "rid = '".$row['rid']."'");
					}
			}
		$feed->__destruct();
		unset($feed); 
	}
	// update timestamp to current time
	
	unset($sql);
}
//**************************************************************************
//function for update when there is a new rss
//title: $item->get_title()
//time: $item->get_date('j M Y, H:i:s O') 
//description : $item->get_description()
function DownloadNewFeed($item,$uid,$autostart=0){
	$GLOBALS['cfg']['uid']=$uid;
	$GLOBALS['myuid']=$uid;
	CronworkLog("new torrent from the rss, uid: $uid,url: ".$item->get_permalink());
	$GLOBALS['myuid']=$uid;
	$torrentid=UrlTorrent($item->get_permalink());
		if($autostart){
			$Bt= new BtControl($torrentid);
			$Bt->Start();
		}
	unset($Bt,$torrentid);
	$GLOBALS['cfg']['uid']=0;
	$GLOBALS['myuid']=0;
}
// ***************************************************************************
// Delete RSS
function deleteOldRSS($rid){
	global $db;
		if(!is_numeric($rid)){
			return false;
		}
	$sql = "delete from tf_rss where rid=".$rid;
	$result = $db->Execute($sql);
	showError($db,$sql);
}
// ***************************************************************************
// addNewRSS - Add New RSS Link
function addNewRSS($url){
	global $db,$cfg;
		if(!checkRSS($url)){
			return false;
		}
	$record = array('url'=>$url,'uid'=>$cfg['uid'],'title'=>getRSStitle($url));
	$db->AutoExecute($cfg['rsstable'],$record,'INSERT');
}
function getRSStitle($url){
	include_once(ENGINE_ROOT.'include/simplepie.inc');
	$feed = new SimplePie();
	$feed->set_feed_url($url);
	$feed->init();
	$title=$feed->get_title();
	$feed->__destruct();
	unset($feed); 
	return $title;
}
function checkRSS($url){
	include_once(ENGINE_ROOT.'include/simplepie.inc');
	$feed = new SimplePie();
	$feed->set_feed_url($url);
	$feed->init();
	$return=$feed->error()?false:true;
	$feed->__destruct();
	unset($feed); 
	return $return;
}
// ***************************************************************************
// addNewRSS - Add New RSS Link
function SetAutoDownload($rid,$value){
	global $db,$cfg;
		if(!is_numeric($rid)){
			return false;
		}
	$value=$value?'1':'0';
	$record = array('autostart'=>$value);
	$db->AutoExecute($cfg['rsstable'],$record,'UPDATE',"`rid`='$rid'");
	return $value;
}
// ***************************************************************************
// Get RSS Feed in an array
function GrabFeedArray($rid,$itemlimit=0){
	global $db,$cfg;
		if(!is_numeric($rid)){
			return false;
		}
	//creat the array for returning values
	$return = array();
	//get the url from rid
	$sql="SELECT `url` FROM ".$cfg['rsstable']." WHERE `rid` = '$rid'";
	$url=$db->GetOne($sql);
	// grab the feed content
	include_once(ENGINE_ROOT.'include/simplepie.inc');
	$feed = new SimplePie();
	$feed->set_feed_url($url);
	$feed->set_item_limit(intval($itemlimit));
	$feed->init();
		foreach($feed->get_items() as $item) {
			$id=$item->get_id();
			$date=$item->get_date('d-m-Y H:i');
			$timestamp=$item->get_date('U');
			$link=$item->get_permalink();
			$title=$item->get_title();
			$return[$id]=array('date'=>$date,'timestamp'=>$timestamp,'link'=>$link,'title'=>$title);
		}
	$feed->__destruct();
	unset($feed,$sql,$url);
	return $return;
}
// ***************************************************************************
// Get RSS Links in an array
function GetRSSLinks($rid=0){
	global $cfg, $db;
	$userSQL=AdminCheck()?' AND `uid`=\''.$cfg['uid'].'\'':'';
		if($rid==0){
			$sql = "SELECT * FROM tf_rss WHERE 1 $userSQL ORDER BY rid";
			return $db->GetArray($sql);
		}else{
			$sql = "SELECT * FROM tf_rss WHERE rid=$rid $userSQL";
			return $db->GetOne($sql);
		}
}
?>
