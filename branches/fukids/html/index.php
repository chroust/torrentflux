<?php

if($_GET['url_upload']){
	header("Location: action.php?id=torrent&action=UrlUpload&usejs=1&closewindow=0&torrent=".$_GET['url_upload']);
	exit();
}

include_once 'include/functions.php';
$action=getRequestVar('action',array('XML'));
	if($action=='XML'){
		$id=getRequestVar('id',array('torrent','stat'));
			if($id=='torrent'){
				$indexurl=$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"];
				include_once(ENGINE_ROOT.'include/rss_generator.class.php');
				$Requiredstatus=getRequestVar('status');
				$Requiredusers=getRequestVar('users');
				$output=Listtorrent($Requiredusers,$Requiredstatus,'DESC');
				$rss_channel = new rssGenerator_channel();
				$rss_channel->atomLinkHref = '';
				$rss_channel->title = 'Torrentflux';
				$rss_channel->link = $indexurl;
				$rss_channel->description = 'Torrent list.';
				//$rss_channel->language = 'en-us';
				$rss_channel->generator = 'PHP RSS Feed Generator';
					foreach($output as $thisitem){
						$item = new rssGenerator_item();
						$item->title = $thisitem['title'];
						$item->description = $thisitem['percent'].','.$thisitem['down_speed'].','.$thisitem['up_speed'].','.$thisitem['size'].','.$thisitem['seeds'].','.$thisitem['peers'];
						$item->link = $indexurl;
						$item->guid_isPermaLink=false;
						$item->guid = $thisitem['id'];
						$item->pubDate = gmdate('D, d M Y H:i:s O',$thisitem['timeStarted']);
						$rss_channel->items[] = $item;
					}
				$rss_feed = new rssGenerator_rss();
				$rss_feed->encoding = 'UTF-8';
				$rss_feed->version = '2.0';
				header('Content-Type: text/xml');
				echo $rss_feed->createFeed($rss_channel); 
			}elseif($id=='stat'){
				$Global_TotalTransfer=GetTransferCount();
				$Global_TotalQueue=getNumberOfQueuedTorrents();
				$statidArray=GetGlobalStatidCount();
				$TotalCurrentSpeed=$cfg['totalupload']+$cfg['totaldownload'];
				header('Content-Type: text/xml');
				echo '<?xml version="1.0" encoding="UTF-8"?>';
				include template('xml_stat');
			}
	}
?>
