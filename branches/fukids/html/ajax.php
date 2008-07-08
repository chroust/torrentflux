<?php

// this file is for returning html and javascript when ever there is a ajax call
//most of them are called via javascript::OpenWindow()
include_once("include/functions.php");
//group by how  it is called
$action = getRequestVar('action',Array('listtorrent','icon','jsonTorrent','tabs','rightclick','tips','form'));
if($action=='listtorrent'){
	include(ENGINE_ROOT.'include/ajax/list_torrent.php');
}elseif($action=='tips'){
	$id = getRequestVar('id',Array('user_profile'));
		if($id=='user_profile'){
			$uid=intval(getRequestVar('uid'));
			$UsrData=GrabUserData($uid);
			include template('ajax_Tips_user_profile');
		}
}elseif($action=='rightclick'){
	$id = getRequestVar('id',Array('_SEND_PM','_EDITUSER','_VIEW_PM','_ADD_USER','_ADMIN_EDIT_USER','_ADMIN_VIEW_HISTORY'));
		if($id=='_SEND_PM'){
		    $rmid = getRequestVar('rmid');
			$tousertmp=getRequestVar('to_user');
			$to_user=IsUser($tousertmp)?$tousertmp:Uid2Username(intval(getRequestVar('uid')));
				if($cfg['user']==$to_user){
					showmessage('should not send message to myself',1);
				}
				if(!empty($rmid)){
					list($from_user, $message, $ip, $time) = GetMessage($rmid);
					$message = _DATE.": ".date(_DATETIMEFORMAT, $time)."\n".$from_user." "._WROTE.":\n\n".$message;
					$message = ">".str_replace("\n", "\n>", $message);
					$message = "\n\n\n".$message;
				}
			include template('ajax_RightClick_SendPm');
		}elseif($id=='_EDITUSER'){
			$arLanguage = GetLanguages();
			$arThemes = GetThemes();
			include template('ajax_user_UpdateProfile');
		}elseif($id=='_VIEW_PM'){
			$pmlist=listPM();
			$pmcount=count($pmlist);
			include template('ajax_user_VIEW_PM');
		}elseif($id=='_ADD_USER'){
			AdminCheck();
			include template('ajax_RightClick_ADD_USER');
		}elseif($id=='_ADMIN_EDIT_USER'){
			AdminCheck();
			$uid=intval(getRequestVar('uid'));
			$userinfo=GrabUserData($uid);
			$arLanguage = GetLanguages();
			$arThemes = GetThemes();
			include template('ajax_admin_EDIT_USER');
		}elseif($id=='_ADMIN_VIEW_HISTORY'){
			AdminCheck();
			header("location: admin.php?op=showUserActivity");
			exit;
		}
}elseif($action=='icon'){
	// if it is called via icon bar
	$id = getRequestVar('id',Array('Upload_Torrent','Url_Torrent','Creat_Torrent','New_Feed','Edit_Torrent','_Admin_Setting'));
		if($id=='Upload_Torrent'){
			$Default_max_upload_rate=$cfg['max_upload_rate'];
			$Default_max_download_rate=$cfg['max_download_rate'];
			$Default_max_uploads=$cfg['max_uploads'];
			$Default_maxport=$cfg['maxport'];
			$Default_minport=$cfg['minport'];
			$Default_rerequest_interval=$cfg['rerequest_interval'];
			include template('ajax_Upload_Torrent');
		}elseif($id=='Url_Torrent'){
			$Default_max_upload_rate=$cfg['max_upload_rate'];
			$Default_max_download_rate=$cfg['max_download_rate'];
			$Default_max_uploads=$cfg['max_uploads'];
			$Default_maxport=$cfg['maxport'];
			$Default_minport=$cfg['minport'];
			$Default_rerequest_interval=$cfg['rerequest_interval'];
			include template('ajax_Url_Torrent');
		}elseif($id=='Creat_Torrent'){
			die('building.....');
		}elseif($id=='New_Feed'){
			die('building.....');
		}elseif($id=='Edit_Torrent'){
			$torrentid=intval(getRequestVar('torrentid'));
				if(!$torrentid){
					showmessage('torrent is not a intval',1);
				}
			// grab torrent config
			include_once("include/BtControl_Tornado.class.php");
			$Bt= new BtControl($torrentid);
			$file_name=$Bt->file_name;
			$Default_max_upload_rate=$Bt->rate;
			$Default_max_download_rate=$Bt->drate;
			$Default_max_uploads=$Bt->maxuploads;
			$Default_maxport=$Bt->maxport;
			$Default_minport=$Bt->minport;
			$Default_rerequest_interval=$Bt->rerequest;
			$Default_sharekill=$Bt->sharekill;
			// grub the file details
			$info=GrabTorrentInfo(TorrentIDtoTorrent($torrentid),'remove padding');
			$filearray=formatTorrentInfoFilesList($info['info']['files']);
			//grab the prio details
			$priolist=explode(',',$Bt->prio);
			include template('ajax_Edit_Torrent');
		}elseif($id=='_Admin_Setting'){
			include template('ajax_icon_admin_setting');
		}
}elseif($action=='tabs'){
	//if it is called via tab bar 
	$tab = getRequestVar('tab',Array('tab1','tab2','tab3','tab4','tab5','tab6'));
	$torrentId=intval(getRequestVar('torrentId'));
		if(!$torrentId){
			showmessage('torrentId Error',1);
		}
		if($tab=='tab1'){
		//normal
			$torrentfile=TorrentIDtoTorrent($torrentId);
			$info=GrabTorrentInfo($torrentfile);
			include template('ajax_tab_tab1');
		}elseif($tab=='tab2'){
		//Tracker
			$info=GrabTorrentInfo(TorrentIDtoTorrent($torrentId));
				foreach($info['announce-list'] as $announce){
					echo $announce[0].'<br />';
				}
		}elseif($tab=='tab3'){
		//user
		}elseif($tab=='tab4'){
		//file
			// read the .torrent file
			$info=GrabTorrentInfo(TorrentIDtoTorrent($torrentId),1);
			//read .stat file
			include_once("AliasFile.php");	
			$af = new AliasFile($cfg["torrent_file_path"].torrent2stat(TorrentIDtoTorrent($torrentId)), $torrentowner);
			//display
			foreach ( $af->files as $fileInfo ){
			echo  $fileInfo->inplace.$fileInfo->complete;
			}
				foreach($info['info']['files'] as $file){
					echo $file['path.utf-8']['0'].'<br />';
				}
		}elseif($tab=='tab5'){
		//speed
			include template('ajax_tab_tab5');
		}elseif($tab=='tab6'){
		//log
			$logfile=$cfg["torrent_file_path"].torrent2log(TorrentIDtoTorrent($torrentId));
			$fh = fopen($logfile, 'r');
				if ($fh) {
					while (!feof($fh)){
						$buffer = fgets($fh, 4096); // Read a line.
						echo $buffer.'<br />';
					}
					fclose($fh);
				}else{
					echo _Current_No_Log;
				}
		}
}elseif($action=='form'){
	$id = getRequestVar('id',Array('Torrent_Search'));
		if($id=='Torrent_Search'){
			include ENGINE_ROOT.'include/ajax/form/torrent_search.php';
		}
}
?>
