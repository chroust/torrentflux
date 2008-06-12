<?
include_once("config.php");
include_once("functions.php");

$action=getRequestVar('action');
$torrent=getRequestVar('torrent');

	if(!in_array($action,array('Kill','Del','Start','Upload')) || !$torrent){
		exit('123');
	}
include_once("include/BtControl_Tornado.class.php");
	if($action=='Kill'){
		$Bt= new BtControl($torrent);
		$Bt->Kill();
	}elseif($action=='Del'){
		$Bt= new BtControl($torrent);
		$Bt->Del();
	}elseif($action=='Start'){
		$Bt= new BtControl($torrent);
		$Bt->Start();
	}elseif($action=='Upload'){
		foreach($HTTP_POST_FILES['torrents']['name'] as $thisfile){
			NewTorrent($thisfile);
		}
	}
?>