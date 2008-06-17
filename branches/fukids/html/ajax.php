<?php

// this file is for returning html and javascript when ever there is a ajax call
include_once("include/functions.php");

$action = getRequestVar('action',Array('listtorrent','icon','jsonTorrent','tabs'));

if($action=='listtorrent'){
	include('include/ajax.list_torrent.php');
}elseif($action=='icon'){
	$id = getRequestVar('id',Array('Upload_Torrent','Url_Torrent','Creat_Torrent','New_Feed','Edit_Torrent'));
		if($id=='Upload_Torrent'){
			$Default_max_upload_rate=$cfg['max_upload_rate'];
			$Default_max_download_rate=$cfg['max_download_rate'];
			$Default_max_uploads=$cfg['max_uploads'];
			$Default_maxport=$cfg['maxport'];
			$Default_minport=$cfg['minport'];
			$Default_rerequest_interval=$cfg['rerequest_interval'];
			include template('ajax_Upload_Torrent');
		}elseif($id=='Url_Torrent'){
			$Default_max_upload_rate=$cfg['max_upload_rate'];
			$Default_max_download_rate=$cfg['max_download_rate'];
			$Default_max_uploads=$cfg['max_uploads'];
			$Default_maxport=$cfg['maxport'];
			$Default_minport=$cfg['minport'];
			$Default_rerequest_interval=$cfg['rerequest_interval'];
			include template('ajax_Url_Torrent');
		}elseif($id=='Creat_Torrent'){
			die('building.....');
		}elseif($id=='New_Feed'){
			die('building.....');
		}elseif($id=='Edit_Torrent'){
			$torrentid=intval(getRequestVar('torrentid'));
				if(!$torrentid){
					showmessage('torrent is not a intval',1);
				}
			include_once("include/BtControl_Tornado.class.php");
			$Bt= new BtControl($torrentid);
			$file_name=$Bt->file_name;
			$Default_max_upload_rate=$Bt->rate;
			$Default_max_download_rate=$Bt->drate;
			$Default_max_uploads=$Bt->maxuploads;
			$Default_maxport=$Bt->maxport;
			$Default_minport=$Bt->minport;
			$Default_rerequest_interval=$Bt->rerequest;
			$Default_sharekill=$Bt->sharekill;
			include template('ajax_Edit_Torrent');
		}
}elseif($action=='tabs'){
	$tab = getRequestVar('tab',Array('tab1','tab2','tab3','tab4','tab5','tab6'));
	$torrentId=intval(getRequestVar('torrentId'));
		if(!$torrentId){
			showmessage('torrentId Error',1);
		}
		if($tab=='tab1'){
			//normal
			$torrentfile=TorrentIDtoTorrent($torrentId);
			$info=GrabTorrentInfo($torrentfile);
			$info['info']['pieces']='';
			$torrent_size = $info["info"]["piece length"] * (strlen($info["info"]["pieces"]) / 20);
			
			?>
			<table>
			<tr><td>Torrent File:</td><td> <?=$torrentfile?> </td></tr>
			<tr><td>Directory Name:</td><td> <?=htmlentities($info['info']['name'], ENT_QUOTES)?></td></tr>
			<?
			if(array_key_exists('comment',$info)){
               ?><tr><td>Comment:</td><td> ".htmlentities($info['comment'], ENT_QUOTES)?></td></tr><?
            }
			echo "<tr><td>Created:</td><td>".date("F j, Y, g:i a",$info['creation date'])."</td></tr>";
            echo "<tr><td>Torrent Size:</td><td>".$torrent_size." (".formatBytesToKBMGGB($torrent_size).")</td></tr>";
            echo "<tr><td>Chunk size:</td><td>".$info['info']['piece length']." (".formatBytesToKBMGGB($info['info']['piece length']).")</td></tr>";
    
			?>
			</table>
			<?
		//	echo "<pre>".var_dump($info).'</pre>';
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
			/*
			$info=GrabTorrentInfo(TorrentIDtoTorrent($torrentId));
				if(is_array($info['info']['files'])){
					//if this is a list of file
					foreach($info['info']['files'] as $file){
						echo $file['path.utf-8']['0'].'<br />';
					}
				}else{
					echo $info['info']['name.utf-8'];
				}
			*/
			$torrentfile=TorrentIDtoTorrent($torrentId);
			$btmeta=GrabTorrentInfo($torrentfile);
			include_once('include/dir.class.php');
            $dirnum =  (array_key_exists('files',$btmeta['info']))?count($btmeta['info']['files']):0;
            if ( is_readable($prioFileName)){
                $prio = split(',',file_get_contents($prioFileName));
                $prio = array_splice($prio,1);
            }else{
                $prio = array();
                for($i=0;$i<$dirnum;$i++)
                {
                    $prio[$i] = -1;
                }
            }
            $tree = new dir("/",$dirnum,isset($prio[$dirnum])?$prio[$dirnum]:-1);
    
            if (array_key_exists('files',$btmeta['info'])){
                foreach( $btmeta['info']['files'] as $filenum => $file){
                    $depth = count($file['path.utf-8']);
                    $branch =& $tree;
                    for($i=0; $i < $depth; $i++){
                        if ($i != $depth-1){
                            $d =& $branch->findDir($file['path.utf-8'][$i]);
    
                            if($d){
                                $branch =& $d;
                            }else{
                                $dirnum++;
                                $d =& $branch->addDir(new dir($file['path.utf-8'][$i], $dirnum, (isset($prio[$dirnum])?$prio[$dirnum]:-1)));
                                $branch =& $d;
                            }
                        }else{
                            $branch->addFile(new file($file['path.utf-8'][$i]." (".$file['length'].")",$filenum,$file['length'],$prio[$filenum]));
                        }
    
                    }
                }
            }
			
            if (array_key_exists('files',$btmeta['info'])){
				echo $tree->draw(-1);

			}
		}elseif($tab=='tab5'){
			//speed
			?>
			<img src="" alt="" id="thisspeed">
			<script type="text/javascript">
			var speed_updateIntervals = 5;
			var updateGraph=function(){
			if($defined(downSpeed[selecting])){
				downSpeedLength=downSpeed[selecting].length;
				var chd1='';
				var max=0;
				var comma='';
					for ( i = 0; i < downSpeedLength; i++) {
						chd1=chd1+comma+downSpeed[selecting][i];
							if(downSpeed[selecting][i] > max){
								max=downSpeed[selecting][i];
							}
						comma=',';
					}
				var chd2='';
				var comma='';
					for ( i = 0; i < downSpeedLength; i++) {
						chd2=chd2+comma+upSpeed[selecting][i];
							if(upSpeed[selecting][i] > max){
								max=upSpeed[selecting][i];
							}
						comma=',';
					}
				max=max==0?1:max;
				basetime=reloadedcount*UpdateInterval;
				lastesttime=basetime+uploadcount*UpdateInterval;
				middletime=(lastesttime-basetime)/2;
				var MaxdownSpeedselecting=MaxdownSpeed[selecting]>250?MaxdownSpeed[selecting]:250;
				var src='http://chart.apis.google.com/chart?cht=lc&chs=700x190&chd=t:'+chd1+'|'+chd2+'&chds=0,'+max+'&chco=ff0000,00ff00&chdl=Download|Upload&chtt=Speed+Chart&chg=5,25&chxt=y,y,x,x&chxl=0:|0|'+MaxdownSpeedselecting+'|1:||Speed(KB/s)|2:|'+basetime+'s|'+middletime+'s|'+lastesttime+'s|3:||time(s)|';
				$('thisspeed').src=src;
				graphtimer= setTimeout("updateGraph()",speed_updateIntervals*1000);
			}
				
			}
			window.addEvent('TabReady', function() {
			updateGraph();
			}).addEvent('TabExit', function() {
				graphtimer=$empty;
				updateGraph=$empty;
			});

			</script>
			<?
		}elseif($tab=='tab6'){
			//log
			$logfile=$cfg["torrent_file_path"].torrent2log(TorrentIDtoTorrent($torrentId));
			$fh = fopen($logfile, 'r');
				if ($fh) {
					while (!feof($fh)){
						$buffer = fgets($fh, 4096); // Read a line.
						echo $buffer.'<br />';
					}
					fclose($fh);
				}else{
					echo _Current_No_Log;
				}
		}
}
?>
