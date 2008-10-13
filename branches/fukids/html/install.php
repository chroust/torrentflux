<?php
$action=isset($_GET['action'])?$_GET['action']:'index';
$need_writable_list=array('cache');

	if($action=='index'){
		include 'install/template.html';
	}elseif($action=='checkDatabaseConfig'){
		echo CheckDataBaseConfig();
	}elseif($action=='CheckWritable'){
		echo CheckWritable();
	}elseif($action=='CreatDB'){
			if (!CheckDataBaseConfig()){
				exit();
			}
		include('config.php');
			if($cfg["db_type"]=='mysql'){
				$db=mysql_connect($cfg["db_host"],$cfg["db_user"],$cfg["db_pass"]);
				mysql_select_db($cfg["db_name"], $db);
				$sql=file_get_contents('install/mysql_torrentflux.sql');
				$result = mysql_query($sql, $db);
					if(!$result){
						echo '0'.mysql_error($db);
					}else{
						echo '1';
					}
			}else{
				echo '2';
			}
	}
	
	
	function CheckDataBaseConfig(){
		include_once('config.php');
		include_once('adodb/adodb.inc.php');
		$db = NewADOConnection($cfg["db_type"]);
		$db->Connect($cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]);
		return $db?1:0;
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