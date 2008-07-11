<?
//this php is for doing action
include_once("include/functions.php");
//check and grab action
$id=getRequestVar('id',array('torrent','user'));
if($id=='torrent'){
$action=getRequestVar('action',array('Kill','Del','Start','Upload','UrlUpload','Edit_Torrent','_Del_n_Remove_Torrent','_Del_n_Remove_Torrent_And_Files','_Del_n_Remove_Files','torrent_download'));
$torrentid=getRequestVar('torrentid');
	if((in_array($action,array('Kill','Del','Start','Edit_Torrent','_Del_n_Remove_Torrent','_Del_n_Remove_Torrent_And_Files','_Del_n_Remove_Files')) && !is_numeric($torrentid)) && (in_array($action,array('Kill','Start')) && $torrentid!=='all')){
		showmessage('no Variable: $torrent OR $torrent is not a intval : '.$torrentid,1);
	}
include_once("include/BtControl_Tornado.class.php");
	if($action=='Kill'){
	// if user what kill the process
			if(is_numeric($torrentid)){
				$Bt= new BtControl($torrentid);
				$Bt->Kill();
				sleep(1);
				showmessage('',1,1);
			}elseif($torrentid=='all'){
				all('Kill');
				sleep(1);
			}
	}elseif($action=='Del'){
		//if user want delet the torrent
		$Bt= new BtControl($torrentid);
		$Bt->Delete();
		sleep(1);
		showmessage('',1,1);
	}elseif($action=='_Del_n_Remove_Torrent'){
		//if user want delet the torrent
		$Bt= new BtControl($torrentid);
		$Bt->Delete(1);
		sleep(1);
		showmessage('',1,1);
	}elseif($action=='_Del_n_Remove_Torrent_And_Files'){
		//if user want delet the torrent
		$Bt= new BtControl($torrentid);
		$Bt->Delete(1,1);
		sleep(1);
		showmessage('',1,1);
	}elseif($action=='_Del_n_Remove_Files'){
		//if user want delet the torrent
		$Bt= new BtControl($torrentid);
		$Bt->Delete(0,1);
		showmessage('',1,1);
	}elseif($action=='Start'){
	// if user what start the process
			if(is_numeric($torrentid)){
				$Bt= new BtControl($torrentid);
				$Bt->Start();
				sleep(1);
			}elseif($torrentid=='all'){
				all('Start');
				sleep(1);
			}
		showmessage('',1,1);
	}elseif($action=='Edit_Torrent'){
		//if user want edit the torrent
		$rate=intval(getRequestVar('rate'));
		$drate=intval(getRequestVar('drate'));
		$maxuploads=intval(getRequestVar('maxuploads'));
		$minport=intval(getRequestVar('minport'));
		$maxport=intval(getRequestVar('maxport'));
		//fix max port and min port in admin specific range
		$minport=$minport < $cfg['limitminport']? $cfg['minport']:$minport;
		$maxport=$maxport > $cfg['limitmaxport']? $cfg['maxport']:$maxport;
		
		$rerequest=intval(getRequestVar('rerequest'));
		$sharekill=intval(getRequestVar('sharekill'));
		$runtime=intval(getRequestVar('runtime'));
		$files=getRequestVar('files');
		$a=GrabTorrentInfo(torrentid2torrentname($torrentid));
		$maxi=sizeof($a['info']['files'])-1;
		$prio=$comma='';
		$narray=array();
			foreach($files as $value){
				$narray[$value]=1;
			}
			for($i=0;$i<=$maxi;$i++){
				$prio.=$comma.($narray[$i]?$narray[$i]:'-1');
				$comma=',';
			}
		$Bt= new BtControl($torrentid,"rate:$rate;drate:$drate;maxuploads:$maxuploads;minport:$minport;maxport:$maxport;rerequest:$rerequest;sharekill:$sharekill;runtime:$runtime;prio:$prio");
		$Bt->Kill();
		$Bt->Start();
		sleep(1);
		showmessage('',1,1);
	}elseif($action=='Upload'){
		//if user want upload the torrent from file
			$autostart=getRequestVar('autostart');
				if(!is_array($_FILES['torrents']['name'])){
					showmessage('TORRENT_NOT_FOUND_OR_THE_FILE_IS_TOO_LARGE',1);
				}
		$rate=intval(getRequestVar('rate'));
		$drate=intval(getRequestVar('drate'));
		$maxuploads=intval(getRequestVar('maxuploads'));
		$minport=intval(getRequestVar('minport'));
		$maxport=intval(getRequestVar('maxport'));
		$rerequest=intval(getRequestVar('rerequest'));
		$sharekill=intval(getRequestVar('sharekill'));
		$runtime=intval(getRequestVar('runtime'));
		//$sharekill=intval(getRequestVar('sharekill'));
			foreach($_FILES['torrents']['name'] as $key => $thisfile){
				$torrentid=NewTorrent($HTTP_POST_FILES['torrents']['tmp_name'][$key],$HTTP_POST_FILES['torrents']['name'][$key],"rate:$rate;drate:$drate;maxuploads:$maxuploads;minport:$minport;maxport:$maxport;rerequest:$rerequest;sharekill:$sharekill;runtime:$runtime");
					if($autostart){
						$Bt= new BtControl($torrentid);
						$Bt->Start();
						showmessage('',1,1);
					}else{
						?> OpenWindow('Edit_Torrent','<?=Edit_Torrent?>','icon','torrentid=<?=$torrentid?>');<?
					}
			}
	}elseif($action=='UrlUpload'){
		//if user want upload torrent from url
		$torrentid=UrlTorrent(getRequestVar('torrent'));
		$autostart=getRequestVar('autostart');
			if($autostart){
				$Bt= new BtControl($torrentid);
				$Bt->Start();
				showmessage('',1,1);
			}
			showmessage('',1,1);
	}elseif($action=='torrent_download'){
			if(!is_numeric($torrentid)){
				exit();
			}
		$torrentName=torrentid2torrentname($torrentid);
		header("Content-Disposition: attachment; filename=$torrentName");
		//header("Content-Type: application/octet-stream");
		readfile($cfg["torrent_file_path"].$torrentName);
	}
}elseif($id=='user'){
	$action=getRequestVar('action',array('Send_PM','_EDITUSER','Del_PM'));
		if($action=='Send_PM'){
			$message = getRequestVar('message');
			$to_user = getRequestVar('to_user');
				if($message==''){
					showmessage('no message typed',1);
				}elseif(empty($to_user)){
					showmessage('no target user',1);
				}
			$to_all = getRequestVar('to_all');
			$to_all = (!empty($to_all))?1:0;
			$force_read = getRequestVar('force_read');
			$force_read = (!empty($force_read) && $isadmin)?1:0;
			$message = check_html($message, "nohtml");
			SaveMessage($to_user, $cfg['user'], $message, $to_all, $force_read);
			showmessage('send ok');
		}elseif($action=='_EDITUSER'){
			$pass1 = getRequestVar('pass1');
			$pass2 = getRequestVar('pass2');
			$hideOffline = getRequestVar('hideOffline');
			$theme = getRequestVar('theme');
			$language = getRequestVar('language');
				if($pass1 != ""){
					$_SESSION['user'] = md5($cfg["pagetitle"]);
				}
			UpdateUserProfile($cfg["user"], $pass1, $hideOffline, $theme, $language);
			JSReload();
		}elseif($action=='Del_PM'){
			$delete = getRequestVar('delete');
			if(!empty($delete)){
				DeleteMessage($delete);
			}
		}
}else{
	showmessage('no id'.$id);
}
?>
