
<html>
<head>
    <title><?=$cfg["pagetitle"]?></title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
    <LINK REL="StyleSheet" HREF="themes/<?=$cfg["theme"]?>/style.css" TYPE="text/css">
    <META HTTP-EQUIV="Pragma" CONTENT="no-cache" charset="<?=_CHARSET?>">
    
<? if((!isset($_SESSION['prefresh']) || ($_SESSION['prefresh'] == true))) { ?>
     <meta http-equiv="REFRESH" content="<?=$cfg["page_refresh"]?>;URL=index.php">
<script language="JavaScript">
    var var_refresh =<?=$cfg["page_refresh"]?>;
    function UpdateRefresh() {
        span_refresh.innerHTML = String(var_refresh--);
        setTimeout("UpdateRefresh();", 1000);
    }
</script>
   
<? } ?>
<div id="overDiv" style="position:absolute;visibility:hidden;z-index:1000;"></div>
<script language="JavaScript">
    var ol_closeclick = "1";
    var ol_close = "<font color=#ffffff><b>X</b></font>";
    var ol_fgclass = "fg";
    var ol_bgclass = "bg";
    var ol_captionfontclass = "overCaption";
    var ol_closefontclass = "overClose";
    var ol_textfontclass = "overBody";
    var ol_cap = "&nbsp;Torrent Status";
</script>
<script src="overlib.js" type="text/javascript"></script>
<script language="JavaScript">
function ShowDetails(name_file)
{
  window.open (name_file,'_blank','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=430,height=225')
}
function StartTorrent(name_file)
{
    myWindow = window.open (name_file,'_blank','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=700,height=530')
}
function ConfirmDelete(file)
{
    return confirm("<?=_ABOUTTODELETE?>: " + file)
}
</script>
</head>

<body onLoad="UpdateRefresh();" topmargin="8" bgcolor="<?=$cfg["main_bgcolor"]?>">

<div align="center">
<? if(($messages != "")) { ?>
<table border="1" cellpadding="10" bgcolor="#ff9b9b">
<tr>
    <td><div align="center"><?=$messages?></div></td>
</tr>
</table><br><br>
<? } ?>
<table border="0" cellpadding="0" cellspacing="0" width="760">
<tr>
    <td>
<table border="1" bordercolor="<?=$cfg["table_border_dk"]?>" cellpadding="4" cellspacing="0" width="100%">
<tr>
    <td colspan="2" background="themes/<?=$cfg["theme"]?>/images/bar.gif">
    
<? echo DisplayTitleBar($cfg["pagetitle"]);; ?>
    </td>
</tr>
<tr>
    <td bgcolor="<?=$cfg["table_header_bg"]?>">
    <table width="100%" cellpadding="3" cellspacing="0" border="0">
    <tr>
        <form name="form_file" action="index.php" method="post" enctype="multipart/form-data">
        <td>
        <?=_SELECTFILE?>:<br>
        <input type="File" name="torrents[]" size="40">
        <input type="Submit" value="<?=_UPLOAD?>">
        </td>
        </form>
    </tr>
    <tr>
        <form name="form_url" action="index.php" method="post">
        <td>
        <hr>
        <?=_URLFILE?>:<br>
        <input type="text" name="url_upload" size="50">
        <input type="Submit" value="<?=_GETFILE?>">
        </td>
        </form>
    </tr>
<?php
if ($cfg["enable_search"])
{
?>
    <tr>
        <form name="form_search" action="torrentSearch.php" method="get">
        <td>
        <hr>
        Torrent <?=_SEARCH?>:<br>
        <input type="text" name="searchterm" size="30" maxlength="50">
<?php
        echo buildSearchEngineDDL($cfg["searchEngine"])
?>
        <input type="Submit" value="<?=_SEARCH?>">
        </td>
        </form>
    </tr>
<?php
}
?>
    </table>

    </td>
    <td bgcolor="<?=$cfg["table_data_bg"]?>" width="310" valign="top">
        <table width="100%" cellpadding="1" border="0">
        <tr>
        <td valign="top">
        <b><?=_TORRENTLINKS?>:</b><br>
<?php
        $arLinks = GetLinks();
        if (is_array($arLinks))
        {
            foreach($arLinks as $link)
            {
                echo "<a href=\"".$link['url']."\" target=\"_blank\"><img src=\"images/arrow.gif\" width=9 height=9 title=\"".$link['url']."\" border=0 align=\"baseline\">".$link['sitename']."</a><br>\n";
            }
        }
        echo "</ul></td>";

        $arUsers = GetUsers();
        $arOnlineUsers = array();
        $arOfflineUsers = array();

        for($inx = 0; $inx < count($arUsers); $inx++)
        {
            if(IsOnline($arUsers[$inx]))
            {
                array_push($arOnlineUsers, $arUsers[$inx]);
            }
            else
            {
                array_push($arOfflineUsers, $arUsers[$inx]);
            }
        }

        echo "<td bgcolor=\"".$cfg["table_data_bg"]."\" valign=\"top\">";
        echo "<b>"._ONLINE.":</b><br>";

        for($inx = 0; $inx < count($arOnlineUsers); $inx++)
        {
            echo "<a href=\"message.php?to_user=".$arOnlineUsers[$inx]."\">";
            echo "<img src=\"images/user.gif\" width=17 height=14 title=\"\" border=0 align=\"bottom\">". $arOnlineUsers[$inx];
            echo "</a><br>\n";
        }

        // Does the user want to see offline users?
        if ($cfg["hide_offline"] == false)
        {
            echo "<b>"._OFFLINE.":</b></br>";
            // Show offline users

            for($inx = 0; $inx < count($arOfflineUsers); $inx++)
            {
                echo "<a href=\"message.php?to_user=".$arOfflineUsers[$inx]."\">";
                echo "<img src=\"images/user_offline.gif\" width=17 height=14 title=\"\" border=0 align=\"bottom\">".$arOfflineUsers[$inx];
                echo "</a><br>\n";
            }
        }

        echo "</td>";
?>
        </tr>
        </table>
    </td>
</tr>
<tr>
    <td bgcolor="<?=$cfg["table_header_bg"]?>" colspan="2">
<?php
    displayDriveSpaceBar($drivespace);
?>
    </td>
</tr>
<tr>
    <td bgcolor="<?=$cfg["table_data_bg"]?>" colspan="2">
    <div align="center">
    <font face="Arial" size="2">
    <a href="readrss.php">
    <img src="images/download_owner.gif" width="16" height="16" border="0" title="RSS Torrents" align="absmiddle">RSS Torrents</a>
     |
    <a href="drivespace.php">
    <img src="images/hdd.gif" width="16" height="16" border="0" title="<?=$drivespace?>% Used" align="absmiddle"><?=_DRIVESPACE?></a>
     |
    <a href="who.php">
    <img src="images/who.gif" width="16" height="16" title="" border="0" align="absmiddle"><?=_SERVERSTATS?></a>
     |
    <a href="all_services.php">
    <img src="images/all.gif" width="16" height="16" title="" border="0" align="absmiddle"><?=_ALL?></a>
     |
    <a href="dir.php">
    <img src="images/folder.gif" width="16" height="16" title="" border="0" align="absmiddle"><?=_DIRECTORYLIST?></a>
     |
    <a href="dir.php?dir=<?=$cfg["user"]?>"><img src="images/folder.gif" width="16" height="16" title="My Directory" border="0" align="absmiddle">My Directory</a>
    </font>
    </div>
    </td>
</tr>
</table>
<?php
    getDirList($cfg["torrent_file_path"]);
?>
<tr><td bgcolor="<?=$cfg["table_header_bg"]?>" colspan="6">

<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
    <td valign="top">

    <div align="center">

    <table>
    <tr>
        <td><img src="images/properties.png" width="18" height="13" title="<?=_TORRENTDETAILS?>"></td>
        <td class="tiny"><?=_TORRENTDETAILS?>&nbsp;&nbsp;&nbsp;</td>
        <td><img src="images/run_on.gif" width="16" height="16" title="<?=_RUNTORRENT?>"></td>
        <td class="tiny"><?=_RUNTORRENT?>&nbsp;&nbsp;&nbsp;</td>
        <td><img src="images/kill.gif" width="16" height="16" title="<?=_STOPDOWNLOAD?>"></td>
        <td class="tiny"><?=_STOPDOWNLOAD?>&nbsp;&nbsp;&nbsp;</td>
      
<? if(($cfg["AllowQueing"])) { ?>
        <td><img src="images/queued.gif" width="16" height="16" title="<?=_DELQUEUE?>"></td>
        <td class="tiny"><?=_DELQUEUE?>&nbsp;&nbsp;&nbsp;</td>
<? } ?>
        <td><img src="images/seed_on.gif" width="16" height="16" title="<?=_SEEDTORRENT?>"></td>
        <td class="tiny"><?=_SEEDTORRENT?>&nbsp;&nbsp;&nbsp;</td>
        <td><img src="images/delete_on.gif" width="16" height="16" title="<?=_DELETE?>"></td>
        <td class="tiny"><?=_DELETE?></td>
       
<? if(($cfg["enable_torrent_download"])) { ?>
        <td>&nbsp;&nbsp;&nbsp;<img src="images/down.gif" width="9" height="9" title="Download Torrent meta file"></td>
        <td class="tiny">Download Torrent</td>
       
<? } ?>
    </tr>
    </table>


    <table width="100%" cellpadding="5">
    <tr>
        <td width="33%">
            <div class="tiny">

    
<? if((checkQManager() > 0)) { ?>
<img src="images/green.gif" align="absmiddle" title="Queue Manager Running" align="absmiddle"> Queue Manager Running<br>";
         <strong>{strval(getRunningTorrentCount())}</strong> torrent(s) running and <strong>{strval(getNumberOfQueuedTorrents())}</strong> queued.<br>
         Total torrents server will run: <strong><?=$cfg["maxServerThreads"]?></strong><br>
         Total torrents a user may run: <strong><?=$cfg["maxUserThreads"]?></strong><br>
         * Torrents are queued when limits are met.<br>
    
<? } else { ?>
        echo "<img src="images/black.gif" title="Queue Manager Off" align="absmiddle"> Queue Manager Off<br><br>";
    
<? } ?>
            </div>
        </td>
        <td width="33%" valign="bottom">
            <div align="center" class="tiny">
   
<? if((!isset($_SESSION['prefresh']) || ($_SESSION['prefresh'] == true))) { ?>
        *** <?=_PAGEWILLREFRESH?> <span id='span_refresh'><?=$cfg["page_refresh"]?></span> <?=_SECONDS?> ***<br>
        	<a href="<?=$_SERVER['PHP_SELF']?>?pagerefresh=false"><font class="tiny"><?=_TURNOFFREFRESH?></font></a>
    
<? } else { ?>
        <a href="<?=$_SERVER['PHP_SELF']?>"?pagerefresh=true"><font class="tiny"><?=_TURNONREFRESH?></font></a>
    
<? } ?>
    
<? if(($drivespace >= 98)) { ?>
        <script  language="JavaScript">alert("<?=_WARNING?>: <?=$drivespace?>% <?=_DRIVESPACEUSED?>");</script>
<? } ?>
    
<? if((!array_key_exists("total_download",$cfg))) { ?>
 $cfg["total_download"] = 0;
<? } ?>
    
<? if((!array_key_exists("total_upload",$cfg))) { ?>
 $cfg["total_upload"] = 0;
<? } ?>
?>
            </div>
        </td>
        <td valign="top" width="33%" align="right">

            <table>
            <tr>
                <td class="tiny" align="right"><?=_CURRENTDOWNLOAD?>:</td>
                <td class="tiny"><strong>
<? echo number_format($cfg["total_download"], 2); ?>
</strong> kB/s</td>
            </tr>
            <tr>
                <td class="tiny" align="right"><?=_CURRENTUPLOAD?>:</td>
                <td class="tiny"><strong>
<? echo number_format($cfg["total_upload"], 2); ?>
</strong> kB/s</td>
            </tr>
            <tr>
                <td class="tiny" align="right"><?=_FREESPACE?>:</td>
                <td class="tiny"><strong>
<? echo formatFreeSpace($cfg["free_space"]); ?>
</strong></td>
            </tr>
            <tr>
                <td class="tiny" align="right"><?=_SERVERLOAD?>:</td>
                <td class="tiny">
                
            
<? if(($cfg["show_server_load"] && @isFile($cfg["loadavg_path"]))) { ?>
                
<? $loadavg_array = explode(" ", exec("cat ".escapeshellarg($cfg["loadavg_path"]))); ?>
                
<? $loadavg = $loadavg_array[2]; ?>
                <strong><?=$loadavg?></strong>
            
<? } else { ?>
                <strong>n/a</strong>
            
<? } ?>
                </td>
            </tr>
            </table>

        </td>
    </tr>
    </table>





    </div>

    </td>
</tr>
</table>



</td></tr>
</table>

<?php
    echo DisplayTorrentFluxLink();
    // At this point Any User actions should have taken place
    // Check to see if the user has a force_read message from an admin
    if (IsForceReadMsg())
    {
        // Yes, then warn them
?>
        <script  language="JavaScript">
        if (confirm("<?=_ADMINMESSAGE?>"))
        {
            document.location = "readmsg.php";
        }
        </script>
<?php
    }
?>

    </td>
</tr>
</table>
</body>
</html>
