<?
function checkSpaceLimit($uid=0,$user_id='',$space_limit=0){
	global $cfg,$db;
	$uid=intval($uid);
		if(!$cfg['force_dl_in_home_dir']){
			return true;
		}
		if($uid==0){
			//if want to check all users' limit
			$userlist=GetUserList();
				foreach($userlist as $user){
						if(checkSpaceLimit($user['uid'],$user['user_id'],$user['space_limit'])===false){
							return false;
						}
				}
			return true;
		}else{
			// if want to check user space limit
			// get user info 
				if(!$user_id){
					$userinfo=GrabUserData($uid);
					$space_limit=$userinfo['space_limit'];
					$user_id=$userinfo['user_id'];
				}
				if($space_limit >0){
					return (($space_limit*1024*1024)<dirsize($cfg["path"]."/".$user_id))?false:true;
				}else{
					return true;
				}
		}
}

function dirsize($path){
		if(!is_readable($path)){
			return 0;
		}
		if (!is_dir($path)){
			return filesize($path);
		}
    $size=0;
		foreach(scandir($path) as $file){
				if ($file=='.' or $file=='..'){
					continue;
				}
			$size+=dirsize($path.'/'.$file);
		}
  return $size;
}
?>