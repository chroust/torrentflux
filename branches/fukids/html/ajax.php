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
	$tab = getRequestVar('tab',Array('tab1','tab2','tab3','tab4','tab5'));
		if($tab=='tab1'){
			//normal
			echo '1';
		}elseif($tab=='tab2'){
			//Tracker
		}elseif($tab=='tab3'){
			//user
		}elseif($tab=='tab4'){
			//file
		}elseif($tab=='tab5'){
			//speed
		}
}
?>
