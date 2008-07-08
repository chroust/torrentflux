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

checkUserPath();

// Setup some defaults if they are not set.
$del = getRequestVar('del');
$down = getRequestVar('down');
$tar = getRequestVar('tar');
$rename = getRequestVar('newname');
$OldName = getRequestVar('rename');
$moveinto = getRequestVar('moveinto');
$dir = getRequestVar('dir');
checkpath($dir);
$dir =  rawurldecode(getRequestVar('dir'));
if($moveinto){
	checkpath($moveinto);
	$target = getRequestVar('target');
	checkpath($target);
	rename($cfg["path"].$moveinto,$cfg["path"].$target.'/'.basename($moveinto));
	die('ok');
}
if($rename && $OldName){
	checkpath($rename);
	checkpath($OldName);
	rename($cfg["path"].rawurldecode($OldName),$cfg["path"].rawurldecode($rename));
	die('ok');
}

// Are we to delete something?
if ($del != ""){
    $current = "";
    // The following lines of code were suggested by Jody Steele jmlsteele@stfu.ca
    // this is so only the owner of the file(s) or admin can delete
    if(IsAdmin($cfg["user"]) || preg_match("/^" . $cfg["user"] . "/",$del))
    {
        // Yes, then delete it

        // we need to strip slashes twice in some circumstances
        // Ex.  If we are trying to delete test/tester's file/test.txt
        //    $del will be "test/tester\\\'s file/test.txt"
        //    one strip will give us "test/tester\'s file/test.txt
        //    the second strip will give us the correct
        //        "test/tester's file/test.txt"

        $del = stripslashes(stripslashes($del));

        if (!ereg("(\.\.\/)", $del))
        {
            avddelete($cfg["path"].$del);

            $arTemp = explode("/", $del);
            if (count($arTemp) > 1)
            {
                array_pop($arTemp);
                $current = implode("/", $arTemp);
            }
            AuditAction($cfg["constants"]["fm_delete"], $del);
        }
        else
        {
            AuditAction($cfg["constants"]["error"], "ILLEGAL DELETE: ".$cfg['user']." tried to delete ".$del);
        }
    }
    else
    {
        AuditAction($cfg["constants"]["error"], "ILLEGAL DELETE: ".$cfg['user']." tried to delete ".$del);
    }
}

// Are we to download something?
if ($down != "" && $cfg["enable_file_download"])
{
    $current = "";
    // Yes, then download it

    // we need to strip slashes twice in some circumstances
    // Ex.  If we are trying to download test/tester's file/test.txt
    // $down will be "test/tester\\\'s file/test.txt"
    // one strip will give us "test/tester\'s file/test.txt
    // the second strip will give us the correct
    //  "test/tester's file/test.txt"

    $down = stripslashes(stripslashes($down));

    if (!ereg("(\.\.\/)", $down))
    {
        $path = $cfg["path"].$down;

        $p = explode(".", $path);
        $pc = count($p);

        $f = explode("/", $path);
        $file = array_pop($f);
        $arTemp = explode("/", $down);
        if (count($arTemp) > 1)
        {
            array_pop($arTemp);
            $current = implode("/", $arTemp);
        }

        if (file_exists($path))
        {
            header("Content-type: application/octet-stream\n");
            header("Content-disposition: attachment; filename=\"".$file."\"\n");
            header("Content-transfer-encoding: binary\n");
            header("Content-length: " . file_size($path) . "\n");

            // write the session to close so you can continue to browse on the site.
            session_write_close();

            //$fp = fopen($path, "r");
            $fp = popen("cat \"$path\"", "r");
            fpassthru($fp);
            pclose($fp);

            AuditAction($cfg["constants"]["fm_download"], $down);
            exit();
        }
        else
        {
            AuditAction($cfg["constants"]["error"], "File Not found for download: ".$cfg['user']." tried to download ".$down);
        }
    }
    else
    {
        AuditAction($cfg["constants"]["error"], "ILLEGAL DOWNLOAD: ".$cfg['user']." tried to download ".$down);
    }
}
// Are we to download something?
if ($tar != "" && $cfg["enable_file_download"])
{
    $current = "";
    // Yes, then tar and download it

    // we need to strip slashes twice in some circumstances
    // Ex.  If we are trying to download test/tester's file/test.txt
    // $down will be "test/tester\\\'s file/test.txt"
    // one strip will give us "test/tester\'s file/test.txt
    // the second strip will give us the correct
    //  "test/tester's file/test.txt"

    $tar = stripslashes(stripslashes($tar));

    if (!ereg("(\.\.\/)", $tar))
    {
        // This prevents the script from getting killed off when running lengthy tar jobs.
        ini_set("max_execution_time", 3600);
        $tar = $cfg["path"].$tar;

        $arTemp = explode("/", $tar);
        if (count($arTemp) > 1)
        {
            array_pop($arTemp);
            $current = implode("/", $arTemp);
        }

        // Find out if we're really trying to access a file within the
        // proper directory structure. Sadly, this way requires that $cfg["path"]
        // is a REAL path, not a symlinked one. Also check if $cfg["path"] is part
        // of the REAL path.
        if (is_dir($tar))
        {
            $sendname = basename($tar);

            switch ($cfg["package_type"])
            {
                Case "tar":
                    $command = "tar cf - \"".addslashes($sendname)."\"";
                    break;
                Case "zip":
                    $command = "zip -0r - \"".addslashes($sendname)."\"";
                    break;
                default:
                    $cfg["package_type"] = "tar";
                    $command = "tar cf - \"".addslashes($sendname)."\"";
                    break;
            }

            // HTTP/1.0
            header("Pragma: no-cache");
            header("Content-Description: File Transfer");
            header("Content-Type: application/force-download");
            header('Content-Disposition: attachment; filename="'.$sendname.'.'.$cfg["package_type"].'"');

            // write the session to close so you can continue to browse on the site.
            session_write_close();

            // Make it a bit easier for tar/zip.
            chdir(dirname($tar));
            passthru($command);

            AuditAction($cfg["constants"]["fm_download"], $sendname.".".$cfg["package_type"]);
            exit();
        }
        else
        {
            AuditAction($cfg["constants"]["error"], "Illegal download: ".$cfg['user']." tried to download ".$tar);
        }
    }
    else
    {
        AuditAction($cfg["constants"]["error"], "ILLEGAL TAR DOWNLOAD: ".$cfg['user']." tried to download ".$tar);
    }
	exit();
  //  header("Location: dir.php?dir=".urlencode($current));
}


$dir = $dir."/";

if (!file_exists($cfg["path"].$dir)){
    echo "<strong>".$dir."</strong> could not be found or is not valid.";
}else{
	$DirList=GetDirectoryArray($cfg["path"].$dir);
	include template('dir');
}

function GetDirectoryArray($dirName){

	//get the list
	$dirLIST=glob(preg_quote( $dirName, DIRECTORY_SEPARATOR).'*');
	$dirlist=$filelist=array();
	foreach ($dirLIST as $filename) {
		$file=Array('is_dir','filesize','name','shortname');
		//get the detail
			if(is_dir($filename)){
				$basename=basename($filename);
				$shortname=substr($basename, 0, 20);
				$file=Array('is_dir'=>'1',
					'name'=>$basename,
					'path'=>$filename,
					'shortname'=>$shortname,
					'icon'=>'images/folder.png',
					'type'=>'folder',
				);
				$dirlist[]=$file;
			}else{
				$basename=basename($filename);
				$filesize=formatBytesToKBMGGB(filesize ($filename));
				$shortname=substr($basename, 0, 20);
				$type=getExtension($filename);
                $image="images/time.gif";
                $imageOption="images/files/$type.png";
					if (file_exists("./".$imageOption)){
						$image = $imageOption;
					}
				$file=Array('is_dir'=>'0',
					'size'=>$filesize,
					'name'=>$basename,
					'shortname'=>$shortname,
					'icon'=>$image,
					'type'=>$type,
				);
				$filelist[]=$file;
			}
	}
		//put it in to an array 
		$result=array_merge($dirlist,$filelist);
	return $result;
}


// ***************************************************************************
// ***************************************************************************
// Checks for the location of the users directory
// If it does not exist, then it creates it.
function checkUserPath()
{
    global $cfg;
    // is there a user dir?
    if (!is_dir($cfg["path"].$cfg["user"]))
    {
        //Then create it
        mkdir($cfg["path"].$cfg["user"], 0777);
    }
}


// This function returns the extension of a given file.
// Where the extension is the part after the last dot.
// When no dot is found the noExtensionFile string is
// returned. This should point to a 'unknown-type' image
// time by default. This string is also returned when the
// file starts with an dot.
function getExtension($fileName)
{
    $noExtensionFile="unknown"; // The return when no extension is found

    //Prepare the loop to find an extension
    $length = -1*(strlen($fileName)); // The maximum negative value for $i
    $i=-1; //The counter which counts back to $length

    //Find the last dot in an string
    while (substr($fileName,$i,1) != "." && $i > $length) {$i -= 1; }

    //Get the extension (with dot)
    $ext = substr($fileName,$i);

    //Decide what to return.
    if (substr($ext,0,1)==".") {$ext = substr($ext,((-1 * strlen($ext))+1)); } else {$ext = $noExtensionFile;}

    //Return the extension
    return strtolower($ext);
}
function checkpath($path){
	if(strpos(stripslashes($path),"../")===false){
		return true;
	}else{
		exit('wrong path');
	}
}
?>
