<?php

/*************************************************************
*  TorrentFlux - PHP Torrent Manager
*  www.torrentflux.com
**************************************************************/
/*
    This file is part of TorrentFlux.

    TorrentFlux is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    TorrentFlux is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with TorrentFlux; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

include_once("include/functions.php");
$op=getRequestVar('op',array('start','stop'));
$cfg['phpbin']='php';
if($op=='start'){
		if(file_exists($cfg['cronwork_log'])){
			unlink($cfg['cronwork_log']);
		}
	$command = 'cd '.ENGINE_ROOT.'; ';
	$command.= $cfg['phpbin'].' '.ENGINE_ROOT.'cronwork.php';
	$command.= ' >> '.$cfg['cronwork_log'];
	$command.= ' &';
	showmessage($command);
	passthru($command);
	sleep(1);
}elseif($op=='stop'){
		if(CheckCronRobot())
			touch($dieCall);
	sleep(6);
}
?>