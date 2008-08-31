<?php
// this file is for returning html and javascript when ever there is a ajax call
//most of them are called via javascript::OpenWindow()
include_once("include/functions.php");
//group by how  it is called
$action = getRequestVar('action',Array('listtorrent','icon','jsonTorrent','tabs','rightclick','tips','form'));
if($action=='listtorrent'){
	include(ENGINE_ROOT.'include/ajax/list_torrent.php');
}elseif($action=='tips'){
	$id = getRequestVar('id',Array('user_profile','checkport','robotstat','dirtree'));
		if($id=='user_profile'){
			$uid=intval(getRequestVar('uid'));
			$UsrData=GrabUserData($uid);
			$TotalTransfer=GetTransferCount($uid);
			$TotalQueue=getNumberOfQueuedTorrents($uid);
			//creat img for transfer static
			$sql="SELECT `date`,`download`,`upload` FROM `tf_xfer` WHERE `user`='$uid'";
			$result = $db->Execute($sql);
			showError($db,$sql);
			$mindate=$maxdate=$maxval=$upstr=$downstr=$comma='';
			$max=0;
				while($row = $result->FetchRow()){
					$mindate=($mindate=='')?$row['date']:$mindate;
					$maxdate=$row['date'];
					$max=$max<$row['download']?$row['upload']:($max<$row['download']?$row['download']:$max);
					$upstr.=$comma.$row['upload'];
					$downstr.=$comma.$row['download'];
					$comma=',';
					$row[]=$row;
				}
			$halfmax=$max/2;
			$maxtext= formatBytesToKBMGGB($max*1024*1024);
			$halfmaxtext= formatBytesToKBMGGB($halfmax*1024*1024);
			$imgsrc= "http://chart.apis.google.com/chart?cht=lc&chs=700x160&chd=t:$downstr|$upstr&chds=0,$max&chco=ff0000,00ff00&chdl=Download|Upload&chtt=Speed+Chart&chg=5,25&chxt=y,x,x&chxl=0:|0|$halfmaxtext|$maxtext|1:|$mindate|$maxdate|2:||time|";
			include template('ajax_Tips_user_profile');
		}elseif($id=='checkport'){
			$minport=intval(getRequestVar('minport'));
			$maxport=intval(getRequestVar('maxport'));
			CheckPorts($minport,$maxport);
		}elseif($id=='robotstat'){
			echo CheckCronRobot()?'<span class="online">'._ONLINE.'</span>':'<span class="offline">'._OFFLINE.'</span>';
		}elseif($id=='dirtree'){
			$dirlist= dirTree2();
			include template('ajax_Tips_dirtree');
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
			//get the pm list
			$pmlist=listPM();
			$pmcount=count($pmlist);
			//uncheck newpm
			$sql="UPDATE `tf_users` SET `newpm`=0 WHERE `uid`='$myuid'";
			$db->Execute($sql);
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
			$Default_location=DIRECTORY_SEPARATOR;
			include template('ajax_Upload_Torrent');
		}elseif($id=='Url_Torrent'){
			$Default_max_upload_rate=$cfg['max_upload_rate'];
			$Default_max_download_rate=$cfg['max_download_rate'];
			$Default_max_uploads=$cfg['max_uploads'];
			$Default_maxport=$cfg['maxport'];
			$Default_minport=$cfg['minport'];
			$Default_rerequest_interval=$cfg['rerequest_interval'];
			$Default_location=DIRECTORY_SEPARATOR;
			$ownername=Uid2Username($myuid);
			include template('ajax_Url_Torrent');
		}elseif($id=='Creat_Torrent'){
			die('building.....');
		}elseif($id=='New_Feed'){
			$rssArray=GetRSSLinks();
			include template('ajax_icon_Feed');
		}elseif($id=='Edit_Torrent'){
			$torrentid=getRequestVar('torrentid');
				if(!is_numeric($torrentid)){
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
			$Default_location=$Bt->location;
			$size=formatBytesToKBMGGB($Bt->size);
			$ownername=Uid2Username($Bt->owner);
			// grub the file details
			$info=GrabTorrentInfo(TorrentIDtoTorrent($torrentid),'remove padding');
			$filearray=formatTorrentInfoFilesList($info['info']['files'],1);
			//grab the prio details
			$priolist=explode(',',$Bt->prio);
			include template('ajax_Edit_Torrent');
		}elseif($id=='_Admin_Setting'){
			$CronRobotLog=file_exists($cfg['cronwork_log'])?nl2br(file_get_contents($cfg['cronwork_log'])):'';
			include template('ajax_icon_admin_setting');
		}
}elseif($action=='tabs'){
	//if it is called via tab bar 
	$tab = getRequestVar('tab',Array('tab1','tab2','tab3','tab4','tab5','tab6','speedimg'));
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
				echo $fileInfo->inplace.$fileInfo->complete;
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
			$filesize=filesize($logfile);
				if(is_file($logfile)){
					$fh = fopen($logfile, 'r');
						if(!$fh || $filesize==0){
							echo '('._Current_No_Log.')';
						}else{
							$theData = fread($fh,$filesize );
							echo $theData;
						}
					fclose($fh);
				}
		}elseif($tab=='speedimg'){
			$upstr=$downstr=$comma='';
			$sql="SELECT `speedlog` FROM `tf_torrents` WHERE `id`='$torrentId'";
			$speedlog=unserialize($db->GetOne($sql));
				foreach($speedlog['down'] as $key => $speed){
					$upstr.=$comma.$speedlog['up'][$key];
					$downstr.=$comma.$speedlog['down'][$key];
					$comma=',';
				}
			$max=max(max($speedlog['up']),max($speedlog['down']));
			$max=$max<1?1:$max/25;
			$max=ceil($max)*25;
			$maxtext=$max.'KB/s';
			$halfmax=($max/2).'KB/s';
			$count=count($speedlog['up']);
			$timestamp=time();
			$time1=date('H:i',$timestamp-5*$count);
			$time2=date('H:i',$timestamp-2.5*$count);
			$time3=date('H:i',$timestamp);
			echo "http://chart.apis.google.com/chart?cht=lc&chs=700x160&chd=t:$downstr|$upstr&chds=0,$max&chco=ff0000,00ff00&chdl=Download|Upload&chtt=Speed+Chart&chg=5,25&chxt=y,x,x&chxl=0:|0|$halfmax|$maxtext|1:|$time1|$time2|$time3|2:||time|";
		}
}elseif($action=='form'){
	$id = getRequestVar('id',Array('Torrent_Search'));
		if($id=='Torrent_Search'){
			include ENGINE_ROOT.'include/ajax/form/torrent_search.php';
		}
}
?>
