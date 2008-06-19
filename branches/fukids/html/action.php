<?
//this php is for doing action
include_once("include/functions.php");
//check and grab action
$id=getRequestVar('id',array('torrent','user'));
if($id=='torrent'){
$action=getRequestVar('action',array('Kill','Del','Start','Upload','UrlUpload','Edit_Torrent'));
$torrentid=getRequestVar('torrentid');
	if((in_array($action,array('Kill','Del','Start','Edit_Torrent')) && !is_numeric($torrentid))){
		showmessage('no Variable: $torrent OR $torrent is not a intval : '.$torrentid,1);
	}
include_once("include/BtControl_Tornado.class.php");
	if($action=='Kill'){
		// if user what kill the process
		$Bt= new BtControl($torrentid);
		$Bt->Kill();
		echo 'Killed';
	}elseif($action=='Edit_Torrent'){
		//if user want edit the torrent
		$rate=intval(getRequestVar('rate'));
		$drate=intval(getRequestVar('drate'));
		$maxuploads=intval(getRequestVar('maxuploads'));
		$minport=intval(getRequestVar('minport'));
		$maxport=intval(getRequestVar('maxport'));
		$rerequest=intval(getRequestVar('rerequest'));
		$sharekill=intval(getRequestVar('sharekill'));
		$runtime=intval(getRequestVar('runtime'));
		
		$Bt= new BtControl($torrentid,"rate:$rate;drate:$drate;maxuploads:$maxuploads;minport:$minport;maxport:$maxport;rerequest:$rerequest;sharekill:$sharekill;runtime:$runtime");
		$Bt->Kill();
		$Bt->Start();
		echo('Edited');
	}elseif($action=='Del'){
		//if user want delet the torrent
		$Bt= new BtControl($torrentid);
		$Bt->Delete();
		echo 'Deleted';
	}elseif($action=='Start'){
		//if user want start the process
		$Bt= new BtControl($torrentid);
		$Bt->Start();
		echo 'Started';
	}elseif($action=='Upload'){
		//if user want upload the torrent from file
			$autostart=getRequestVar('autostart');
				if(!is_array($_FILES['torrents']['name'])){
					showmessage('TORRENT NOT FOUND OR THE FILE IS TOO LARGE',1);
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
			}
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
			showmessage('_UPDATED_NEED_REFLESH',1);
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
