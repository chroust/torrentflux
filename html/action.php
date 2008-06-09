<?
include_once("config.php");
include_once("functions.php");

$action=getRequestVar('action');
$torrent=getRequestVar('torrent');
	if(!in_array($action,array('Kill','Del','Start')) || !$torrent){
		exit('123');
	}
include_once("include/BtControl_Tornado.class.php");

$Bt= new BtControl($torrent);
	if($action=='Kill'){
		$Bt->Kill();
	}elseif($action=='Del'){
		$Bt->Del();
	}elseif($action=='Start'){
		$Bt->Start();
	}
?>