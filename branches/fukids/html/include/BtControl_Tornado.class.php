<?php
include_once("config.php");
include_once("functions.php");
// class for writing .stat
include_once("AliasFile.php");
include_once("setpriority.php");

Class BtControl {
	function BtControl($torrentid,$options=''){
		GLOBAL $cfg,$db;
		$this->torrentid=intval($torrentid);
		$sql='SELECT torrent FROM tf_torrents WHERE `id`=\''.$this->torrentid.'\'';
		$result=$db->GetOne($sql);
		showError($db,$sql);
		$this->torrent = $result;
		$torrent=$result;
		$this->CheckTorrent($torrent);
		//grab the options to variables
		extract(Options2Vars($options,Array('rate','drate','superseeder','runtime','maxuploads','minport','maxport','rerequest','sharekill','queue')),  EXTR_OVERWRITE);
		//use default if no options are set
		$this->rate = empty($rate)?$cfg["max_upload_rate"]:$rate;
		$this->drate = empty($drate)?$cfg["max_download_rate"]:$drate;
		$this->superseeder = "0";
		$this->runtime = empty($runtime)?$cfg["torrent_dies_when_done"]:$runtime;
		$this->maxuploads = empty($maxuploads)?$cfg["max_uploads"]:$maxuploads;
		$this->minport = empty($minport)?$cfg["minport"]:$minport;
		$this->maxport = empty($maxport)?$cfg["maxport"]:$maxport;
		$this->rerequest = empty($rerequest)?$cfg["rerequest_interval"]:$rerequest;
		$this->sharekill= ($this->runtime == "True")? "-1": $cfg["sharekill"];
		$this->alias = getAliasName($torrent);
		$this->owner = getOwner($torrent);
		$this->queue= (IsAdmin() AND $queue == 'on')?"1":"0";
	}
	
	function Start(){
		GLOBAL $cfg;
		// check if home dir exist, if not, creat it 
		//* this is not unix user home dir
		CheckHomeDir($this->owner);
		//creat .stat file
		CheckHung($this->alias);
			if(CheckRunning($this->alias)!==0){
				echo 'already running'.$this->alias;
				exit();
			}
		$af = new AliasFile($cfg["torrent_file_path"].$this->alias.".stat", $this->owner);
			if ($cfg["AllowQueing"] AND $queue == "1"){
				$af->QueueTorrentFile();  // this only writes out the stat file (does not start torrent)
			}else{
				$af->StartTorrentFile();  // this only writes out the stat file (does not start torrent)
			}
			if ($cfg["AllowQueing"] && $this->queue == "1"){
				//  This file is being queued.
			}else{
		// build the command
					if (!$cfg["debugTorrents"]){
						$pyCmd = escapeshellarg($cfg["pythonCmd"]) . " -OO";
					}else{
						$pyCmd = escapeshellarg($cfg["pythonCmd"]);
					}
				$command.= "cd " . $cfg["path"].$owner . ";";
				$command.= " HOME=".$cfg["path"].";";
				$command.= "export HOME; nohup " . $pyCmd;
				$command.= " ".escapeshellarg($cfg["btphpbin"])." ";
				$command.= escapeshellarg($this->runtime)." ".escapeshellarg($this->sharekill)." '".$cfg["torrent_file_path"].$this->alias.".stat' ".$this->owner;
				$command.= " --responsefile '".$cfg["torrent_file_path"].$this->torrent."'";
				$command.= " --display_interval 1 ";
				$command.= " --max_download_rate ". escapeshellarg($this->drate) ;
				$command.= " --max_upload_rate ".escapeshellarg($this->rate);
				$command.= " --max_uploads ".escapeshellarg($this->maxuploads);
				$command.= " --minport ".escapeshellarg($this->minport);
				$command.= " --maxport ".escapeshellarg($this->maxport);
				$command.= " --rerequest_interval ".escapeshellarg($this->rerequest);
				$command.= " --super_seeder ".escapeshellarg($this->superseeder);
					if(file_exists($cfg["torrent_file_path"].$this->alias.".prio")) {
						$priolist = explode(',',file_get_contents($cfg["torrent_file_path"].$this->alias.".prio"));
						$priolist = implode(',',array_slice($priolist,1,$priolist[0]));
						$command .= " --priority ".escapeshellarg($priolist);
					}
				$command .= " ".escapeshellarg($cfg["cmd_options"]);
				$command .= " 1>> ".$cfg["torrent_file_path"].$this->alias.".log";
				$command .= " 2>> ".$cfg["torrent_file_path"].$this->alias.".log";
				$command .=" &"; 
					// insert setting if it is not set yet
					if (! array_key_exists("pythonCmd", $cfg)){
						insertSetting("pythonCmd","/usr/bin/python");
					}
					if (! array_key_exists("debugTorrents", $cfg)){
						insertSetting("debugTorrents", "0");
					}
					
				passthru($command);
				sleep(1);
			}
	}
	
	// function for Killing the process, but not the file 
	function Kill(){
		global $cfg;
		// write the new state to .state
		$af = new AliasFile($cfg["torrent_file_path"].$this->alias.'.stat', $this->owner);
			if($af->percent_done < 100){
				// The torrent is being stopped but is not completed dowloading
				//$af->percent_done = ($af->percent_done+100)*-1;
				$af->running = "0";
				$af->time_left = "Torrent Stopped";
			}else{
				// Torrent was seeding and is now being stopped
				$af->percent_done = 100;
				$af->running = "0";
				$af->time_left = "Download Succeeded!";
			}
		$af->WriteFile();
		// see if the torrent process is hung.
		CheckHung($this->alias);
		sleep(1);
        passthru("kill ".GetPid($this->alias));
        // try to remove the pid file
        @unlink($cfg["torrent_file_path"].$this->alias.".pid");
		AuditAction($cfg["constants"]["kill_torrent"], $this->torrent);
	}

	function Delete($delTorrent=1,$delFile=0){
		GLOBAL $cfg;
		if ( !(($cfg["user"] == getOwner($this->torrent)) || IsAdmin())){
			AuditAction($cfg["constants"]["error"], $cfg["user"]." attempted to delete ".$this->torrent);
		}
		//kill the process first
		$this->Kill();
		DelTorrentSQL($this->torrentid);
		@unlink($cfg["torrent_file_path"].$this->alias.'.stat');
		@unlink($cfg["torrent_file_path"].$this->alias.'.log');
			if($delTorrent){
				@unlink($cfg["torrent_file_path"].$this->torrent);
			}
			if($delFile){
				// try to remove the QInfo if in case it was queued.
				@unlink($cfg["torrent_file_path"]."queue/".$alias_file.".Qinfo");
				// try to remove the pid file
				@unlink($cfg["torrent_file_path"].$this->alias.".pid");
				@unlink($cfg["torrent_file_path"].$this->alias.".prio");
			}
		AuditAction($cfg["constants"]["delete_torrent"], $this->torrent);
	}
	
	function CheckTorrent($torrent){
		Global $cfg;
			if(!is_file($cfg["torrent_file_path"].$this->torrent)){
				showmessage($torrent.' not exist');
			}
	}
}


?>
