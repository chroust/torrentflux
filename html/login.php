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

// ADODB support.
include_once('db.php');
include_once("settingsfunctions.php");

// Create Connection.
$db = getdb();

loadSettings();

if (!defined("IMG_JPG")) define("IMG_JPG", 2);
    

if (in_array("gfx", $_REQUEST) && $_REQUEST["gfx"] == "gfx" && file_exists("images/code_bg.jpg"))
{
    // Check gd is loaded AND that jpeg image type is supported:
    if (extension_loaded('gd') && (imagetypes() & IMG_JPG))
    {
        $code = getCode($_REQUEST["rnd"]);

        $image = ImageCreateFromJPEG("images/code_bg.jpg");
        $text_color = ImageColorAllocate($image, 80, 80, 80);
        
        Header("Content-type: image/jpeg");
        ImageString ($image, 5, 12, 2, $code, $text_color);
        ImageJPEG($image, '', 75);
        ImageDestroy($image);
        die();
    }
    else
    {
        header("Content-type: application/octet-stream\n");
        header("Content-transfer-encoding: binary\n");

        $fp = popen("cat images/red.gif", "r");
        fpassthru($fp);
        pclose($fp);

        die();
    }
}

session_name("TorrentFlux");
session_start();
include_once("config.php");
include("themes/".$cfg["default_theme"]."/index.php");
global $cfg;
if(isset($_SESSION['user']))
{
    header("location: index.php");
    exit;
}
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $cfg["pagetitle"] ?></title>
    <meta http-equiv="pragma" content="no-cache" />
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="themes/<?php echo $cfg["default_theme"] ?>/style.css" rel="stylesheet">
    <style>
        .form-signin {
            max-width: 330px;
            padding: 15px;
            margin: 0 auto;
        }
    </style>
</head>
<body>

<script type="text/javascript">
<!--
function loginvalidate()
{
    msg = "";
    pass = document.theForm.iamhim.value;
    user = document.theForm.username.value;
    if (user.length < 1)
    {
        msg = msg + "* Username is required\n";
        document.theForm.username.focus();
    }
    if(pass.length<1)
    {
        msg = msg + "* Password is required\n";
        if (user.length > 0)
        {
            document.theForm.iamhim.focus();
        }
    }

    if (msg != "")
    {
        alert("Check the following:\n\n" + msg);
        return false;
    }
}
-->
</script>

<div class="container">

<?php

$user = strtolower(getRequestVar('username'));

$iamhim = addslashes(getRequestVar('iamhim'));

$create_time = time();

// Check for user
if(!empty($user) && !empty($iamhim))
{
    $sec_code = getRequestVar('security');
    $rnd_number = getRequestVar('rnd_chk');
    $log_msg = "";
    $allow_login = true;
        
    if (extension_loaded('gd') && (imagetypes() & IMG_JPG) && array_key_exists("security_code",$cfg) && $cfg["security_code"])
    {
        if ($sec_code == getCode($rnd_number))
        {
            $allow_login = true;
        }
        else
        {
            $allow_login = false;
            $log_msg = "Invalid Security Code: ". $sec_code . " for " . $user;
        }
    }
    
    /* First User check */
    $next_loc = "index.php";
    $sql = "SELECT count(*) FROM tf_users";
    $result = $db->query($sql);
    $user_count = $result->fetchColumn();
    if($user_count == 0 && $allow_login)
    {
        // This user is first in DB.  Make them super admin.
        // this is The Super USER, add them to the user table

        $record = array(
                        ':user_id'=>$user,
                        ':password'=>md5($iamhim),
                        ':hits'=>1,
                        ':last_visit'=>$create_time,
                        ':time_created'=>$create_time,
                        ':user_level'=>2,
                        ':hide_offline'=>0,
                        ':theme'=>$cfg["default_theme"],
                        ':language_file'=>$cfg["default_language"]
                        );
        $sTable = 'tf_users';

        $sql = "INSERT INTO $sTable (
                                      'user_id',
                                      'password',
                                      'hits',
                                      'last_visit',
                                      'time_created',
                                      'user_level',
                                      'hide_offline',
                                      'theme','language_file') 
                VALUES(
                        ':user_id',
                        ':password',
                        ':hits',
                        ':last_visit',
                        ':time_created',
                        ':user_level',
                        ':hide_offline',
                        ':theme',
                        ':language_file'
                        )";

//        $sql = $db->GetInsertSql($sTable, $record);

        $sth = $db->prepare($sql);
        $sth->execute();
        showError($db,$sql);

        // Test and setup some paths for the TF settings
        $pythonCmd = $cfg["pythonCmd"];
        $btphpbin = getcwd() . "/TF_BitTornado/btphptornado.py";
        $tfQManager = getcwd() . "/TF_BitTornado/tfQManager.py";
        $maketorrent = getcwd() . "/TF_BitTornado/btmakemetafile.py";
        $btshowmetainfo = getcwd() . "/TF_BitTornado/btshowmetainfo.py";
        $tfPath = getcwd() . "/downloads/";

        if (!isFile($cfg["pythonCmd"]))
        {
            $pythonCmd = trim(shell_exec("which python"));
            if ($pythonCmd == "")
            {
                $pythonCmd = $cfg["pythonCmd"];
            }
        }

        $settings = array(
                            "pythonCmd" => $pythonCmd,
                            "btphpbin" => $btphpbin,
                            "tfQManager" => $tfQManager,
                            "btmakemetafile" => $maketorrent,
                            "btshowmetainfo" => $btshowmetainfo,
                            "path" => $tfPath
                        );

        saveSettings($settings);
        AuditAction($cfg["constants"]["update"], "Initial Settings Updated for first login.");
        $next_loc = "admin.php?op=configSettings";
    }
        
    if ($allow_login)
    {
        $sql = "SELECT uid, hits, hide_offline, theme, language_file FROM tf_users WHERE user_id=:user_id AND password=:password";
        $result = $db->prepare($sql);
        $result->execute([
            ':user_id' => $user,
            ':password' => md5($iamhim)
        ]);
        showError($db,$sql);
    
        list(
        $uid,
        $hits,
        $cfg["hide_offline"],
        $cfg["theme"],
        $cfg["language_file"]) = $result->fetchColumn(4);
    
        if(!array_key_exists("shutdown",$cfg))
            $cfg['shutdown'] = '';
        if(!array_key_exists("upload_rate",$cfg))
            $cfg['upload_rate'] = '';
    
        if($result->rowCount()==1)
        {
            // Add a hit to the user
            $hits++;
    
            $sql = 'select * from tf_users where uid = :uid';
            $rs = $db->prepare( $sql );
            $rs->execute([':uid' => $uid]);
            showError($db, $sql);
    
            $rec = array(
                            ':hits'=>$hits,
                            ':last_visit'=>'NOW()',
                            ':theme'=>$cfg['theme'],
                            ':language_file'=>$cfg['language_file'],
                            ':shutdown'=>$cfg['shutdown'],
                            ':upload_rate'=>$cfg['upload_rate'],
                            ':uid' => $uid
                        );
            $sql = "UPDATE tf_users SET hits = :hits,last_visit = :last_visit,theme = :theme,language_file = :language_file,shutdown = :shutdown,upload_rate = :upload_rate WHERE uid = :uid";
            
            $result = $db->prepare( $sql );
            $result->execute($rec);
            showError($db, $sql);
    
            $_SESSION['user'] = $user;
            session_write_close();
    
            header("location: ".$next_loc);
            exit();
        }
        else
        {
            $allow_login = false;
            $log_msg = "FAILED AUTH: ".$user;
        }
    }
    
    if (!$allow_login)
    {
        AuditAction($cfg["constants"]["access_denied"], $log_msg);
        echo '<div align="center">Login failed.<br>Please try again.</div>';
    }
}
?>

                        <form class="form-signin" name="theForm" action="login.php" method="post" onsubmit="return loginvalidate()">

                            <h2 class="form-signin-heading"><?php echo $cfg["pagetitle"] ?> Login</h2>
                            <label for="inputEmail" class="sr-only">Username</label>
                            <input type="email" id="inputEmail" class="form-control" name="username" placeholder="Email address" required="" autofocus="">
                            <label for="inputPassword" class="sr-only">Password</label>
                            <input type="password" id="inputPassword" name="iamhim" class="form-control" placeholder="Password" required="">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" value="remember-me"> Remember me
                                </label>
                            </div>
                                <button class="btn btn-lg btn-primary btn-block button" type="submit" value="Login">Sign in</button>

<?php
if (extension_loaded('gd') && (imagetypes() & IMG_JPG) && array_key_exists("security_code", $cfg) && $cfg["security_code"])
{
    mt_srand ((double)microtime()*1000000);
	$rnd = mt_rand(0, 1000000);
?>
                                <img border="1" align="middle" src="?gfx=gfx&rnd=<?php echo $rnd ?>">:
                                <input type="Text" name="security" value="" size="15" style="font-family:verdana,helvetica,sans-serif; font-size:9px; color:#000" />
                            <input type="hidden" name="rnd_chk" value="<?php echo $rnd ?>">
<?php 
}
?>

                        </form>

</div>

<script language="JavaScript">
    document.theForm.username.focus();
</script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script src="themes/<?php echo $cfg["default_theme"] ?>/bootstrap.min.js"></script>
</body>
</html>


<?php
ob_end_flush();

?>

