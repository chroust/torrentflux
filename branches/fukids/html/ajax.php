<?php
include_once('db.php');
include_once("settingsfunctions.php");
include_once("functions.php");
session_name("TorrentFlux");
session_start();
$db = getdb();
loadSettings();

$action = getRequestVar('action',Array('icon','jsonTorrent','tabs'));

if($action=='icon'){
	$id = getRequestVar('id',Array('Upload_Torrent','Url_Torrent','Creat_Torrent','New_Feed'));
		if($id=='Upload_Torrent'){
			?>
			<form method="POST" id="UploadTorrentForm" action="action.php?action=Upload"  enctype="multipart/form-data" >
				<input type="file" name="torrents[]" /><br />
				<input type="checkbox" value="1" name="autostart" id="autostart" />
				<label for="autostart"><?php echo _CLICK_TO_AUTOSTART ?></label>
				<input type="submit">
			</form>
			<?
		}elseif($id=='Url_Torrent'){
			?>
			<form method="POST" action="action.php?action=UrlUpload">
				Url: <input type="text" name="torrent">
				<input type="checkbox" value="1" name="autostart" id="autostart" />
				<label for="autostart"><?php echo _CLICK_TO_AUTOSTART ?></label>
				<input type="submit">
			</form>
			<?
		}elseif($id=='Creat_Torrent'){
			die('building.....');
		}elseif($id=='New_Feed'){
		
		}
}elseif($action=='tabs'){
	$tab = getRequestVar('tab',Array('tab1','tab2','tab3','tab4','tab5','tab6'));
	$torrentId=intval(getRequestVar('torrentId'));
		if(!$torrentId){
			showmessage('torrentId Error',1);
		}
		if($tab=='tab1'){
			//normal
			$info=GrabTorrentInfo(TorrentIDtoTorrent($torrentId));
			$info['info']['pieces']='';
			echo "<pre>".var_dump($info).'</pre>';
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
			$info=GrabTorrentInfo(TorrentIDtoTorrent($torrentId));
				if(is_array($info['info']['files'])){
					//if this is a list of file
					foreach($info['info']['files'] as $file){
						echo $file['path.utf-8']['0'].'<br />';
					}
				}else{
					echo $info['info']['name.utf-8'];
				}
		}elseif($tab=='tab5'){
			//speed
			?>
			<img src="" alt="" id="thisspeed">
			<script type="text/javascript">
			echo('speed loaded');
			if($defined(downSpeed[selecting.id])){
				downSpeedLength=downSpeed[selecting.id].length;
				var chd1='';
				var max=0;
				var comma='';
					for ( i = 0; i < downSpeedLength; i++) {
						chd1=chd1+comma+downSpeed[selecting.id][i];
							if(downSpeed[selecting.id][i] > max){
								max=downSpeed[selecting.id][i];
							}
						comma=',';
					}
				var chd2='';
				var comma='';
					for ( i = 0; i < downSpeedLength; i++) {
						chd2=chd2+comma+upSpeed[selecting.id][i];
							if(upSpeed[selecting.id][i] > max){
								max=upSpeed[selecting.id][i];
							}
						comma=',';
					}
				max=max==0?1:max;
				var src='http://chart.apis.google.com/chart?cht=lc&chs=700x200&chd=t:'+chd1+'|'+chd2+'&chds=0,'+max+'&chco=ff0000,00ff00&chdl=Download|Upload&chtt=Speed+Chart&chg=5,25&chxt=y,y,x,x&chxl=0:|0|'+MaxdownSpeed[selecting.id]+'|1:||Speed(KB/s)|2:|0|'+uploadcount*UpdateInterval+'|3:||time(s)|';
				$('thisspeed').src=src;
			}
			</script>
			<?
		}elseif($tab=='tab6'){
			//log
			$logfile=$cfg["torrent_file_path"].torrent2log(TorrentIDtoTorrent($torrentId));
			$fh = fopen($logfile, 'r');
			$log = fread($fh, filesize($logfile));
			fclose($fh);
			echo $log;

		}
}
?>
