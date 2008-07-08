<?php

// this is a class for controlling bittorrent process
include_once("functions.php");
// class for writing .stat
include_once("AliasFile.php");

Class BtControl {
	function BtControl($torrentid,$options=''){
		GLOBAL $cfg,$db;
		$this->torrentid=intval($torrentid);
		// grab torrent config from database
		$sql='SELECT file_name ,torrent,rate,drate,superseeder,runtime,maxuploads,minport,maxport,rerequest,sharekill,owner_id,prio FROM tf_torrents WHERE `id`=\''.$this->torrentid.'\'';
		$recordset = $db->Execute($sql);
		list($this->file_name,$this->torrent, $this->rate, $this->drate, $this->superseeder,
		$this->runtime,$this->maxuploads,$this->minport,$this->maxport,
		$this->rerequest,$this->sharekill,$this->owner,$this->prio
		) = $recordset->FetchRow();
		showError($db,$sql);
		//check if torrent is ok or not
		$this->CheckTorrent($this->torrent);
		//grab the options to variables
		extract(Options2Vars($options,Array('rate','drate','superseeder','runtime','maxuploads','minport','maxport','rerequest','sharekill','queue','prio')),  EXTR_OVERWRITE);
		//use default in database if no options are set
		$this->rate = empty($rate)?$this->rate:intval($rate);
		$this->drate = empty($drate)?$this->drate:intval($drate);
		$this->superseeder = "0";
		$this->runtime = empty($runtime)?$this->runtime:intval($runtime);
		$this->maxuploads = empty($maxuploads)?$this->maxuploads:intval($maxuploads);
		$this->minport = empty($minport)?$this->minport:intval($minport);
		$this->maxport = empty($maxport)?$this->maxport:intval($maxport);
		$this->rerequest = empty($rerequest)?$this->rerequest:intval($rerequest);
		$this->sharekill= (empty($sharekill) OR $sharekill == o) ? $this->sharekill: intval($sharekill);
		$this->prio=empty($prio)?$this->prio:str_replace('\'','',$prio);
		$this->alias = getAliasName($torrent);
		$this->queue= (IsAdmin() AND $queue == 'on')?"1":"0";
		// update the torrent config to the database
		$sql = 'UPDATE `tf_torrents`  SET `rate`=\''.$this->rate.'\',`drate`=\''.$this->drate.'\',`superseeder`=\''.$this->superseeder.'\',
		`runtime`=\''.$this->runtime.'\',`maxuploads`=\''.$this->maxuploads.'\',`minport`=\''.$this->minport.'\',
		`maxport`=\''.$this->maxport.'\',`rerequest`=\''.$this->rerequest.'\',`sharekill`=\''.$this->sharekill.'\',`sharekill`=\''.$this->sharekill.'\',`prio`=\''.$this->prio.'\' WHERE `id`=\''.$torrentid.'\' LIMIT 1';
		$recordset = $db->Execute($sql);
		showError($db,$sql);
		$this->pid=torrent2pid($this->torrent);
		$this->stat=torrent2stat($this->torrent);
		$this->log=torrent2log($this->torrent);
	}
	function Start(){
		GLOBAL $cfg;
		// check if home dir exist, if not, creat it 
		//* this is not unix user home dir
		CheckHomeDir($this->owner);
		//check if it is hung
		CheckHung($this->torrent);
			if(CheckRunning($this->pid)!==0){
				showmessage($this->torrent.'is already running',1);
			}
			if(!checkTransferLimit($this->owner)){
				showmessage('_TRANSFER_LIMIT_OVERFLOW',1,0);
			}
		$af = new AliasFile($cfg["torrent_file_path"].torrent2stat($this->torrent), $this->owner);
			if($af->downtotal || $af->uptotal)
				saveXfer($af->torrentowner,$af->downtotal,$af->uptotal);

			if ($cfg["AllowQueing"] AND $queue == "1"){
				$af->QueueTorrentFile();  // this only writes out the stat file (does not start torrent)
			}else{
				$af->StartTorrentFile();  // this only writes out the stat file (does not start torrent)
			}
			if ($cfg["AllowQueing"] && $this->queue == "1"){
				//  This file is being queued.
			}else{
					@unlink($cfg["torrent_file_path"].$this->log);

		// build the command
					if (!$cfg["debugTorrents"]){
						$pyCmd = escapeshellarg($cfg["pythonCmd"]) . " -OO";
					}else{
						$pyCmd = escapeshellarg($cfg["pythonCmd"]);
					}
				//change to download DIR
				$command.= "cd " . $cfg["path"].$owner . ";";
				$command.= " HOME=".$cfg["path"].";";
				$command.= "export HOME; nohup " . $pyCmd;
				$command.= " ".escapeshellarg($cfg["btphpbin"])." ";
				$command.= escapeshellarg($this->runtime).' '.escapeshellarg($this->sharekill)." '".$cfg["torrent_file_path"].$this->stat.'\'' .' '.$this->owner;
				$command.= " --responsefile '".$cfg["torrent_file_path"].$this->torrent."'";
				//update stat interval
				$command.= " --display_interval 2 ";
				$command.= " --max_download_rate ". escapeshellarg($this->drate) ;
				$command.= " --max_upload_rate ".escapeshellarg($this->rate);
				$command.= " --max_uploads ".escapeshellarg($this->maxuploads);
				$command.= " --minport ".escapeshellarg($this->minport);
				$command.= " --maxport ".escapeshellarg($this->maxport);
				$command.= " --rerequest_interval ".escapeshellarg($this->rerequest);
				$command.= " --super_seeder ".escapeshellarg($this->superseeder);
				$command .= " --priority ".escapeshellarg($this->prio);
				$command .= " ".escapeshellarg($cfg["cmd_options"]);
				$command .= " 1>> ".$cfg["torrent_file_path"].$this->log;
				$command .= " 2>> ".$cfg["torrent_file_path"].$this->log;
				$command .=" &";
					// insert setting if it is not set yet
					if (! array_key_exists("pythonCmd", $cfg)){
						insertSetting("pythonCmd","/usr/bin/python");
					}
					if (! array_key_exists("debugTorrents", $cfg)){
						insertSetting("debugTorrents", "0");
					}
				passthru($command);
			}
		sleep(1);
	}
	
	// function for Killing the process, but not the file 
	function Kill(){
		global $cfg;
		// write the new state to .state
		$af = new AliasFile($cfg["torrent_file_path"].$this->stat, $this->owner);
			if($af->downtotal || $af->uptotal)
				saveXfer($af->torrentowner,$af->downtotal,$af->uptotal);
			if($af->percent_done < 100){
				// The torrent is being stopped but is not completed dowloading
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
        passthru("kill ".$this->pid);
		sleep(1);
        // try to remove the pid file
        @unlink($cfg["torrent_file_path"].$this->pid);
		sleep(1);
		CheckHung($this->torrent);
		AuditAction($cfg["constants"]["kill_torrent"], $this->torrent);
	}

	function Delete($delTorrent=0,$delFile=0){
		GLOBAL $cfg;
		if ( !(($cfg["user"] == getOwner($this->torrent)) || IsAdmin())){
			AuditAction($cfg["constants"]["error"], $cfg["user"]." attempted to delete ".$this->torrent);
		}
		//kill the process first
		$this->Kill();
		sleep(1);
		DelTorrentSQL($this->torrentid);
		@unlink($cfg["torrent_file_path"].$this->stat);
		@unlink($cfg["torrent_file_path"].$this->log);
		// try to remove the QInfo if in case it was queued.
		@unlink($cfg["torrent_file_path"]."queue/".$alias_file.".Qinfo");
		// try to remove the pid file
		@unlink($cfg["torrent_file_path"].$this->pid);
		@unlink($cfg["torrent_file_path"].$this->alias.".prio");
		$delTorrent=1;
			if($delTorrent){
				@unlink($cfg["torrent_file_path"].$this->torrent);
			}
			if($delFile){
			}
		AuditAction($cfg["constants"]["delete_torrent"], $this->torrent);
		sleep(1);
	}
	
	function CheckTorrent($torrent){
		Global $cfg;
			if(!is_file($cfg["torrent_file_path"].$this->torrent)){
				showmessage('torrent: '.$this->torrent.' not exist');
			}
	}
}


?>
