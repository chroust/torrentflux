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

// Start Session and grab user
define('ENGINE_ROOT', substr(dirname(__FILE__), 0, -7));
include_once(ENGINE_ROOT.'include/db.class.php');
session_name("TorrentFlux");
session_start();
header("Content-Type: text/html; charset=utf-8"); 
$cfg["user"] = isset($_SESSION['user'])?strtolower($_SESSION['user']):'';
include_once(ENGINE_ROOT.'include/settings.functions.php');
$usejs=getRequestVar('usejs');
// Create Connection.
$db = getdb();
loadSettings();

// Free space in MB
$cfg["free_space"] = @disk_free_space($cfg["path"])/(1024*1024);

// Path to where the torrent meta files will be stored... usually a sub of $cfg["path"]
// also, not the '.' to make this a hidden directory
$cfg["torrent_file_path"] = $cfg["path"].".torrents/";
if($_SERVER['SCRIPT_FILENAME']==ENGINE_ROOT.'login.php' || ($_SERVER['argv'][0]==ENGINE_ROOT.'cronwork.php')){
}else{
Authenticate();
include_once("language/".$cfg['language_file']);
include_once("themes/".$cfg['theme']."/index.php");
}

AuditAction($cfg["constants"]["hit"], $_SERVER['PHP_SELF']);
PruneDB();

// is there a stat and torrent dir?  If not then it will create it.
checkTorrentPath();

//**********************************************************************************
// START FUNCTIONS HERE
//**********************************************************************************

//*********************************************************
function getLinkSortOrder($lid)
{
	global $db;

	// Get Current sort order index of link with this link id:
	$sql="SELECT sort_order FROM tf_links WHERE lid=$lid";
	$rtnValue=$db->GetOne($sql);
	showError($db,$sql);

	return $rtnValue;
}

//*********************************************************
// avddelete()
function avddelete($file){
	$file = html_entity_decode($file, ENT_QUOTES);
	chmod($file,0777);
	if (@is_dir($file)){
		$handle = @opendir($file);
		while($filename = readdir($handle)){
			if ($filename != "." && $filename != ".."){
				avddelete($file."/".$filename);
			}
		}
		closedir($handle);
		@rmdir($file);
	}else{
		@unlink($file);
	}
}
function Uid2Username($uid){
	global $db;
		if(!is_numeric($uid)){
			showmessage('uid is not a number',1);
		}elseif($uid==0){
			showmessage('uid should not equal to 0');
		}
	$sql='SELECT `user_id` FROM `tf_users` WHERE `uid`=\''.$uid.'\'';
	$username=$db->GetOne($sql);
	showError($db,$sql);
	return $username;
}
function GrabUserData($uid){
	global $db;
		if(!is_numeric($uid)){
			showmessage('uid is not a number',1);
		}elseif($uid==0){
			showmessage('uid should not equal to 0');
		}
	$sql='SELECT * FROM `tf_users` WHERE `uid`=\''.$uid.'\'';
	$recordset = $db->Execute($sql);
	showError($db,$sql);
	$UsrData=$recordset->FetchRow();
	$UsrData['user_level_text']=ShowUserType($UsrData['user_level']);
	$UsrData['last_visit_text']=date("m/d/Y H:i:s",$UsrData['last_visit'] );
	$UsrData['time_created_text']=date("m/d/Y H:i:s",$UsrData['time_created'] );
	return $UsrData;
}
//*********************************************************
// Authenticate()
function Authenticate(){
	global $cfg, $db;

	$create_time = time();
	if(!isset($_SESSION['user'])){
		header('location: login.php');
		exit();
	}

	if ($_SESSION['user'] == md5($cfg["pagetitle"])){
		// user changed password and needs to login again
		header('location: logout.php');
		exit();
	}

	$sql = "SELECT uid, hits, hide_offline, theme, language_file ,allow_view_other_torrent,transferlimit_period,transferlimit_number FROM tf_users WHERE user_id=".$db->qstr($cfg['user']);
	$recordset = $db->Execute($sql);
	showError($db, $sql);

	if($recordset->RecordCount() != 1){
		AuditAction($cfg["constants"]["error"], "FAILED AUTH: ".$cfg['user']);
		session_destroy();
		header('location: login.php');
		exit();
	}

	list($uid, $hits, $cfg['hide_offline'], $cfg['theme'], $cfg['language_file'],$allow_view_other_torrent,$cfg['transferlimit_period'],$cfg['transferlimit_number']) = $recordset->FetchRow();
	$cfg['uid']=$uid;
	// Check for valid theme
	if (!ereg('^[^./][^/]*$', $cfg['theme'])){
		AuditAction($cfg["constants"]["error"], "THEME VARIABLE CHANGE ATTEMPT: ".$cfg['theme']." from ".$cfg['user']);
		$cfg['theme'] = $cfg["default_theme"];
	}

	// Check for valid language file
	if(!ereg('^[^./][^/]*$', $cfg['language_file'])){
		AuditAction($cfg["constants"]["error"], "LANGUAGE VARIABLE CHANGE ATTEMPT: ".$cfg['language_file']." from ".$cfg['user']);
		$cfg['language_file'] = $cfg["default_language"];
	}

	if (!is_dir("themes/".$cfg['theme'])){
		$cfg['theme'] = $cfg["default_theme"];
	}

	// Check for valid language file
	if (!is_file("language/".$cfg['language_file'])){
		$cfg['language_file'] = $cfg["default_language"];
	}

	$hits++;

	$sql = 'select * from tf_users where uid = '.$uid;
	$rs = $db->Execute($sql);
	showError($db, $sql);

	$rec = array(
					'hits' => $hits,
					'last_visit' => $create_time,
					'theme' => $cfg['theme'],
					'language_file' => $cfg['language_file']
				);
	$sql = $db->GetUpdateSQL($rs, $rec);

	$result = $db->Execute($sql);
	showError($db,$sql);
	$GLOBALS['isadmin']=IsAdmin();
	$GLOBALS['myuid']=$uid;
	$GLOBALS['allow_view_other_torrent']=$GLOBALS['isadmin'] || $allow_view_other_torrent?1:0;
}
function AdminCheck(){
		if(!$GLOBALS['isadmin']){
			// the user probably hit this page direct
			AuditAction($cfg["constants"]["access_denied"], $_SERVER['PHP_SELF']);
			header("location: index.php");
			exit();
		}
}

//*********************************************************
// SaveMessage
function SaveMessage($to_user, $from_user, $message, $to_all=0, $force_read=0)
{
	global $_SERVER, $cfg, $db;

	$message = str_replace(array("'"), "", $message);

	$create_time = time();

	$sTable = 'tf_messages';
	if($to_all == 1)
	{
		$message .= "\n\n__________________________________\n*** "._MESSAGETOALL." ***";
		$sql = 'select user_id from tf_users';
		$result = $db->Execute($sql);
		showError($db,$sql);

		while($row = $result->FetchRow())
		{
			$rec = array(
						'to_user' => $row['user_id'],
						'from_user' => $from_user,
						'message' => $message,
						'IsNew' => 1,
						'ip' => $cfg['ip'],
						'time' => $create_time,
						'force_read' => $force_read
						);

			$sql = $db->GetInsertSql($sTable, $rec);

			$result2 = $db->Execute($sql);
			showError($db,$sql);
		}
	}
	else
	{
		// Only Send to one Person
		$rec = array(
					'to_user' => $to_user,
					'from_user' => $from_user,
					'message' => $message,
					'IsNew' => 1,
					'ip' => $cfg['ip'],
					'time' => $create_time,
					'force_read' => $force_read
					);
		$sql = $db->GetInsertSql($sTable, $rec);
		$result = $db->Execute($sql);
		showError($db,$sql);
		$sql='UPDATE `tf_users` SET `newpm`=\'1\' WHERE `user_id`=\''.$to_user.'\' LIMIT 1';
		$result = $db->Execute($sql);
		showError($db,$sql);
	}

}

//*********************************************************
function addNewUser($newUser, $pass1, $userType=1){
	global $cfg, $db;
	AdminCheck();
		if($newUser==''){
			showmessage(_USERIDREQUIRED,1);
		}
		if(strlen($pass1)<6){
			showmessage(_PASSWORDLENGTH,1);
		}
		if (IsUser($newUser)){
			showmessage(_TRYDIFFERENTUSERID.$newUser._HASBEENUSED,1);
		}
	$create_time = time();
	$record = array(
					'user_id'=>strtolower($newUser),
					'password'=>md5($pass1),
					'hits'=>0,
					'time_created'=>$create_time,
					'user_level'=>$userType,
					'hide_offline'=>"0",
					'theme'=>$cfg["default_theme"],
					'language_file'=>$cfg["default_language"]
					);

	$sTable = 'tf_users';
	$sql = $db->GetInsertSql($sTable, $record);
	$result = $db->Execute($sql);
	showError($db,$sql);
}

//*********************************************************
function PruneDB()
{
	global $cfg, $db;

	// Prune LOG
	$testTime = time()-($cfg['days_to_keep'] * 86400); // 86400 is one day in seconds
	$sql = "delete from tf_log where time < " . $db->qstr($testTime);
	$result = $db->Execute($sql);
	showError($db,$sql);
	unset($result);

	$testTime = time()-($cfg['minutes_to_keep'] * 60);
	$sql = "delete from tf_log where time < " . $db->qstr($testTime). " and action=".$db->qstr($cfg["constants"]["hit"]);
	$result = $db->Execute($sql);
	showError($db,$sql);
	unset($result);
}

//*********************************************************
function IsOnline($user)
{
	global $cfg, $db;

	$online = false;

	$sql = "SELECT count(*) FROM tf_log WHERE user_id=" . $db->qstr($user)." AND action=".$db->qstr($cfg["constants"]["hit"]);

	$number_hits = $db->GetOne($sql);
	showError($db,$sql);

	if ($number_hits > 0)
	{
		$online = true;
	}

	return $online;
}

//*********************************************************
function IsUser($user)
{
	global $cfg, $db;

	$isUser = false;

	$sql = "SELECT count(*) FROM tf_users WHERE user_id=".$db->qstr($user);
	$number_users = $db->GetOne($sql);

	if ($number_users > 0)
	{
		$isUser = true;
	}

	return $isUser;
}

//*********************************************************
function getOwner($file){
	global $cfg, $db;
	$rtnValue = "n/a";
	// Check log to see what user has a history with this file
	$sql = "SELECT user_id FROM tf_log WHERE file=".$db->qstr($file)." AND (action=".$db->qstr($cfg["constants"]["file_upload"])." OR action=".$db->qstr($cfg["constants"]["url_upload"])." OR action=".$db->qstr($cfg["constants"]["reset_owner"]).") ORDER  BY time DESC";
	$user_id = $db->GetOne($sql);
	$rtnValue =($user_id != "")? $user_id:resetOwner($file);
	return $rtnValue;
}

//*********************************************************
function resetOwner($file)
{
	global $cfg, $db;
	include_once("AliasFile.php");

	// log entry has expired so we must renew it
	$rtnValue = "";

	$alias = getAliasName($file).".stat";

	if(file_exists($cfg["torrent_file_path"].$alias))
	{
		$af = new AliasFile($cfg["torrent_file_path"].$alias);

		if (IsUser($af->torrentowner))
		{
			// We have an owner!
			$rtnValue = $af->torrentowner;
		}
		else
		{
			// no owner found, so the super admin will now own it
			$rtnValue = GetSuperAdmin();
		}

		$host_resolved = $cfg['ip'];
		$create_time = time();

		$rec = array(
						'user_id' => $rtnValue,
						'file' => $file,
						'action' => $cfg["constants"]["reset_owner"],
						'ip' => $cfg['ip'],
						'ip_resolved' => $host_resolved,
						'user_agent' => $_SERVER['HTTP_USER_AGENT'],
						'time' => $create_time
					);

		$sTable = 'tf_log';
		$sql = $db->GetInsertSql($sTable, $rec);

		// add record to the log
		$result = $db->Execute($sql);
		showError($db,$sql);
	}

	return $rtnValue;
}

//*********************************************************
function getCookie($cid)
{
	global $cfg, $db;

	$rtnValue = "";

	$sql = "SELECT host, data FROM tf_cookies WHERE cid=".$cid;
	$rtnValue = $db->GetAll($sql);

	return $rtnValue[0];
}

//*********************************************************
function getAllCookies($uid)
{
	global $cfg, $db;

	$rtnValue = "";

	$sql = "SELECT c.cid, c.host, c.data FROM tf_cookies AS c, tf_users AS u WHERE u.uid=c.uid AND u.user_id='" . $uid . "' order by host";
	$rtnValue = $db->GetAll($sql);

	return $rtnValue;
}

// ***************************************************************************
// Delete Cookie Host Information
function deleteCookieInfo($cid)
{
	global $db;
	$sql = "delete from tf_cookies where cid=".$cid;
	$result = $db->Execute($sql);
	showError($db,$sql);
}

// ***************************************************************************
// addCookieInfo - Add New Cookie Host Information
function addCookieInfo( $newCookie )
{
	global $db, $cfg;
	// Get uid of user
	$sql = "SELECT uid FROM tf_users WHERE user_id = '" . $cfg["user"] . "'";
	$uid = $db->GetOne( $sql );
	$sql = "INSERT INTO tf_cookies ( uid, host, data ) VALUES ( " . $uid . ", " . $db->qstr($newCookie["host"]) . ", " . $db->qstr($newCookie["data"]) . " )";
	$db->Execute( $sql );
	showError( $db, $sql );
}

// ***************************************************************************
// modCookieInfo - Modify Cookie Host Information
function modCookieInfo($cid, $newCookie)
{
	global $db;
	$sql = "UPDATE tf_cookies SET host='" . $newCookie["host"] . "', data='" . $newCookie["data"] . "' WHERE cid=" . $cid;
	$db->Execute($sql);
	showError($db,$sql);
}

//*********************************************************
function getSite($lid)
{
	global $cfg, $db;

	$rtnValue = "";

	$sql = "SELECT sitename FROM tf_links WHERE lid=".$lid;
	$rtnValue = $db->GetOne($sql);

	return $rtnValue;
}

//*********************************************************
function getLink($lid)
{
	global $cfg, $db;

	$rtnValue = "";

	$sql = "SELECT url FROM tf_links WHERE lid=".$lid;
	$rtnValue = $db->GetOne($sql);

	return $rtnValue;
}

//*********************************************************
function getRSS($rid)
{
	global $cfg, $db;

	$rtnValue = "";

	$sql = "SELECT url FROM tf_rss WHERE rid=".$rid;
	$rtnValue = $db->GetOne($sql);

	return $rtnValue;
}

//*********************************************************
function IsOwner($user, $owner)
{
	$rtnValue = false;

	if (strtolower($user) == strtolower($owner))
	{
		$rtnValue = true;
	}

	return $rtnValue;
}

//*********************************************************
function GetActivityCount($user="",$timestamp=0)
{
	global $cfg, $db;

	$count = 0;
	$for_user = "";

	if ($user != "")
	{
		$for_user = "user_id=".$db->qstr($user)." AND ";
	}

	if(intval($timestamp) && $timestamp >0){
		$foruser.="time > $timestamp AND ";
	}
	$sql = "SELECT count(*) FROM tf_log WHERE ".$for_user."(action=".$db->qstr($cfg["constants"]["file_upload"])." OR action=".$db->qstr($cfg["constants"]["url_upload"]).")";
	$count = $db->GetOne($sql);

	return $count;
}

//*********************************************************
function GetSpeedValue($inValue)
{
	$rtnValue = 0;
	$arTemp = split(" ", trim($inValue));

	if (is_numeric($arTemp[0]))
	{
		$rtnValue = $arTemp[0];
	}
	return $rtnValue;
}
function ShowUserType($typeid){
	if($typeid=='2'){
		return _SUPERADMIN;
	}elseif($typeid=='1'){
		return _ADMINISTRATOR;
	}else{
		return _NORMALUSER;
	}
}
// ***************************************************************************
// Is User Admin
// user is Admin if level is 1 or higher
function IsAdmin($user="")
{
	global $cfg, $db;

	$isAdmin = false;

	if($user == "")
	{
		$user = $cfg["user"];
	}

	$sql = "SELECT user_level FROM tf_users WHERE user_id=".$db->qstr($user);
	$user_level = $db->GetOne($sql);

	if ($user_level >= 1)
	{
		$isAdmin = true;
	}
	return $isAdmin;
}

// ***************************************************************************
// Is User SUPER Admin
// user is Super Admin if level is higher than 1
function IsSuperAdmin($user="")
{
	global $cfg, $db;

	$isAdmin = false;

	if($user == "")
	{
		$user = $cfg["user"];
	}

	$sql = "SELECT user_level FROM tf_users WHERE user_id=".$db->qstr($user);
	$user_level = $db->GetOne($sql);

	if ($user_level > 1)
	{
		$isAdmin = true;
	}
	return $isAdmin;
}


// ***************************************************************************
// Returns true if user has message from admin with force_read
function IsForceReadMsg()
{
	global $cfg, $db;
	$rtnValue = false;

	$sql = "SELECT count(*) FROM tf_messages WHERE to_user=".$db->qstr($cfg["user"])." AND force_read=1";
	$count = $db->GetOne($sql);
	showError($db,$sql);

	if ($count >= 1)
	{
		$rtnValue = true;
	}
	return $rtnValue;
}

// ***************************************************************************
// Get Message data in an array
function GetMessage($mid)
{
	global $cfg, $db;

	$rtnValue = array();

	if (is_numeric($mid))
	{
		$sql = "select from_user, message, ip, time, isnew, force_read from tf_messages where mid=".$mid." and to_user=".$db->qstr($cfg['user']);
		$rtnValue = $db->GetRow($sql);
		showError($db,$sql);
	}

	return $rtnValue;
}

// ***************************************************************************
// Get Themes data in an array
function GetThemes()
{
	$arThemes = array();
	$dir = "themes/";

	$handle = opendir($dir);
	while($entry = readdir($handle))
	{
		if (is_dir($dir.$entry) && ($entry != "." && $entry != ".."))
		{
			array_push($arThemes, $entry);
		}
	}
	closedir($handle);

	sort($arThemes);

	return $arThemes;
}

// ***************************************************************************
// Get Languages in an array
function GetLanguages()
{
	$arLanguages = array();
	$dir = "language/";

	$handle = opendir($dir);
	while($entry = readdir($handle))
	{
		if (is_file($dir.$entry) && (strcmp(strtolower(substr($entry, strlen($entry)-4, 4)), ".php") == 0))
		{
			array_push($arLanguages, $entry);
		}
	}
	closedir($handle);

	sort($arLanguages);

	return $arLanguages;
}

// ***************************************************************************
// Get Language name from file name
function GetLanguageFromFile($inFile)
{
	$rtnValue = "";

	$rtnValue = str_replace("lang-", "", $inFile);
	$rtnValue = str_replace(".php", "", $rtnValue);

	return $rtnValue;
}

// ***************************************************************************
// Delete Message
function DeleteMessage($mid)
{
	global $cfg, $db;

	$sql = "delete from tf_messages where mid=".$mid." and to_user=".$db->qstr($cfg['user']);
	$result = $db->Execute($sql);
	showError($db,$sql);
}


// ***************************************************************************
// Delete Link
function deleteOldLink($lid)
{
	global $db;
	// Get Current sort order index of link with this link id:
	$idx = getLinkSortOrder($lid);

	// Fetch all link ids and their sort orders where the sort order is greater
	// than the one we're removing - we need to shuffle each sort order down
	// one:
	$sql = "SELECT sort_order, lid FROM tf_links ";
	$sql .= "WHERE sort_order > ".$idx." ORDER BY sort_order ASC";
	$result = $db->Execute($sql);
	showError($db,$sql);
	$arLinks = $result->GetAssoc();

	// Decrement the sort order of each link:
	foreach($arLinks as $sid => $this_lid)
	{
		$sql="UPDATE tf_links SET sort_order=sort_order-1 WHERE lid=".$this_lid;
		$db->Execute($sql);
		showError($db,$sql);
	}

	// Finally delete the link:
	$sql = "DELETE FROM tf_links WHERE lid=".$lid;
	$result = $db->Execute($sql);
	showError($db,$sql);
}

// ***************************************************************************
// Delete RSS
function deleteOldRSS($rid)
{
	global $db;
	$sql = "delete from tf_rss where rid=".$rid;
	$result = $db->Execute($sql);
	showError($db,$sql);
}

// ***************************************************************************
// Delete User
function DeleteThisUser($uid){
	global $db,$cfg;
	AdminCheck();
	$uid=intval($uid);
	$sql = "SELECT `user_level`,`user_id` FROM tf_users WHERE uid = ".$uid;
	$result = $db->SelectLimit($sql, 1);
	$ar = $result->FetchRow();
	$user_id=$ar['user_id'];
	$user_level=$ar['user_level'];
	showError($db,$sql);
		if($user_level >= 2){
			showmessage('cannot delete '._SUPERADMIN,1);
		}elseif(!$user_id){
			showmessage(_user_not_found,1);
		}
	// delete any cookies this user may have had
	//$sql = "DELETE tf_cookies FROM tf_cookies, tf_users WHERE (tf_users.uid = tf_cookies.uid) AND tf_users.user_id=".$db->qstr($user_id);
	$sql = "DELETE FROM tf_cookies WHERE uid=".$uid;
	$result = $db->Execute($sql);
	showError($db,$sql);

	// Now cleanup any message this person may have had
	$sql = "DELETE FROM tf_messages WHERE to_user=".$db->qstr($user_id);
	$result = $db->Execute($sql);
	showError($db,$sql);

	// now delete the user from the table
	$sql = "DELETE FROM tf_users WHERE uid=".$uid;
	$result = $db->Execute($sql);
	showError($db,$sql);
	AuditAction($cfg["constants"]["admin"], _DELETE." "._USER.": ".$user_id);
}

// ***************************************************************************
// Update User -- used by admin
function updateThisUser($user_id, $org_user_id, $pass1, $userType, $hideOffline,$allow_view_other_torrent,$torrentlimit_period,$torrentlimit_number,$transferlimit_period,$transferlimit_number){
	global $db,$cfg;
	AdminCheck();
		if(IsUser($user_id) && ($user_id != $org_user_id)){
			showmessage(_TRYDIFFERENTUSERID.$user_id._HASBEENUSED,1);
		}
		if($user_id==''){
			showmessage(_USERIDREQUIRED,1);
		}
		if(strlen($pass1)<6 AND $pass1 !=''){
			showmessage(_PASSWORDLENGTH,1);
		}
	$hideOffline =$hideOffline?1: 0;
	$$allow_view_other_torrent=$allow_view_other_torrent?1:0;
	$torrentlimit_period=intval($torrentlimit_period);
	$torrentlimit_number=intval($torrentlimit_number);
	$transferlimit_period=intval($transferlimit_period);
	$transferlimit_number=intval($transferlimit_number);
	$sql = 'select * from tf_users where user_id = '.$db->qstr($org_user_id);
	$rs = $db->Execute($sql);
	showError($db,$sql);

	$rec = array();
	$rec['user_id'] = $user_id;
	$rec['user_level'] = $userType;
	$rec['hide_offline'] = $hideOffline;
	$rec['allow_view_other_torrent'] = $allow_view_other_torrent;
	$rec['torrentlimit_period'] = $torrentlimit_period;
	$rec['torrentlimit_number'] = $torrentlimit_number;
	$rec['transferlimit_period'] = $transferlimit_period;
	$rec['transferlimit_number'] = $transferlimit_number;

	if ($pass1 != ""){
		$rec['password'] = md5($pass1);
	}

	$sql = $db->GetUpdateSQL($rs, $rec);

	if ($sql != ""){
		$result = $db->Execute($sql);
		showError($db,$sql);
	}

	// if the original user id and the new id do not match, we need to update messages and log
	if ($user_id != $org_user_id){
		$sql = "UPDATE tf_messages SET to_user=".$db->qstr($user_id)." WHERE to_user=".$db->qstr($org_user_id);

		$result = $db->Execute($sql);
		showError($db,$sql);

		$sql = "UPDATE tf_messages SET from_user=".$db->qstr($user_id)." WHERE from_user=".$db->qstr($org_user_id);
		$result = $db->Execute($sql);
		showError($db,$sql);

		$sql = "UPDATE tf_log SET user_id=".$db->qstr($user_id)." WHERE user_id=".$db->qstr($org_user_id);
		$result = $db->Execute($sql);
		showError($db,$sql);
	}
        AuditAction($cfg["constants"]["admin"], _EDITUSER.": ".$user_id);
}

function CheckPorts($minport,$maxport){
		if($minport >$maxport) exit('port error');
		if($maxport-$minport>300) exit('too many port');
		for ($x=$minport;$x<=$maxport;$x++){
			echo $x.':';
			echo CheckPort($x)?'<img src="images/green.gif" />':'<img src="images/yellow.gif" />';
			echo '<br />';
		}
}
function CheckPort($port){
	$port=intval($port);
		if($port <=0) return false;
	$binding =  @fsockopen($_SERVER["SERVER_ADDR"], $port, $errno, $errstr, 10);
	return $binding?1:0;
}

// ***************************************************************************
// changeUserLevel Changes the Users Level
function changeUserLevel($user_id, $level)
{
	global $db;

	$sql='select * from tf_users where user_id = '.$db->qstr($user_id);
	$rs = $db->Execute($sql);
	showError($db,$sql);

	$rec = array('user_level'=>$level);
	$sql = $db->GetUpdateSQL($rs, $rec);
	$result = $db->Execute($sql);
	showError($db,$sql);
}
//****************************************************************************
// validateFile -- Validates the existance of a file and returns the status image
//****************************************************************************
function validateFile($the_file){
	return isFile($the_file)?1:0;
}

//****************************************************************************
// validatePath -- Validates TF Path and Permissions
//****************************************************************************
function validatePath($path){
	return is_dir($path) && is_writable($path)?1:0;
}
// ***************************************************************************
// Mark Message as Read
function MarkMessageRead($mid)
{
	global $cfg, $db;

	$sql = 'select * from tf_messages where mid = '.$mid;
	$rs = $db->Execute($sql);
	showError($db,$sql);

	$rec = array('IsNew'=>0,
			 'force_read'=>0);

	$sql = $db->GetUpdateSQL($rs, $rec);
	$db->Execute($sql);
	showError($db,$sql);
}

//**************************************************************************
// alterLink()
// This function updates the database and alters the selected links values
function alterLink($lid,$newLink,$newSite)
{
	global $cfg, $db;

	$sql = "UPDATE tf_links SET url='".$newLink."',`sitename`='".$newSite."' WHERE `lid`=".$lid;
	$db->Execute($sql);
	showError($db,$sql);
}

// ***************************************************************************
// addNewLink - Add New Link
function addNewLink($newLink,$newSite)
{
	global $db;
	// Link sort order index:
	$idx = -1;

	// Get current highest link index:
	$sql = "SELECT sort_order FROM tf_links ORDER BY sort_order DESC";
	$result = $db->SelectLimit($sql, 1);
	showError($db, $sql);

	if($result->fields === false)
	{
		// No links currently in db:
		$idx = 0;
	}
	else
	{
		$idx = $result->fields["sort_order"]+1;
	}

	$rec = array
	(
		'url'=>$newLink,
		'sitename'=>$newSite,
		'sort_order'=>$idx
	);
	$sTable = 'tf_links';
	$sql = $db->GetInsertSql($sTable, $rec);
	$db->Execute($sql);
	showError($db,$sql);
}


// ***************************************************************************
// addNewRSS - Add New RSS Link
function addNewRSS($newRSS)
{
	global $db;
	$rec = array('url'=>$newRSS);
	$sTable = 'tf_rss';
	$sql = $db->GetInsertSql($sTable, $rec);
	$db->Execute($sql);
	showError($db,$sql);
}

// ***************************************************************************
// UpdateUserProfile
function UpdateUserProfile($user_id, $pass1, $hideOffline, $theme, $language){
	global $cfg, $db;
	if (empty($hideOffline) || $hideOffline == "" || !isset($hideOffline)){
		$hideOffline = "0";
	}
	// update values
	$rec = array();
	if ($pass1 != ""){
		$rec['password'] = md5($pass1);
		AuditAction($cfg["constants"]["update"], _PASSWORD);
	}
	$sql = 'select * from tf_users where user_id = '.$db->qstr($user_id);
	$rs = $db->Execute($sql);
	showError($db,$sql);
	$rec['hide_offline'] = $hideOffline;
	$rec['theme'] = $theme;
	$rec['language_file'] = $language;
	$sql = $db->GetUpdateSQL($rs, $rec);
	$result = $db->Execute($sql);
}


// ***************************************************************************
// Get Users in an array
function GetUsers()
{
	global $cfg, $db;

	$user_array = array();

	$sql = "select user_id from tf_users order by user_id";
	$user_array = $db->GetCol($sql);
	showError($db,$sql);
	return $user_array;
}

// ***************************************************************************
// Get Super Admin User ID as a String
function GetSuperAdmin()
{
	global $cfg, $db;

	$rtnValue = "";

	$sql = "select user_id from tf_users WHERE user_level=2";
	$rtnValue = $db->GetOne($sql);
	showError($db,$sql);
	return $rtnValue;
}

// ***************************************************************************
// Get Links in an array
function GetLinks()
{
	global $cfg, $db;

	$link_array = array();

	$link_array = $db->GetAssoc("SELECT lid, url, sitename, sort_order FROM tf_links ORDER BY sort_order");
	return $link_array;
}

// ***************************************************************************
// Get RSS Links in an array
function GetRSSLinks()
{
	global $cfg, $db;

	$link_array = array();

	$sql = "SELECT rid, url FROM tf_rss ORDER BY rid";
	$link_array = $db->GetAssoc($sql);
	showError($db,$sql);

	return $link_array;
}

// ***************************************************************************
// Build Search Engine Drop Down List
function buildSearchEngineDDL($selectedEngine = 'PirateBay', $autoSubmit = false)
{
	$output = "<select name=\"searchEngine\" ";
	if ($autoSubmit)
	{
		 $output .= "onchange=\"this.form.submit();\" ";
	}
	$output .= " STYLE=\"width: 125px\">";

	$handle = opendir("./searchEngines");
	while($entry = readdir($handle))
	{
		$entrys[] = $entry;
	}
	natcasesort($entrys);

	foreach($entrys as $entry)
	{
		if ($entry != "." && $entry != ".." && substr($entry, 0, 1) != ".")
			if(strpos($entry,"Engine.php"))
			{
				$tmpEngine = str_replace("Engine",'',substr($entry,0,strpos($entry,".")));
				$output .= "<option";
				if ($selectedEngine == $tmpEngine)
				{
					$output .= " selected";
				}
				$output .= ">".str_replace("Engine",'',substr($entry,0,strpos($entry,".")))."</option>";
			}
	}
	$output .= "</select>\n";

	return $output;
}

// ***************************************************************************
// Build Search Engine Links
function buildSearchEngineLinks($selectedEngine = 'PirateBay')
{
	global $cfg;

	$settingsNeedsSaving = false;
	$settings['searchEngineLinks'] = Array();

	$output = '';

	if( (!array_key_exists('searchEngineLinks', $cfg)) || (!is_array($cfg['searchEngineLinks'])))
	{
		saveSettings($settings);
	}

	$handle = opendir("./searchEngines");
	while($entry = readdir($handle))
	{
		$entrys[] = $entry;
	}
	natcasesort($entrys);

	foreach($entrys as $entry)
	{
		if ($entry != "." && $entry != ".." && substr($entry, 0, 1) != ".")
			if(strpos($entry,"Engine.php"))
			{
				$tmpEngine = str_replace("Engine",'',substr($entry,0,strpos($entry,".")));

				if(array_key_exists($tmpEngine,$cfg['searchEngineLinks']))
				{
					$hreflink = $cfg['searchEngineLinks'][$tmpEngine];
					$settings['searchEngineLinks'][$tmpEngine] = $hreflink;
				}
				else
				{
					$hreflink = getEngineLink($tmpEngine);
					$settings['searchEngineLinks'][$tmpEngine] = $hreflink;
					$settingsNeedsSaving = true;
				}

				if (strlen($hreflink) > 0)
				{
					$output .=  "<a href=\"http://".$hreflink."/\" target=\"_blank\">";
					if ($selectedEngine == $tmpEngine)
					{
						$output .= "<b>".$hreflink."</b>";
					}
					else
					{
						$output .= $hreflink;
					}
					$output .= "</a><br>\n";
				}
			}
	}

	if ( count($settings['searchEngineLinks'],COUNT_RECURSIVE) <> count($cfg['searchEngineLinks'],COUNT_RECURSIVE))
	{
		$settingsNeedsSaving = true;
	}

	if ($settingsNeedsSaving)
	{
		natcasesort($settings['searchEngineLinks']);

		saveSettings($settings);
	}

	return $output;
}
function getEngineLink($searchEngine)
{
	$tmpLink = '';
	$engineFile = 'searchEngines/'.$searchEngine.'Engine.php';
	if (is_file($engineFile))
	{
		$fp = @fopen($engineFile,'r');
		if ($fp)
		{
			$tmp = fread($fp, filesize($engineFile));
			@fclose( $fp );

			$tmp = substr($tmp,strpos($tmp,'$this->mainURL'),100);
			$tmp = substr($tmp,strpos($tmp,"=")+1);
			$tmp = substr($tmp,0,strpos($tmp,";"));
			$tmpLink = trim(str_replace(array("'","\""),"",$tmp));
		}
	}
	return $tmpLink;
}

// ***************************************************************************
// ***************************************************************************
// Display Functions


// ***************************************************************************
// ***************************************************************************
// Display the header portion of admin views
function DisplayHead($subTopic, $showButtons=true, $refresh="", $percentdone="")
{
	global $cfg;
	?>

	<html>
	<HEAD>
		<TITLE><?php echo $percentdone.$cfg["pagetitle"] ?></TITLE>
		<link rel="icon" href="images/favicon.ico" type="image/x-icon" />
		<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
		<LINK REL="StyleSheet" HREF="themes/<?php echo $cfg['theme'] ?>/style.css" TYPE="text/css">
		<META HTTP-EQUIV="Pragma" CONTENT="no-cache" charset="<?php echo _CHARSET ?>">

	<?php
	if ($refresh != "")
	{
		echo "<meta http-equiv=\"REFRESH\" content=\"".$refresh."\">";
	}
	?>
	</HEAD>

	<body topmargin="8" leftmargin="5" bgcolor="<?php echo $cfg["main_bgcolor"] ?>">

	<div align="center">
	<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>

	<table border="1" bordercolor="<?php echo $cfg["table_border_dk"] ?>" cellpadding="4" cellspacing="0">
	<tr>
		<td bgcolor="<?php echo $cfg["main_bgcolor"] ?>" background="themes/<?php echo $cfg['theme'] ?>/images/bar.gif">
		<?php DisplayTitleBar($cfg["pagetitle"]." - ".$subTopic, $showButtons); ?>
		</td>
	</tr>
	<tr>
	<td bgcolor="<?php echo $cfg["table_header_bg"] ?>">
	<div align="center">

	<table width="100%" bgcolor="<?php echo $cfg["body_data_bg"] ?>">
	 <tr><td>
<?php
}


// ***************************************************************************
// ***************************************************************************
// Display the footer portion
function DisplayFoot($showReturn=true)
{
	global $cfg;
	?>
	 </td></tr>
	</table>
<?php
	if ($showReturn)
	{
		echo "[<a href=\"index.php\">"._RETURNTOTORRENTS."</a>]";
		echo "</form>";
	}
?>
	</div>
	</td>
	</tr>
	</table>
<?php
	echo DisplayTorrentFluxLink();
?>

		</td>
	</tr>
	</table>
	</div>

   </body>
  </html>

	<?php
}


// ***************************************************************************
// ***************************************************************************
// Dipslay TF Link and Version
function DisplayTorrentFluxLink()
{
	global $cfg;

	echo "<div align=\"right\">";
	echo "<a href=\"http://www.torrentflux.com\" target=\"_blank\"><font class=\"tinywhite\">TorrentFlux ".$cfg["version"]."</font></a>&nbsp;&nbsp;";
	echo "</div>";
}



function saveXfer($user, $down, $up){
	global $db;
	//increase performance by saving bytes to MB
	$down=$down/1000000;
	$up=$up/1000000;
	$sql = 'SELECT 1 FROM tf_xfer WHERE user = "'.$user.'" AND date = '.$db->DBDate(time());
		if ($db->GetRow($sql)) {
			$sql = 'UPDATE tf_xfer SET download = download+'.($down+0).', upload = upload+'.($up+0).' WHERE user = "'.$user.'" AND date = '.$db->DBDate(time());
			$db->Execute($sql);
			showError($db,$sql);
		} else {
			showError($db,$sql);
			$sql = 'INSERT INTO tf_xfer SET user = "'.$user.'", date = '.$db->DBDate(time()).', download = '.($down+0).', upload = '.($up+0);
			$db->Execute($sql);
			showError($db,$sql);
		}
}




// ***************************************************************************
// ***************************************************************************
// Dipslay Title Bar
// 2004-12-09 PFM: now using adodb.
function DisplayTitleBar($pageTitleText, $showButtons=true)
{
	global $cfg, $db;
	?>
		<table width="100%" cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td align="left"><font class="title"><?php echo $pageTitleText ?></font></td>

	<?php
	if ($showButtons)
	{
		echo "<td align=right>";
		// Top Buttons
		echo "&nbsp;&nbsp;";

		echo "<a href=\"index.php\"><img src=\"themes/".$cfg['theme']."/images/home.gif\" width=49 height=13 title=\""._TORRENTS."\" border=0></a>&nbsp;";
		echo "<a href=\"dir.php\"><img src=\"themes/".$cfg['theme']."/images/directory.gif\" width=49 height=13 title=\""._DIRECTORYLIST."\" border=0></a>&nbsp;";
		echo "<a href=\"history.php\"><img src=\"themes/".$cfg['theme']."/images/history.gif\" width=49 height=13 title=\""._UPLOADHISTORY."\" border=0></a>&nbsp;";
		echo "<a href=\"profile.php\"><img src=\"themes/".$cfg['theme']."/images/profile.gif\" width=49 height=13 title=\""._MYPROFILE."\" border=0></a>&nbsp;";

		// Does the user have messages?
		$sql = "select count(*) from tf_messages where to_user='".$cfg['user']."' and IsNew=1";

		$number_messages = $db->GetOne($sql);
		showError($db,$sql);
		if ($number_messages > 0)
		{
			// We have messages
			$message_image = "themes/".$cfg['theme']."/images/messages_on.gif";
		}
		else
		{
			// No messages
			$message_image = "themes/".$cfg['theme']."/images/messages_off.gif";
		}

		echo "<a href=\"readmsg.php\"><img src=\"".$message_image."\" width=49 height=13 title=\""._MESSAGES."\" border=0></a>";

		if(IsAdmin())
		{
			echo "&nbsp;<a href=\"admin.php\"><img src=\"themes/".$cfg['theme']."/images/admin.gif\" width=49 height=13 title=\""._ADMINISTRATION."\" border=0></a>";
		}

		echo "&nbsp;<a href=\"logout.php\"><img src=\"images/logout.gif\" width=13 height=12 title=\"Logout\" border=0></a>";
	}
?>
			</td>
		</tr>
		</table>
<?php
}

function listPM(){
	global $db,$cfg;
	$sql = "SELECT u.uid, m.mid, m.from_user, m.message, m.IsNew, m.ip, m.time, m.force_read FROM tf_messages m LEFT JOIN tf_users u ON u.user_id =m.from_user  WHERE to_user=".$db->qstr($cfg['user'])." ORDER BY time";
    $result = $db->Execute($sql);
    showError($db,$sql);
    while($row = $result->FetchRow()){
        $row['mail_image'] =($row['new'] == 1)? "images/new_message.gif":"images/old_message.gif";
        $row['display_message'] = check_html($row['message'], "nohtml");
        if(strlen($row['display_message']) >= 40) { // needs to be trimmed
            $row['display_message'] = substr($row['display_message'], 0, 39);
            $row['display_message'] .= "..";
        }
		$row['time_text']=date(_DATETIMEFORMAT, $row['time']);
		$resulta[]=$row;
	}
	return $resulta;
}


function torrentid2torrentname($id){
	global $db;
	$id=intval($id);
	$sql = "select `torrent` from `tf_torrents` where `id`='".$id."' ";
	$name = $db->GetOne($sql);
	showError($db,$sql);
	return $name;
}
// ***************************************************************************
// ***************************************************************************
// Removes HTML from Messages
function check_html ($str, $strip="")
{
	/* The core of this code has been lifted from phpslash */
	/* which is licenced under the GPL. */
	if ($strip == "nohtml")
	{
		$AllowableHTML=array('');
	}
	$str = stripslashes($str);
	$str = eregi_replace("<[[:space:]]*([^>]*)[[:space:]]*>",'<\\1>', $str);
			// Delete all spaces from html tags .
	$str = eregi_replace("<a[^>]*href[[:space:]]*=[[:space:]]*\"?[[:space:]]*([^\" >]*)[[:space:]]*\"?[^>]*>",'<a href="\\1">', $str);
			// Delete all attribs from Anchor, except an href, double quoted.
	$str = eregi_replace("<[[:space:]]* img[[:space:]]*([^>]*)[[:space:]]*>", '', $str);
		// Delete all img tags
	$str = eregi_replace("<a[^>]*href[[:space:]]*=[[:space:]]*\"?javascript[[:punct:]]*\"?[^>]*>", '', $str);
		// Delete javascript code from a href tags -- Zhen-Xjell @ http://nukecops.com
	$tmp = "";

	while (ereg("<(/?[[:alpha:]]*)[[:space:]]*([^>]*)>",$str,$reg))
	{
		$i = strpos($str,$reg[0]);
		$l = strlen($reg[0]);
		if ($reg[1][0] == "/")
		{
			$tag = strtolower(substr($reg[1],1));
		}
		else
		{
			$tag = strtolower($reg[1]);
		}
		if ($a = $AllowableHTML[$tag])
		{
			if ($reg[1][0] == "/")
			{
				$tag = "</$tag>";
			}
			elseif (($a == 1) || ($reg[2] == ""))
			{
				$tag = "<$tag>";
			}
			else
			{
			  # Place here the double quote fix function.
			  $attrb_list=delQuotes($reg[2]);
			  // A VER
			  $attrb_list = ereg_replace("&","&amp;",$attrb_list);
			  $tag = "<$tag" . $attrb_list . ">";
			} # Attribs in tag allowed
		}
		else
		{
			$tag = "";
		}
		$tmp .= substr($str,0,$i) . $tag;
		$str = substr($str,$i+$l);
	}
	$str = $tmp . $str;
	return $str;
}


// ***************************************************************************
// ***************************************************************************
// Checks for the location of the torrents
// If it does not exist, then it creates it.
function checkTorrentPath()
{
	global $cfg;
	// is there a stat and torrent dir?
	if (!@is_dir($cfg["torrent_file_path"]) && is_writable($cfg["path"]))
	{
		//Then create it
		@mkdir($cfg["torrent_file_path"], 0777);
	}
}

// ***************************************************************************
// ***************************************************************************
// Returns the drive space used as a percentage i.e 85 or 95
function getDriveSpace($drive)
{
	$percent = 0;

	if (is_dir($drive))
	{
		$dt = disk_total_space($drive);
		$df = disk_free_space($drive);

		$percent = round((($dt - $df)/$dt) * 100);
	}
	return $percent;
}

// ***************************************************************************
// ***************************************************************************
// Display the Drive Space Graphical Bar
function displayDriveSpaceBar($drivespace)
{
	global $cfg;
	$freeSpace = "";

	if ($drivespace > 20)
	{
		$freeSpace = " (".formatFreeSpace($cfg["free_space"])." Free)";
	}
?>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr nowrap>
		<td width="2%"><div class="tiny"><?php echo _STORAGE ?>:</div></td>
		<td width="80%">
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td background="themes/<?php echo $cfg['theme'] ?>/images/proglass.gif" width="<?php echo $drivespace ?>%"><div class="tinypercent" align="center"><?php echo $drivespace."%".$freeSpace ?></div></td>
				<td background="themes/<?php echo $cfg['theme'] ?>/images/noglass.gif" width="<?php echo (100 - $drivespace) ?>%"><img src="images/blank.gif" width="1" height="3" border="0"></td>
			</tr>
			</table>
		</td>
	</tr>
	</table>
<?php
}

// ***************************************************************************
// ***************************************************************************
// Convert free space to GB or MB depending on size
function formatFreeSpace($freeSpace)
{
	$rtnValue = "";
	if ($freeSpace > 1024)
	{
		$rtnValue = number_format($freeSpace/1024, 2)." GB";
	}
	else
	{
		$rtnValue = number_format($freeSpace, 2)." MB";
	}

	return $rtnValue;
}

//**************************************************************************
// getFileFilter()
// Returns a string used as a file filter.
// Takes in an array of file types.
function getFileFilter($inArray)
{
	$filter = "(\.".strtolower($inArray[0]).")|"; // used to hold the file type filter
	$filter .= "(\.".strtoupper($inArray[0]).")";
	// Build the file filter
	for($inx = 1; $inx < sizeof($inArray); $inx++)
	{
		$filter .= "|(\.".strtolower($inArray[$inx]).")";
		$filter .= "|(\.".strtoupper($inArray[$inx]).")";
	}
	$filter .= "$";
	return $filter;
}


//**************************************************************************
// getAliasName()
// Create Alias name for Text file and Screen Alias
function getAliasName($inName){
	$replaceItems = array(" ", ".", "-", "[", "]", "(", ")", "#", "&", "@");
	$alias = str_replace($replaceItems, "_", $inName);
	$alias = strtolower($alias);
	$alias = str_replace("_torrent", "", $alias);
	return $alias;
}


//**************************************************************************
// cleanFileName()
// Remove bad characters that cause problems
function cleanFileName($inName){
	$replaceItems = array("?", "&", "'", "\"", "+", "@");
	$cleanName = str_replace($replaceItems, "", $inName);
	$cleanName = ltrim($cleanName, "-");
	$cleanName = preg_replace("/[^0-9a-z.]+/i",'_', $cleanName);
	return $cleanName;
}

//**************************************************************************
// usingTornado()
// returns true if client is tornado
function usingTornado()
{
	return true;
}

//**************************************************************************
// cleanURL()
// split on the "*" coming from Varchar URL
function cleanURL($url)
{
	$rtnValue = $url;
	$arURL = explode("*", $url);

	if (sizeof($arURL) > 1)
	{
		$rtnValue = $arURL[1];
	}

	return $rtnValue;
}

// -------------------------------------------------------------------
// FetchTorrent() method to get data from URL
// Has support for specific sites
// -------------------------------------------------------------------
function FetchTorrent($url)
{
	global $cfg, $db;
	ini_set("allow_url_fopen", "1");
	ini_set("user_agent", $_SERVER["HTTP_USER_AGENT"]);

	$rtnValue = "";

	$domain  = parse_url( $url );

	if( strtolower( substr( $domain["path"], -8 ) ) != ".torrent" )
	{
		// Check know domain types
		if( strpos( strtolower ( $domain["host"] ), "mininova" ) !== false )
		{
			// Sample (http://www.mininova.org/rss.xml):
			// http://www.mininova.org/tor/2254847
			// <a href="/get/2281554">FreeLinux.ISO.iso.torrent</a>

			// If received a /tor/ get the required information
			if( strpos( $url, "/tor/" ) !== false )
			{
				// Get the contents of the /tor/ to find the real torrent name
				$html = FetchHTML( $url );

				// Check for the tag used on mininova.org
				if( preg_match( "/<a href=\"\/get\/[0-9].[^\"]+\">(.[^<]+)<\/a>/i", $html, $html_preg_match ) )
				{
					// This is the real torrent filename
					$cfg["save_torrent_name"] = $html_preg_match[1];
				}

				// Change to GET torrent url
				$url = str_replace( "/tor/", "/get/", $url );
			}

			// Now fetch the torrent file
			$html = FetchHTML( $url );

			// This usually gets triggered if the original URL was /get/ instead of /tor/
			if( strlen( $cfg["save_torrent_name"] ) == 0 )
			{
				// Get the name of the torrent, and make it the filename
				if( preg_match( "/name([0-9][^:]):(.[^:]+)/i", $html, $html_preg_match ) )
				{
					$filelength = $html_preg_match[1];
					$filename = $html_preg_match[2];
					$cfg["save_torrent_name"] = substr( $filename, 0, $filelength ) . ".torrent";
				}
			}

			// Make sure we have a torrent file
			if( strpos( $html, "d8:" ) === false )
			{
				// We don't have a Torrent File... it is something else
				AuditAction( $cfg["constants"]["error"], "BAD TORRENT for: " . $url . "\n" . $html );
				$html = "";
			}

			return $html;
		}
		elseif( strpos( strtolower ( $domain["host"] ), "isohunt" ) !== false )
		{
			// Sample (http://isohunt.com/js/rss.php):
			// http://isohunt.com/download.php?mode=bt&id=8837938
			// http://isohunt.com/btDetails.php?ihq=&id=8464972
			$referer = "http://" . $domain["host"] . "/btDetails.php?id=";

			// If the url points to the details page, change it to the download url
			if( strpos( strtolower( $url ), "/btdetails.php?" ) !== false )
			{
				$url = str_replace( "/btDetails.php?", "/download.php?", $url ) . "&mode=bt"; // Need to make it grab the torrent
			}

			// Grab contents of details page
			$html = FetchHTML( $url, $referer );

			// Get the name of the torrent, and make it the filename
			if( preg_match( "/name([0-9]+):[^:]+/i", $html, $html_preg_match ) )
			{
				$filelength = $html_preg_match[1];
				$filename = $html_preg_match[0];
				$cfg["save_torrent_name"] = substr( $filename, 5+strlen($filelength), $filelength ) . ".torrent";
			}


			// Make sure we have a torrent file
			if( strpos( $html, "d8:" ) === false )
			{
				// We don't have a Torrent File... it is something else
				AuditAction( $cfg["constants"]["error"], "BAD TORRENT for: " . $url . "\n" . $html );
				$html = "";
			}

			return $html;
		}
		elseif( strpos( strtolower( $url ), "details.php?" ) !== false )
		{
			// Sample (http://www.bitmetv.org/rss.php?passkey=123456):
			// http://www.bitmetv.org/details.php?id=18435&hit=1
			$referer = "http://" . $domain["host"] . "/details.php?id=";

			$html = FetchHTML( $url, $referer );

			// Sample (http://www.bitmetv.org/details.php?id=18435)
			// download.php/18435/SpiderMan%20Season%204.torrent
			if( preg_match( "/(download.php.[^\"]+)/i", $html, $html_preg_match ) )
			{
				$torrent = str_replace( " ", "%20", substr( $html_preg_match[0], 0, -1 ) );
				$url2 = "http://" . $domain["host"] . "/" . $torrent;
				$html2 = FetchHTML( $url2 );

				// Make sure we have a torrent file
				if (strpos($html2, "d8:") === false)
				{
					// We don't have a Torrent File... it is something else
					AuditAction($cfg["constants"]["error"], "BAD TORRENT for: ".$url."\n".$html2);
					$html2 = "";
				}
				return $html2;
			}
			else
			{
				return "";
			}
		}
		elseif( strpos( strtolower( $url ), "download.asp?" ) !== false )
		{
			// Sample (TF's TorrenySpy Search):
			// http://www.torrentspy.com/download.asp?id=519793
			$referer = "http://" . $domain["host"] . "/download.asp?id=";

			$html = FetchHTML( $url, $referer );

			// Get the name of the torrent, and make it the filename
			if( preg_match( "/name([0-9]+):[^:]+/i", $html, $html_preg_match ) )
			{
				$filelength = $html_preg_match[1];
				$filename = $html_preg_match[0];
				$cfg["save_torrent_name"] = substr( $filename, 5+strlen($filelength), $filelength ) . ".torrent";
			}

			if( !empty( $html ) )
			{
				// Make sure we have a torrent file
				if( strpos( $html, "d8:" ) === false )
				{
					// We don't have a Torrent File... it is something else
					AuditAction( $cfg["constants"]["error"], "BAD TORRENT for: " . $url . "\n" . $html );
					$html = "";
				}
				return $html;
			}
			else
			{
				return "";
			}
		}
	}

	$html = FetchHTML( $url );
	// Make sure we have a torrent file
	if( strpos( $html, "d8:" ) === false )
	{
		// We don't have a Torrent File... it is something else
		AuditAction( $cfg["constants"]["error"], "BAD TORRENT for: " . $url.  "\n" . $html );
		$html = "";
	}
	else
	{
		$html = substr($html, strpos($html, "d8:"));
		// Get the name of the torrent, and make it the filename
		if( preg_match( "/name([0-9]+):[^:]+/i", $html, $html_preg_match ) )
		{
			$filelength = $html_preg_match[1];
			$filename = $html_preg_match[0];
			$cfg["save_torrent_name"] = substr( $filename, 5+strlen($filelength), $filelength ) . ".torrent";
		}
	}

	return $html;
}

// -------------------------------------------------------------------
// FetchHTML() method to get data from URL -- uses timeout and user agent
// -------------------------------------------------------------------
function FetchHTML( $url, $referer = "" )
{
	global $cfg, $db;
	ini_set("allow_url_fopen", "1");
	ini_set("user_agent", $_SERVER["HTTP_USER_AGENT"]);

	//$url = cleanURL( $url );
	$domain = parse_url( $url );
	$getcmd  = $domain["path"];

	if(!array_key_exists("query", $domain))
	{
		$domain["query"] = "";
	}

	$getcmd .= ( !empty( $domain["query"] ) ) ? "?" . $domain["query"] : "";

	$cookie = "";
	$rtnValue = "";

	// If the url already doesn't contain a passkey, then check
	// to see if it has cookies set to the domain name.
	if( ( strpos( $domain["query"], "passkey=" ) ) === false )
	{
		$sql = "SELECT c.data FROM tf_cookies AS c LEFT JOIN tf_users AS u ON ( u.uid = c.uid ) WHERE u.user_id = '" . $cfg["user"] . "' AND c.host = '" . $domain['host'] . "'";
		$cookie = $db->GetOne( $sql );
		showError( $db, $sql );
	}

	if( !array_key_exists("port", $domain) )
	{
		$domain["port"] = 80;
	}

	// Check to see if this site requires the use of cookies
	if( !empty( $cookie ) )
	{
		$socket = @fsockopen( $domain["host"], $domain["port"], $errno, $errstr, 30 ); //connect to server

		if( !empty( $socket ) )
		{
			// Write the outgoing header packet
			// Using required cookie information
			$packet  = "GET " . $url . " HTTP/1.0\r\n";
			$packet .= ( !empty( $referer ) ) ? "Referer: " . $referer . "\r\n" : "";
			$packet .= "Accept: */*\r\n";
			$packet .= "Accept-Language: en-us\r\n";
			$packet .= "User-Agent: ".$_SERVER["HTTP_USER_AGENT"]."\r\n";
			$packet .= "Host: " . $domain["host"] . "\r\n";
			$packet .= "Connection: Close\r\n";
			$packet .= "Cookie: " . $cookie . "\r\n\r\n";

			// Send header packet information to server
			@fputs( $socket, $packet );

			// Initialize variable, make sure null until we add too it.
			$rtnValue = null;

			// If http 1.0 just take it all as 1 chunk (Much easier, but for old servers)
			while( !@feof( $socket ) )
			{
				$rtnValue .= @fgets( $socket, 500000 );
			}

			@fclose( $socket ); // Close our connection
		}
	}
	else
	{
		if( $fp = @fopen( $url, 'r' ) )
		{
			$rtnValue = "";
			while( !@feof( $fp ) )
			{
				$rtnValue .= @fgets( $fp, 4096 );
			}
			@fclose( $fp );
		}
	}

	// If the HTML is still empty, then try CURL
	if (($rtnValue == "" && function_exists("curl_init")) ||
		(strpos($rtnValue, "HTTP/1.0 302") > 0 && function_exists("curl_init")) ||
		(strpos($rtnValue, "HTTP/1.1 302") > 0 && function_exists("curl_init")))
	{
		// Give CURL a Try
		$ch = curl_init();
		if ($cookie != "")
		{
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}
		curl_setopt($ch, CURLOPT_PORT, $domain["port"]);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, TRUE);

		$response = curl_exec($ch);

		curl_close($ch);

		$rtnValue = substr($response, strpos($response, "d8:"));
		$rtnValue = rtrim($rtnValue, "\r\n");
	}

	return $rtnValue;
}

//**************************************************************************
// getDownloadSize()
// Grab the full size of the download from the torrent metafile
function getDownloadSize($torrent){
	$rtnValue = "";
	if (file_exists($torrent))
	{
		include_once("BDecode.php");
		$fd = fopen($torrent, "rd");
		$alltorrent = fread($fd, filesize($torrent));
		$array = BDecode($alltorrent);
		fclose($fd);
		$rtnValue = $array["info"]["piece length"] * (strlen($array["info"]["pieces"]) / 20);
	}
	return $rtnValue;
}

//**************************************************************************
// formatBytesToKBMGGB()
// Returns a string in format of GB, MB, or KB depending on the size for display
function formatBytesToKBMGGB($inBytes)
{
	$rsize = "";
	if ($inBytes > (1024 * 1024 * 1024))
	{
		$rsize = round($inBytes / (1024 * 1024 * 1024), 2) . " GB";
	}
	elseif ($inBytes < 1024 * 1024)
	{
		$rsize = round($inBytes / 1024, 1) . " KB";
	}
	else
	{
		$rsize = round($inBytes / (1024 * 1024), 1) . " MB";
	}
	return $rsize;
}

//**************************************************************************
// HealthData
// Stores the image and title of for the health of a file.
class HealthData
{
	var $image = "";
	var $title = "";
}

//**************************************************************************
// getStatusImage() Takes in an AliasFile object
// Returns a string "file name" of the status image icon
function getStatusImage($af)
{
	$hd = new HealthData();
	$hd->image = "black.gif";
	$hd->title = "";

	if ($af->running == "1")
	{
		// torrent is running
		if ($af->seeds < 2)
		{
			$hd->image = "yellow.gif";
		}
		if ($af->seeds == 0)
		{
			$hd->image = "red.gif";
		}
		if ($af->seeds >= 2)
		{
			$hd->image = "green.gif";
		}
	}
	if ($af->percent_done >= 100)
	{
		if(trim($af->up_speed) != "" && $af->running == "1")
		{
			// is seeding
			$hd->image = "green.gif";
		} else {
			// the torrent is finished
			$hd->image = "black.gif";
		}
	}

	if ($hd->image != "black.gif")
	{
		$hd->title = "S:".$af->seeds." P:".$af->peers." ";
	}

	if ($af->running == "3")
	{
		// torrent is queued
		$hd->image = "black.gif";
	}

	return $hd;
}

//**************************************************************************
function writeQinfo($fileName,$command)
{
	$fp = fopen($fileName.".Qinfo","w");
	fwrite($fp, $command);
	fflush($fp);
	fclose($fp);
}

//**************************************************************************
class ProcessInfo
{
	var $pid = "";
	var $ppid = "";
	var $cmdline = "";

	function ProcessInfo($psLine)
	{
		$psLine = trim($psLine);
		if (strlen($psLine) > 12)
		{
			$this->pid = trim(substr($psLine, 0, 5));
			$this->ppid = trim(substr($psLine, 5, 6));
			$this->cmdline = trim(substr($psLine, 12));
		}
	}
}

//**************************************************************************
function runPS()
{
	global $cfg;

	return shell_exec("ps x -o pid='' -o ppid='' -o command='' -ww | grep ".basename($cfg["btphpbin"])." | grep ".$cfg["torrent_file_path"]." | grep -v grep");
}

//**************************************************************************
function RunningProcessInfo()
{
	global $cfg;

	if (IsAdmin())
	{
		include_once("RunningTorrent.php");

		$screenStatus = runPS();

		$arScreen = array();
		$tok = strtok($screenStatus, "\n");
		while ($tok)
		{
			array_push($arScreen, $tok);
			$tok = strtok("\n");
		}

		$cProcess = array();
		$cpProcess = array();
		$pProcess = array();
		$ProcessCmd = array();

		$QLine = "";
		for($i = 0; $i < sizeof($arScreen); $i++)
		{
			if(strpos($arScreen[$i], $cfg["tfQManager"]) > 0)
			{
				$pinfo = new ProcessInfo($arScreen[$i]);
				$QLine = $pinfo->pid;
			}
			else
			{
			   if(strpos($arScreen[$i], basename($cfg["btphpbin"])) !== false)
			   {
				   $pinfo = new ProcessInfo($arScreen[$i]);

				   if (intval($pinfo->ppid) == 1)
				   {
						if(!strpos($pinfo->cmdline, "rep python") > 0)
						{
							if(!strpos($pinfo->cmdline, "ps x") > 0)
							{
								array_push($pProcess,$pinfo->pid);
								$rt = new RunningTorrent($pinfo->pid . " " . $pinfo->cmdline);
								//array_push($ProcessCmd,$pinfo->cmdline);
								array_push($ProcessCmd,$rt->torrentOwner . "\t". str_replace(array(".stat"),"",$rt->statFile));
							}
						}
				   }
				   else
				   {
						if(!strpos($pinfo->cmdline, "rep python") > 0)
						{
							if(!strpos($pinfo->cmdline, "ps x") > 0)
							{
								array_push($cProcess,$pinfo->pid);
								array_push($cpProcess,$pinfo->ppid);
							}
						}
				   }
			   }
			}
		}
		echo " --- Running Processes ---\n";
		echo " Parents  : " . count($pProcess) . "\n";
		echo " Children : " . count($cProcess) . "\n";
		echo "\n";

		echo " PID \tOwner\tTorrent File\n";
		foreach($pProcess as $key => $value)
		{
			echo " " . $value . "\t" . $ProcessCmd[$key] . "\n";
			foreach($cpProcess as $cKey => $cValue)
				if (intval($value) == intval($cValue))
					echo "\t" . $cProcess[$cKey] . "\n";
		}
		echo "\n";
		echo " --- QManager --- \n";
		echo " PID : ";
		echo " ".$QLine;
	}
}

//**************************************************************************
function getNumberOfQueuedTorrents()
{
	global $cfg;

	$rtnValue = 0;

	$dirName = $cfg["torrent_file_path"] . "queue/";

	$handle = @opendir($dirName);

	if ($handle)
	{
		while($entry = readdir($handle))
		{
			if ($entry != "." && $entry != "..")
			{
				if (!(@is_dir($dirName.$entry)) && (substr($entry, -6) == ".Qinfo"))
				{
					$rtnValue = $rtnValue + 1;
				}
			}
		}
	}

	return $rtnValue;
}

//**************************************************************************
function getRunningTorrentCount()
{
	return count(getRunningTorrents());
}

//**************************************************************************
function getRunningTorrents(){
	global $cfg;
	$screenStatus = runPS();
	$arScreen = array();
	$tok = strtok($screenStatus, "\n");
	while ($tok){
		array_push($arScreen, $tok);
		$tok = strtok("\n");
	}
	$artorrent = array();

	for($i = 0; $i < sizeof($arScreen); $i++){
		if(! strpos($arScreen[$i], $cfg["tfQManager"]) > 0){
		   if(strpos($arScreen[$i], basename($cfg["btphpbin"])) !== false) {
			   $pinfo = new ProcessInfo($arScreen[$i]);
			   if (intval($pinfo->ppid) == 1) {
					if(!strpos($pinfo->cmdline, "rep python") > 0){
						if(!strpos($pinfo->cmdline, "ps x") > 0){
							array_push($artorrent,$pinfo->pid . " " . $pinfo->cmdline);
						}
					}
			   }
		   }
		}
	}

	return $artorrent;
}

//**************************************************************************
function checkQManager()
{
	$x = getQManagerPID();
	if (strlen($x) > 0)
	{
		$y = $x;
		$arScreen = array();
		$tok = strtok(shell_exec("ps -p " . $x . " | grep " . $y), "\n");

		while ($tok)
		{
			array_push($arScreen, $tok);
			$tok = strtok("\n");
		}

		$QMgrCount = sizeOf($arScreen);
	}
	else
	{
		$QMgrCount = 0;
	}

	return $QMgrCount;
}

//**************************************************************************
function getQManagerPID()
{
	global $cfg;

	$rtnValue = "";

	$pidFile = $cfg["torrent_file_path"] . "queue/tfQManager.pid";

	if(file_exists($pidFile))
	{
		$fp = fopen($pidFile,"r");
		if ($fp)
		{
			while (!feof($fp))
			{
				$tmpValue = fread($fp,1);
				if($tmpValue != "\n")
					$rtnValue .= $tmpValue;
			}
			fclose($fp);
		}
	}
	return $rtnValue;
}

//**************************************************************************
function startQManager($maxServerThreads=5,$maxUserThreads=2,$sleepInterval=10)
{
	global $cfg;

	// is there a stat and torrent dir?
	if (is_dir($cfg["torrent_file_path"]))
	{
		if (is_writable($cfg["torrent_file_path"]) && !is_dir($cfg["torrent_file_path"]."queue/"))
		{
			//Then create it
			mkdir($cfg["torrent_file_path"]."queue/", 0777);
		}
	}

	if (checkQManager() == 0)
	{
	$cmd1 = "cd " . $cfg["path"] . "TFQUSERNAME";

	if (! array_key_exists("pythonCmd",$cfg))
	{
		insertSetting("pythonCmd","/usr/bin/python");
	}

	if (! array_key_exists("debugTorrents",$cfg))
	{
		insertSetting("debugTorrents",false);
	}

		if (!$cfg["debugTorrents"])
		{
			$pyCmd = $cfg["pythonCmd"] . " -OO";
		}
		else
		{
			$pyCmd = $cfg["pythonCmd"];
		}

		$btphp = "'" . $cmd1. "; HOME=".$cfg["path"]."; export HOME; nohup " . $pyCmd . " " .$cfg["btphpbin"] . " '";
		$command = $pyCmd . " " . $cfg["tfQManager"] . " ".$cfg["torrent_file_path"]."queue/ ".escapeshellarg($maxServerThreads)." ".escapeshellarg($maxUserThreads)." ".escapeshellarg($sleepInterval)." ".$btphp." > /dev/null &";
		//$command = $pyCmd . " " . $cfg["tfQManager"] . " ".$cfg["torrent_file_path"]."queue/ ".$maxServerThreads." ".$maxUserThreads." ".$sleepInterval." ".$btphp." > /dev/null2>&1 & &";

		$result = exec($command);

		sleep(2); // wait for it to start prior to getting pid

		AuditAction($cfg["constants"]["QManager"], "Started PID:" . getQManagerPID());

	}else{
		AuditAction($cfg["constants"]["QManager"], "QManager Already Started  PID:" . getQManagerPID());
	}
}

//**************************************************************************
function stopQManager()
{
	global $cfg;

	$QmgrPID = getQManagerPID();
	if($QmgrPID != "")
	{
		AuditAction($cfg["constants"]["QManager"], "Stopping PID:" . $QmgrPID);
		$result = exec("kill ".escapeshellarg($QmgrPID));
		unlink($cfg["torrent_file_path"] . "queue/tfQManager.pid");
	}
}

//**************************************************************************
// file_size()
// Returns file size... overcomes PHP limit of 2.0GB
function file_size($file)
{
	$size = @filesize($file);
	if ( $size == 0)
	{
		$size = exec("ls -l \"".escapeshellarg($file)."\" | awk '{print $5}'");
	}
	return $size;
}

//**************************************************************************
// SecurityClean()
// Cleans the file name for delete and alias file creation
function SecurityClean($string)
{
	global $cfg;
	
	if (empty($string))
	{
		return $string;
	}
	
	$array = array("<", ">", "\\", "//", "..", "'", "/");
	foreach ($array as $char)
	{
		$string = str_replace($char, NULL, $string);
	}
		
	if( (strtolower( substr( $string, -8 ) ) == ".torrent") || (strtolower( substr( $string, -5 ) ) == ".stat") )
	{
		// we are good
	}
	else
	{
		AuditAction($cfg["constants"]["error"], "Not a stat or torrent: " . $string);
		die("Invalid file specified.  Action has been logged.");
	}
	return $string;
}


// ***************************************************************************
// ***************************************************************************
//check if user home folder exist, if not  , creat it 
function CheckHomeDir($owner){
	global $owner;
		if (!is_dir($cfg["path"]."/".$owner)){
				if (is_writable($cfg["path"])){
					mkdir($cfg["path"]."/".$owner, 0777);
				}else{
					AuditAction($cfg["constants"]["error"], "Error -- " . $cfg["path"] . " is not writable.");
					showmessage("TorrentFlux settings are not correct (path is not writable) -- please contact an admin.");
				}
		}
}
// ***************************************************************************
// ***************************************************************************
// check if any hung , whiah have no pid file but running process
function CheckHung($torrent){
	global $cfg;
	include_once(ENGINE_ROOT."include/BtControl/RunningTorrent.php");
	$runningTorrents = getRunningTorrents();
		foreach ($runningTorrents as $key => $value){
			$rt = new RunningTorrent($value);
				if ($rt->statFile == torrent2stat($torrent)) {
					AuditAction($cfg["constants"]["error"], "Posible Hung Process " . $rt->processId);
					$result = exec("kill ".$rt->processId);
				}
		}
}
// ***************************************************************************
// ***************************************************************************
//get pid from alias
function GetPid($alias){
	global $cfg;
			if(!is_file($cfg["torrent_file_path"].$alias.".pid"))
				return -1;
		$content = file($cfg["torrent_file_path"].$alias.".pid");
		return $content[0];
}
// ***************************************************************************
// ***************************************************************************
// convert XX:yyyyyy;XXXXX:yyyyyyy; to $XX=yyyyyy;
function Options2Vars($options,$allowedVars){
	$output= Array();
		if($options==''){
			return $output;
		}
	$options=split(';', $options);
		foreach($options as $option){
			$tmp=split(':',$option ,2);
				if(in_array($tmp[0],$allowedVars)){
					$output[$tmp[0]]=$tmp[1];
				}
		}
	return $output;
}
// ***************************************************************************
// ***************************************************************************
//Check if process is running
function CheckRunning($pid=''){
	global $cfg;
	if($torrent){
		// if check one torrent running or not
		return (!is_file($cfg["torrent_file_path"].$pid)) ? 0:1;
	}else{
		// if check any torrent running or not 
		//* not build yet
		return 0;
	}
}
// ***************************************************************************
// ***************************************************************************
//Upload a Torrent
function NewTorrent($file,$name,$options=''){
	global $cfg;
		if(!is_file($file)){
			return $file.'not found';
		}
	$filesize=filesize($file);
		if(!($filesize <= 1000000 && $filesize > 0)){
			return 'filesize error';
		}
		do{
			$file_name=$cfg["torrent_file_path"].RANDOM().$timestamp.'.torrent';
		}while(is_file($cfg["torrent_file_path"].$file_name));
	rename($file,$file_name);
	chmod ($file_name, 0644);
	return NewTorrentInjectDATA($file_name,$options);
}
// ***************************************************************************
// ***************************************************************************
//Upload a Torrent form Url
function UrlTorrent($url,$options=''){
	global $cfg;
		if(!function_exists('curl_init')){
			showmessage('please install php curl first',1);
		}
	// download by curl
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_TIMEOUT, 300);
	curl_setopt($ch, CURLE_OPERATION_TIMEOUTED, 300);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	$return=curl_exec($ch);
	$errno=curl_errno($ch);
		if($errno !==0){
			$errstring=curl_error($ch);
			showmessage(DumpCurlError($errno,$errstring),1);
		}
	curl_close($ch);
	//generate torrent name
		do{
			$file_name=$cfg["torrent_file_path"].RANDOM().$timestamp.'.torrent';
		}while(is_file($cfg["torrent_file_path"].$file_name));
	$fp = fopen($file_name, 'w');
	fwrite($fp, $return);
	fclose($fp);
	$return = NewTorrentInjectDATA($file_name,$options);
	AuditAction($cfg["constants"]["url_upload"], $file_name);
	return $return;
}
// ***************************************************************************
// ***************************************************************************
//function for creating a new .stat file
function CreatNewStat($torrent,$filesize){
	global $cfg;
	include_once("AliasFile.php");	
	$af = new AliasFile($cfg["torrent_file_path"].torrent2stat($torrent), $torrentowner);
	$af->running = "2"; // file is new
	$af->size = $filesize;
	$af->WriteFile();
}

function torrent2stat($torrent){
	return RemoveExtension(basename($torrent)).'.stat';
}
function torrent2pid($torrent){
	return RemoveExtension(basename($torrent)).'.stat.pid';
}
function torrent2log($torrent){
	return RemoveExtension(basename($torrent)).'.stat.log';
}
// ***************************************************************************
// ***************************************************************************
//function for removing the extension
function RemoveExtension($strName){
	$ext = strrchr($strName, '.');
		if($ext !== false){
			$strName = substr($strName, 0, -strlen($ext));
		}
return $strName;
}
function get_file_extension($file_name){  
	return substr(strrchr($file_name,'.'),1);  
}  
// function for grabbing torrent infocarion
function GrabTorrentInfo($basename,$smartremove_padding=0){
	require_once './include/BEncode.php';
	require_once 'include/TorrentFile.class.php';
	$basename=basename($basename);
    $torrent = new BDECODE($basename);
	$info=$torrent->result;
    $info['hash']=@sha1(BEncode($torrent->result["info"]));
		//giveing same announce-list t pattern even there is only one announce
		if(!is_array($info['announce-list'])){
			$info['announce-list'][0][0]=$info['announce'];
		}
		// giving same file-list pattern even there is only one file
		if(!array_key_exists('files',$info['info'])){
			$info['info']['files'][0]['length'][0]=$info['info']['length'];
			$info['info']['files'][0]['path'][0]=$info['info']['name'];
			$info['info']['files'][0]['path.utf-8'][0]=$info['info']['name.utf-8']?$info['info']['name.utf-8']:$info['info']['name'];
			$info['info']['files'][0]['piece length'][0]=$info['info']['piece length'];
			$info['info']['files'][0]['pieces'][0]=$info['info']['pieces'];
		}
	$info['creation date_text']=date("m/d/Y H:i:s",$info['creation date'] );
		//creat utf8 path name if there is not
		foreach($info['info']['files'] as $index =>$file){
				if(!array_key_exists('path.utf-8',$file)){
					$info['info']['files'][$index]['path.utf-8']['0']=$file['path']['0'];
					unset($file['path']['0']);
				}
		}
		if($smartremove_padding){
			foreach($info['info']['files'] as $index =>$file){
					if(strpos($file['path.utf-8']['0'], '_padding_file') !==FALSE){
						unset($info['info']['files'][$index]);
					}
			}
		}

	return $info;
}
// ***************************************************************************
// ***************************************************************************
//function for injecting SQL while creating a torrent job
function NewTorrentInjectDATA($filename,$options=''){
	global $db,$cfg;
	//check torrent limit
		if(!checkTorrentLimit($cfg['uid'])){
			showmessage('_Max_Torrent_Limit_Reached',1,1);
		}
	$basename=basename($filename);
	//GrabTorrentInfo
	$info=GrabTorrentInfo($basename);
    $torrent = new BDECODE($basename);
    $hash=@sha1(BEncode($info["info"]));
	$name=$info['info']['name.utf-8']?$info['info']['name.utf-8'] :$info['info']['name'];
	$name=str_replace('_','-',$name);
	// return if it is not a torrent file
		if(!$name || !$hash){
			unlink($filename);
			showmessage('WRONG TORRENT FORMAT');
			return;
		}
	//check if the torrent exist 
	$sql = "SELECT `id` FROM `tf_torrents` WHERE `hash`=".$db->qstr($hash);
	$recordset = $db->Execute($sql);
	showError($db, $sql);
		if($recordset->RecordCount() == 1){
			showmessage('TORRENT ALREADY EXIST',1,0);
		}
	$filesize=$info["info"]["piece length"] * (strlen($info["info"]["pieces"]) / 20);
	// creat the .stat file for displaying
	CreatNewStat($basename,$filesize);
	//creat prio string
	$prio=buildprio(formatTorrentInfoFilesList($info['info']['files']),NULL,$cfg['smart_select']?1:0,1);
	
	//grab the options to variables
	extract(Options2Vars($options,Array('rate','drate','superseeder','runtime','maxuploads','minport','maxport','rerequest','sharekill','queue')),  EXTR_OVERWRITE);
	//use default if variables is not seted
	$rate = empty($rate)?$cfg["max_upload_rate"]:intval($rate);
	$drate = empty($drate)?$cfg["max_download_rate"]:intval($drate);
	$superseeder = "0";
	$runtime = empty($runtime)?$cfg["torrent_dies_when_done"]:intval($runtime);
	$maxuploads = empty($maxuploads)?$maxuploads = $cfg["max_uploads"]:intval($maxuploads);
	$minport = empty($minport)?$cfg["minport"]:intval($minport);
	$maxport = empty($maxport)?$cfg["maxport"]:intval($maxport);
	$rerequest = empty($rerequest)? $cfg["rerequest_interval"]:intval($rerequest);
	$sharekill= ($sharekill == "0")? "-1": intval($sharekill);
	$location='/';
	//injecting sql
	$sql = 'INSERT INTO `tf_torrents` 
	(`file_name`,`torrent` ,`hash` ,`owner_id`,
	`rate`,`drate`,`superseeder`,`runtime`,`maxuploads`,`minport`,`location`,`maxport`,`rerequest`,`sharekill`,`prio`) VALUES 
	(\''.$name.'\',\''.$basename.'\', \''.$hash.'\', \''.$cfg['uid'].'\'
	, \''.$rate.'\', \''.$drate.'\', \''.$superseeder.'\'
	, \''.$runtime.'\', \''.$maxuploads.'\', \''.$minport.'\', \''.$location.'\'
	, \''.$maxport.'\', \''.$rerequest.'\', \''.$sharekill.'\', \''.$prio.'\');';
	$recordset = $db->Execute($sql);
	$torrentID=$db->Insert_ID();
	showError($db, $sql);
	return $torrentID;
}

function checkTorrentLimit($uid){
	//function for checking if the user reached torrent limit
	// false is over limit
	global $db;
	$userinfo=GrabUserData($uid);
		if($userinfo['torrentlimit_period']>0 && $userinfo['torrentlimit_number']>0){
			return GetActivityCount(Uid2Username($uid),$userinfo['torrentlimit_period']*85200)>$userinfo['torrentlimit_number']?false:true;
		}else{
			return true;
		}
}
function checkTransferLimit($uid){
	//function for checking if the user reached transfer limit
	// false is over limit
	global $cfg,$db;
	$userinfo=GrabUserData($uid);
	$stat=GetTransferCount($uid,$userinfo['transferlimit_period']);
		if($userinfo['transferlimit_number'] >0 && $userinfo['transferlimit_period']>0){
			return ($userinfo['transferlimit_number']<$stat['total'])?false:true;
		}else{
			return true;
		}
}
function GetTransferCount($uid,$period){
	//grab the transfer static in specific range
	//$period is how many DAYS from current time
	global $cfg,$db;
	$total_speed=$total_up_speed=$total_down_speed=0;
	$uid=intval($uid);
	$torrentowner=Uid2Username($uid);
	//get current active transfer static
	$sql = "SELECT `torrent` FROM `tf_torrents` WHERE `owner_id`='$uid'";
	$result = $db->Execute($sql);
	include_once("AliasFile.php");	
	while(list($torrent) = $result->FetchRow()){
		$af = new AliasFile($cfg["torrent_file_path"].torrent2stat($torrent), $torrentowner);
		$total_up_speed+=$af->up_speed;
		$total_down_speed+=$af->down_speed;
	}
	unset($af);
	//get completed or stoped transfer static 
	$targetDate=$db->DBDate(time()-85200*$period);
	$sql = "select SUM(download ),SUM(upload) from tf_xfer where user=".$uid." and date > ".$db->qstr($targetDate);
	list($thisdown,$thisup) = $db->GetRow($sql);
	showError($db,$sql);
	$total_up_speed+=$thisup;
	$total_down_speed+=$thisdown;
	$totalspeed=$total_up_speed+$total_down_speed;
	return array(
		'up'=>$total_up_speed,
		'down'=>$total_down_speed,
		'total'=>$totalspeed);
}
// ***************************************************************************
// ***************************************************************************
//function for removing SQL while deleting a torrent job
function DelTorrentSQL($torrent_id){
	global $db,$cfg;
	$sql = 'DELETE FROM `tf_torrents` WHERE `id`= \''.$torrent_id.'\' ';
	$sql.=IsAdmin()? '':' AND `owner_id`=\''.$cfg['uid'].'\'';
	$sql.='LIMIT 1;';
	$recordset = $db->Execute($sql);
	showError($db, $sql);
}


// ***************************************************************************
// ***************************************************************************
//function for controlling all torrent
function All($action,$uid=""){
	global $cfg,$db;
		if(!in_array($action,Array('Start','Kill'))){
			showmessage('wrong_action',1);;
		}
	$sqladd='';
		if($uid){
			$sqladd=" AND owner_id='".intval($uid)."'";
		}
	$sql = "SELECT `id` FROM `tf_torrents` WHERE 1 ".$sqladd;
	$result = $db->Execute($sql);
	include_once(ENGINE_ROOT."include/BtControl_Tornado.class.php");
	while(list($id) = $result->FetchRow()){
	
		$abd=new BtControl($id,'');
		$abd->$action();
	}
}
// ***************************************************************************
// ***************************************************************************
//Displaying Curl errors:
function DumpCurlError($errno,$errstring){
	return '<b>Curl Error Number:<a href="http://curl.haxx.se/libcurl/c/libcurl-errors.html">'.$errno.'</a></b>';
}
// ***************************************************************************
// ***************************************************************************
//showmessage:
function showmessage($msg,$stop=0,$closewindow=0){
	global $usejs;
		if($closewindow){
				if($usejs){
						if($msg)echo 'alert(\''.addslashes($msg).'\');';
					echo 'window.MochaUI.closeAll();'; 
				}else{
					if($msg)echo $msg;
				}
		}else{
			if($msg){
				if($usejs){
					echo 'alert(\''.addslashes($msg).'\');'; 
				}else{
					echo $msg;
				}
			}
		}
		if($stop){
			exit();
		}

}

function JSReload(){
	exit( 'window.location.reload();');
}
// ***************************************************************************
// ***************************************************************************
//function for generating random seed for more random
function make_seed(){
  list($usec, $sec) = explode(' ', microtime());
  return (float) $sec + ((float) $usec * 100000);
}

// ***************************************************************************
// ***************************************************************************
//function for returning random 
function RANDOM(){
	srand(make_seed());
	return rand();
}
// ***************************************************************************
// ***************************************************************************
//function for grabbing status and status_text

function grabbingStatus($running,$percent_done,$haspid){
	if($haspid){
			if($percent_done >=100){
			//seeding
				$status=4;
				$status_text=_show_status_Seeding;
			}else{
				//downloading
				$status=2;
				$status_text=_show_status_Downloading;
			}
	}else{
			if($running=="2"){
				//new
				$status=0;
				$status_text=_show_status_New;
			}elseif($running=="3"){
				// waiting ,queue
				$status=1;
				$status_text=_show_status_Queue;
			}else{
					if($percent_done >=100){
						//all finished
						$status=5;
						$status_text=_show_status_Finished;
					}else{
						//stoped
						$status=3;
						$status_text=_show_status_Stopped;
					}
			}
	}
	return array($status,$status_text);
}

function TorrentIDtoTorrent($id){
	global $db;
	$sql='select `torrent` From `tf_torrents` WHERE `id`=\''.intval($id).'\' LIMIT 1';
	$recordset = $db->Execute($sql);
	$return=$recordset->FetchRow();
	return $return['torrent'];
}
function GetUserList(){
// get an array of user list
	global $db;
	$sql='select * From `tf_users`';
	$recordset = $db->Execute($sql);
	$array=$recordset->GetRows();
		foreach($array as $index =>$user){
			$array[$index]['online']=($user['hide_offline'] || time()-$user['last_visit']>300)?0:1;
		}
	return $array;
}
function template($file){
// return the path of the cached template file
// it also creat cache file if it does not exist
	global $lang;
	$objfile = ENGINE_ROOT . '/cache/templates/' .$lang.'_'.$file .'.tpl.php';
	$tmpfile = ENGINE_ROOT.'/template/'.$file.'.htm';
		if(!is_file($tmpfile)){
			showmessage('template file not found');
			return;
		}
		if(!is_file($objfile) || filectime($tmpfile)>filectime($objfile)){
			include ENGINE_ROOT.'/include/template_rebuild.func.php';
			parse_template($file);
		}
	return $objfile;
}
function buildprio($FileList,$prioList=array(),$smartremove=1,$default=-1){
// this function build the variable for prio
// the input file list should look like this:
//  $filelist[0]['path']='xxxxxxx';
//  $filelist[1]['path']='xxxxxxx';
//
//the priolist should look like this:
//  $priolist[0]=1;
//  $priolist[1]=1;
// 1= download, 0=not download
//
// it return -1,2,2,2,-1, for saving into database or send to the tornado
	$comma='';
	$default==1?1:-1;
	$prioList=$prioList==NULL?array():$prioList;
		if (is_array($FileList) && count($FileList) > 0){
            foreach($FileList as $index => $file){
					if($smartremove AND (get_file_extension($file['path'])=='txt' OR get_file_extension($file['path'])=='url') ){
							//if autoremove .txt and .url
						$result.=$comma.'-1';
					}else{
						if(array_key_exists($index,$prioList) AND $prioList[$index]>0){
						// if key in priolist is found in filelist
								$result.=$comma.'2';
						}else{
						// if  key in priolist is NOT found in filelist
							$result.=$comma.$default;
						}
					}
				$comma=',';
            }
        }else{
			showmessage('Filelist is not an array or shorter than 1');
		}
	return $result;
}
function getFile($var){
	return ($var < 65535)? true:false;
}
function formatTorrentInfoFilesList($meta_info,$FindPadding=9){
	//this function can format torrent meta to the file array
		if(is_array($meta_info)){
			//if this is a list of file
			foreach($meta_info as $fileindex=> $file){
				$filearray[$fileindex]['path']=$file['path.utf-8']['0'];
			}
		}else{
			$filearray[0]['path']=$file['path.utf-8']['0'];
		}
		if($FindPadding){
			foreach($filearray as $index =>$file){
					$file['padd']=(strpos($file['path'],'_padding_file'))?1:0;
			}
		}
	return $filearray;
}
function MakeDefault($name,$type){
	global $cfg;
		if(!array_key_exists($name,$cfg)) return ;
		if($type=='checkbox'){
			return $cfg[$name]==1?'checked="1"':'';
		}elseif($type=='text'){
			return 'value=\''.$cfg[$name].'\'';
		}
}
?>
