<?
//this php is for doing action
include_once("include/functions.php");
//check and grab action
$action=getRequestVar('action',array('Kill','Del','Start','Upload','UrlUpload','Edit_Torrent'));
$torrentid=getRequestVar('torrentid');
	if((in_array($action,array('Kill','Del','Start','Edit_Torrent')) && !is_numeric($torrentid))){
		showmessage('no Variable: $torrent OR $torrent is not a intval',1);
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
		showmessage('Edited',1);
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
?>
