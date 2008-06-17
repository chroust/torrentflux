<?
include_once("include/functions.php");

$action=getRequestVar('action',array('Kill','Del','Start','Upload','UrlUpload'));
$torrentid=getRequestVar('torrentid');
	if((in_array($action,array('Kill','Del','Start')) && !is_numeric($torrentid))){
		showmessage('no Variable: $torrent OR $torrent is not a intval',1);
	}
include_once("include/BtControl_Tornado.class.php");
	if($action=='Kill'){
		$Bt= new BtControl($torrentid);
		$Bt->Kill();
		echo 'Killed';
	}elseif($action=='Del'){
		$Bt= new BtControl($torrentid);
		$Bt->Delete();
		echo 'Deleted';
	}elseif($action=='Start'){
		$Bt= new BtControl($torrentid);
		$Bt->Start();
		echo 'Started';
	}elseif($action=='Upload'){
			$autostart=getRequestVar('autostart');
				if(!is_array($_FILES['torrents']['name'])){
					showmessage('TORRENT NOT FOUND OR THE FILE IS TOO LARGE',1);
				}
			foreach($_FILES['torrents']['name'] as $key => $thisfile){
				$torrentid=NewTorrent($HTTP_POST_FILES['torrents']['tmp_name'][$key],$HTTP_POST_FILES['torrents']['name'][$key]);
					if($autostart){
						$Bt= new BtControl($torrentid);
						$Bt->Start();
					}
			}
			showmessage('ALL TORRENT ADDED',1);
	}elseif($action=='UrlUpload'){
		$torrentid=UrlTorrent(getRequestVar('torrent'));
		$autostart=getRequestVar('autostart');
			if($autostart){
				$Bt= new BtControl($torrentid);
				$Bt->Start();
			}
	}
?>
