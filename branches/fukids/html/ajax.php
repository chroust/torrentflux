<?php

// this file is for returning html and javascript when ever there is a ajax call
//most of them are called via javascript::OpenWindow()
include_once("include/functions.php");
//group by how  it is called
$action = getRequestVar('action',Array('listtorrent','icon','jsonTorrent','tabs','rightclick','tips'));

if($action=='listtorrent'){
	include('include/ajax.list_torrent.php');
}elseif($action=='tips'){
	$id = getRequestVar('id',Array('user_profile'));
		if($id=='user_profile'){
			$uid=intval(getRequestVar('uid'));
			$UsrData=GrabUserData($uid);
			include template('ajax_Tips_user_profile');
		}
}elseif($action=='rightclick'){
	$id = getRequestVar('id',Array('_SEND_PM','_EDITUSER','_VIEW_PM'));
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
		}
}elseif($action=='icon'){
	// if it is called via icon bar
	$id = getRequestVar('id',Array('Upload_Torrent','Url_Torrent','Creat_Torrent','New_Feed','Edit_Torrent'));
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
			$info=GrabTorrentInfo(TorrentIDtoTorrent($torrentId),1);
				foreach($info['info']['files'] as $file){
					echo $file['path.utf-8']['0'].'<br />';
			}
		}elseif($tab=='tab5'){
			//speed
			?>
			<img src="" alt="" id="thisspeed">
			<script type="text/javascript">
			var speed_updateIntervals = 5;
			var updateGraph=function(){
			if($defined(downSpeed[selecting])){
				downSpeedLength=downSpeed[selecting].length;
				var chd1='';
				var max=0;
				var comma='';
					for ( i = 0; i < downSpeedLength; i++) {
						chd1=chd1+comma+downSpeed[selecting][i];
							if(downSpeed[selecting][i] > max){
								max=downSpeed[selecting][i];
							}
						comma=',';
					}
				var chd2='';
				var comma='';
					for ( i = 0; i < downSpeedLength; i++) {
						chd2=chd2+comma+upSpeed[selecting][i];
							if(upSpeed[selecting][i] > max){
								max=upSpeed[selecting][i];
							}
						comma=',';
					}
				max=max==0?1:max;
				basetime=reloadedcount*UpdateInterval;
				lastesttime=basetime+uploadcount*UpdateInterval;
				middletime=(lastesttime-basetime)/2;
				var MaxdownSpeedselecting=MaxdownSpeed[selecting]>250?MaxdownSpeed[selecting]:250;
				var src='http://chart.apis.google.com/chart?cht=lc&chs=700x190&chd=t:'+chd1+'|'+chd2+'&chds=0,'+max+'&chco=ff0000,00ff00&chdl=Download|Upload&chtt=Speed+Chart&chg=5,25&chxt=y,y,x,x&chxl=0:|0|'+MaxdownSpeedselecting+'|1:||Speed(KB/s)|2:|'+basetime+'s|'+middletime+'s|'+lastesttime+'s|3:||time(s)|';
				$('thisspeed').src=src;
				graphtimer= setTimeout("updateGraph()",speed_updateIntervals*1000);
			}
				
			}
			window.addEvent('TabReady', function() {
			updateGraph();
			}).addEvent('TabExit', function() {
				graphtimer=$empty;
				updateGraph=$empty;
			});

			</script>
			<?
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
}
?>
