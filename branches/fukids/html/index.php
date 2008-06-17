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

$messages = "";

// set refresh option into the session cookie
if(array_key_exists("pagerefresh", $_GET))
{
    if(getRequestVar("pagerefresh") == "false")
    {
        $_SESSION['prefresh'] = false;
        header("location: index.php");
        exit();
    }

    if(getRequestVar("pagerefresh") == "true")
    {
        $_SESSION["prefresh"] = true;
        header("location: index.php");
        exit();
    }
}
// Check to see if QManager is running if not Start it.
if (checkQManager() == 0 )
{
    if ($cfg["AllowQueing"])
    {
        if (is_dir($cfg["path"]) && is_writable($cfg["path"]))
        {
            AuditAction($cfg["constants"]["QManager"], "QManager Not Running");
            sleep(2);
            startQManager($cfg["maxServerThreads"],$cfg["maxUserThreads"],$cfg["sleepInterval"]);
            sleep(2);
        }
        else
        {
            AuditAction($cfg["constants"]["error"], "Error starting Queue Manager -- TorrentFlux settings are not correct (path is not valid)");
            if (IsAdmin())
            {
                header("location: admin.php?op=configSettings");
                exit();
            }
            else
            {
                $messages .= "<b>Error</b> TorrentFlux settings are not correct (path is not valid) -- please contact an admin.<br>";
            }
        }
    }
}

$torrent = getRequestVar('torrent');

if(!empty($torrent))
{
    include_once("AliasFile.php");

    if ($cfg["enable_file_priority"])
    {
        include_once("setpriority.php");
        // Process setPriority Request.
        setPriority($torrent);
    }

    $spo = getRequestVar('setPriorityOnly');
    if (!empty($spo)){
        // This is a setPriortiyOnly Request.
    }else
    {
        // if we are to start a torrent then do so

        // check to see if the path to the python script is valid
        if (!is_file($cfg["btphpbin"]))
        {
            AuditAction($cfg["constants"]["error"], "Error  Path for ".$cfg["btphpbin"]." is not valid");
            if (IsAdmin())
            {
                header("location: admin.php?op=configSettings");
                exit();
            }
            else
            {
                $messages .= "<b>Error</b> TorrentFlux settings are not correct (path to python script is not valid) -- please contact an admin.<br>";
            }
        }
	
        $command = "";

        $rate = getRequestVar('rate');
        if (empty($rate))
        {
            if ($rate != "0")
            {
                $rate = $cfg["max_upload_rate"];
            }
        }
        $drate = getRequestVar('drate');
        if (empty($drate))
        {
            if ($drate != "0")
            {
                $drate = $cfg["max_download_rate"];
            }
        }
        $superseeder = getRequestVar('superseeder');
        if (empty($superseeder))
        {
            $superseeder = "0"; // should be 0 in most cases
        }
        $runtime = getRequestVar('runtime');
        if (empty($runtime))
        {
            $runtime = $cfg["torrent_dies_when_done"];
        }

        $maxuploads = getRequestVar('maxuploads');
        if (empty($maxuploads))
        {
            if ($maxuploads != "0")
            {
                $maxuploads = $cfg["max_uploads"];
            }
        }
        $minport = getRequestVar('minport');
        if (empty($minport))
        {
            $minport = $cfg["minport"];
        }
        $maxport = getRequestVar('maxport');
        if (empty($maxport))
        {
            $maxport = $cfg["maxport"];
        }
        $rerequest = getRequestVar("rerequest");
        if (empty($rerequest))
        {
            $rerequest = $cfg["rerequest_interval"];
        }
        $sharekill = getRequestVar('sharekill');

        if ($runtime == "True" )
        {
            $sharekill = "-1";
        }

        if (empty($sharekill))
        {
            if ($sharekill != "0")
            {
                $sharekill = $cfg["sharekill"];
            }
        }
        if ($cfg["AllowQueing"])
        {
            if(IsAdmin())
            {
                $queue = getRequestVar('queue');
                if($queue == 'on')
                {
                    $queue = "1";
                }else
                {
                    $queue = "0";
                }
            }
            else
            {
                $queue = "1";
            }
        }

        $crypto_allowed = getRequestVar('crypto_allowed');
        if (empty($crypto_allowed))
        {
            $crypto_allowed = $cfg["crypto_allowed"];
        }

        $crypto_only = getRequestVar('crypto_only');
        if (empty($crypto_only))
        {
            $crypto_only = $cfg["crypto_only"];
        }

        $crypto_stealth = getRequestVar('crypto_stealth');
        if (empty($crypto_stealth))
        {
            $crypto_stealth = $cfg["crypto_stealth"];
        }

        //$torrent = urldecode($torrent);
        $alias = getAliasName($torrent);
        $owner = getOwner($torrent);

        // The following lines of code were suggested by Jody Steele jmlsteele@stfu.ca
        // This is to help manage user downloads by their user names
        //if the user's path doesnt exist, create it
        if (!is_dir($cfg["path"]."/".$owner))
        {
            if (is_writable($cfg["path"]))
            {
                mkdir($cfg["path"]."/".$owner, 0777);
            }
            else
            {
                AuditAction($cfg["constants"]["error"], "Error -- " . $cfg["path"] . " is not writable.");
                if (IsAdmin())
                {
                    header("location: admin.php?op=configSettings");
                    exit();
                }
                else
                {
                    $messages .= "<b>Error</b> TorrentFlux settings are not correct (path is not writable) -- please contact an admin.<br>";
                }
            }
        }

        // create AliasFile object and write out the stat file
        $af = new AliasFile($cfg["torrent_file_path"].$alias.".stat", $owner);

        if ($cfg["AllowQueing"])
        {
            if($queue == "1")
            {
                $af->QueueTorrentFile();  // this only writes out the stat file (does not start torrent)
            }
            else
            {
                $af->StartTorrentFile();  // this only writes out the stat file (does not start torrent)
            }
        }
        else
        {
            $af->StartTorrentFile();  // this only writes out the stat file (does not start torrent)
        }

        if (usingTornado())
        {
            $command = escapeshellarg($runtime)." ".escapeshellarg($sharekill)." '".$cfg["torrent_file_path"].$alias.".stat' ".$owner." --responsefile '".$cfg["torrent_file_path"].$torrent."' --display_interval 5 --max_download_rate ". escapeshellarg($drate) ." --max_upload_rate ".escapeshellarg($rate)." --max_uploads ".escapeshellarg($maxuploads)." --minport ".escapeshellarg($minport)." --maxport ".escapeshellarg($maxport)." --rerequest_interval ".escapeshellarg($rerequest)." --super_seeder ".escapeshellarg($superseeder)." --crypto_allowed ".escapeshellarg($crypto_allowed)." --crypto_only ".escapeshellarg($crypto_only)." --crypto_stealth ".escapeshellarg($crypto_stealth);

            if(file_exists($cfg["torrent_file_path"].$alias.".prio")) {
                $priolist = explode(',',file_get_contents($cfg["torrent_file_path"].$alias.".prio"));
                $priolist = implode(',',array_slice($priolist,1,$priolist[0]));
                $command .= " --priority ".escapeshellarg($priolist);
            }
            if ($cfg["cmd_options"])
            	$command .= " ".escapeshellarg($cfg["cmd_options"]);
            
            $command .= " > /dev/null &";

            if ($cfg["AllowQueing"] && $queue == "1")
            {
                //  This file is being queued.
            }
            else
            {
                // This flie is being started manually.

                if (! array_key_exists("pythonCmd", $cfg))
                {
                    insertSetting("pythonCmd","/usr/bin/python");
                }

                if (! array_key_exists("debugTorrents", $cfg))
                {
                    insertSetting("debugTorrents", "0");
                }

                if (!$cfg["debugTorrents"])
                {
                    $pyCmd = escapeshellarg($cfg["pythonCmd"]) . " -OO";
                }else{
                    $pyCmd = escapeshellarg($cfg["pythonCmd"]);
                }

                $command = "cd " . $cfg["path"] . $owner . "; HOME=".$cfg["path"]."; export HOME; nohup " . $pyCmd . " " .escapeshellarg($cfg["btphpbin"]) . " " . $command;
            }

        }
        else
        {
            // Must be using the Original BitTorrent Client
            // This is now being required to allow Queing functionality
            //$command = "cd " . $cfg["path"] . $owner . "; nohup " . $cfg["btphpbin"] . " ".$runtime." ".$sharekill." ".$cfg["torrent_file_path"].$alias.".stat ".$owner." --responsefile \"".$cfg["torrent_file_path"].$torrent."\" --display_interval 5 --max_download_rate ". $drate ." --max_upload_rate ".$rate." --max_uploads ".$maxuploads." --minport ".$minport." --maxport ".$maxport." --rerequest_interval ".$rerequest." ".$cfg["cmd_options"]." > /dev/null &";
            $messages .= "<b>Error</b> BitTornado is only supported Client at this time.<br>";
        }

        // write the session to close so older version of PHP will not hang
        session_write_close();

        if($af->running == "3")
        {
            writeQinfo($cfg["torrent_file_path"]."queue/".$alias.".stat",$command);
            AuditAction($cfg["constants"]["queued_torrent"], $torrent."<br>Die:".$runtime.", Sharekill:".$sharekill.", MaxUploads:".$maxuploads.", DownRate:".$drate.", UploadRate:".$rate.", Ports:".$minport."-".$maxport.", SuperSeed:".$superseeder.", Rerequest Interval:".$rerequest);
            AuditAction($cfg["constants"]["queued_torrent"], $command);
        }
        else
        {
            // The following command starts the torrent running! w00t!
            passthru($command);

            AuditAction($cfg["constants"]["start_torrent"], $torrent."<br>Die:".$runtime.", Sharekill:".$sharekill.", MaxUploads:".$maxuploads.", DownRate:".$drate.", UploadRate:".$rate.", Ports:".$minport."-".$maxport.", SuperSeed:".$superseeder.", Rerequest Interval:".$rerequest);

            // slow down and wait for thread to kick off.
            // otherwise on fast servers it will kill stop it before it gets a chance to run.
            sleep(1);
        }

        if ($messages == "")
        {
            if (array_key_exists("closeme",$_POST))
            {
?>

<?php
               exit();
            }
            else
            {
                header("location: index.php");
                exit();
            }
        }
        else
        {
            AuditAction($cfg["constants"]["error"], $messages);
        }
    }
}


// Do they want us to get a torrent via a URL?
$url_upload = getRequestVar('url_upload');

if(! $url_upload == ''){
	include_once("include/BtControl_Tornado.class.php");
		$torrentid=UrlTorrent(getRequestVar('torrent'));
		$autostart=getRequestVar('autostart');
			if($autostart){
				$Bt= new BtControl($torrentid);
				$Bt->Start();
			}
	header("location: index.php");
}

// Handle the file upload if there is one
if(!empty($_FILES['torrents']['name'])){
	include_once("include/BtControl_Tornado.class.php");
			foreach($_FILES['torrents']['name'] as $key => $thisfile){
				$torrentid=NewTorrent($HTTP_POST_FILES['torrents']['tmp_name'][$key],$HTTP_POST_FILES['torrents']['name'][$key]);
					if($autostart){
						$Bt= new BtControl($torrentid);
						$Bt->Start();
					}
			}
			showmessage('ALL TORRENT ADDED',1);
}  // End File Upload


// if a file was set to be deleted then delete it
$delfile = SecurityClean(getRequestVar('delfile'));
if(! $delfile == ''){
	include_once("include/BtControl_Tornado.class.php");
	$Bt= new BtControl($delfile);
	$Bt->Delete();
}

// Did the user select the option to kill a running torrent?
$kill = getRequestVar('kill');
if(! $kill == '' && is_numeric($kill) ){
	include_once("include/BtControl_Tornado.class.php");
	$Bt= new BtControl($delfile);
	$Bt->Delete();
}

// Did the user select the option to remove a torrent from the Queue?
if(isset($_REQUEST["dQueue"]))
{
    $alias_file = SecurityClean(getRequestVar('alias_file'));
    $QEntry = getRequestVar('QEntry');

    // Is the Qinfo file still there?
    if (file_exists($cfg["torrent_file_path"]."queue/".$alias_file.".Qinfo"))
    {
        // Yes, then delete it and update the stat file.
        include_once("AliasFile.php");
        // We are going to write a '2' on the front of the stat file so that
        // it will be set back to New Status
        $the_user = getOwner($QEntry);
        // read the alias file
        // create AliasFile object
        $af = new AliasFile($cfg["torrent_file_path"].$alias_file, $the_user);

        if($af->percent_done > 0 && $af->percent_done < 100)
        {
            // has downloaded something at some point, mark it is incomplete
            $af->running = "0";
            $af->time_left = "Torrent Stopped";
        }

        if ($af->percent_done == 0 || $af->percent_done == "")
        {
            $af->running = "2";
            $af->time_left = "";
        }

        if ($af->percent_done == 100)
        {
            // Torrent was seeding and is now being stopped
            $af->running = "0";
            $af->time_left = "Download Succeeded!";
        }

        // Write out the new Stat File
        $af->WriteFile();

        // Remove Qinfo file.
        @unlink($cfg["torrent_file_path"]."queue/".$alias_file.".Qinfo");

        AuditAction($cfg["constants"]["unqueued_torrent"], $QEntry);
    }
    else
    {
        // torrent has been started... try and kill it.
        AuditAction($cfg["constants"]["unqueued_torrent"], $QEntry . "has been started -- TRY TO KILL IT");
        header("location: index.php?alias_file=".$alias_file."&kill=true&kill_torrent=".urlencode($QEntry));
        exit();
    }

    header("location: index.php");
    exit();
}

$drivespace = getDriveSpace($cfg["path"]);


/************************************************************

************************************************************/
include template('index');
?>
