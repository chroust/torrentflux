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
