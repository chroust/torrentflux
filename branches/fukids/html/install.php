<?php
$action=isset($_GET['action'])?$_GET['action']:'index';
$need_writable_list=array('cache');
include'install/lang/eng.php';
	if($action=='index'){
		include 'install/template/index.html';
	}elseif($action=='checkPHP'){
		$php_version=strpos(phpversion(), '-')===false?
			phpversion()
			:substr(phpversion(),0,strpos(phpversion(), '-'));
		include 'install/template/checkPHP.html';
	}elseif($action=='checkDatabaseConfig'){
		include 'install/template/checkDatabaseConfig.html';
	}elseif($action=='CheckWritable'){
		include 'install/template/CheckWritable.html';
	}elseif($action=='CreatDB'){
			if (!CheckDataBaseConfig()){
				exit();
			}
			$error=1;
		include('config.php');
			if($cfg["db_type"]=='mysql'){
				$db=mysql_connect($cfg["db_host"],$cfg["db_user"],$cfg["db_pass"]);
				mysql_select_db($cfg["db_name"], $db);
				$sql=file_get_contents('install/mysql_torrentflux.sql');
				$result = mysql_query($sql, $db);
					if(!$result){
						//echo $LANG['CreatDB_ERR'].mysql_error($db);
					}else{
						$error=0;
						//echo $LANG['CreatDB_OK'];
					}
			}else{
				//echo $LANG['CreatDB_NotSupported'];
			}
		include 'install/template/CreatDB.html';
	}
	
	function CheckDataBaseConfig(){
		include_once('config.php');
		include_once('adodb/adodb.inc.php');
		$db = NewADOConnection($cfg["db_type"]);
		$db->Connect($cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
		return $db->ErrorNo()?0:1;
	}
	
	function CheckWritable(){
		GLOBAL $need_writable_list;
			foreach($need_writable_list as $file){
					if(!is_writable($file)){
						return 0;
					}
			}
		return 1;
	}
	
	
	
?>