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

AdminCheck();

//****************************************************************************
function editLink($lid,$newLink,$newSite)
{
    global $cfg;

    if(!empty($newLink))
    {
        if(strpos($newLink, "http://" ) !== 0 && strpos($newLink, "https://" ) !== 0 && strpos($newLink, "ftp://" ) !== 0)
        {
            $newLink = "http://".$newLink;
        }
        empty($newSite) && $newSite = $newLink;

        $oldLink = getLink($lid);
        $oldSite = getSite($lid);
        alterLink($lid, $newLink, $newSite);
        AuditAction($cfg["constants"]["admin"], "Change Link: ".$oldSite." [".$oldLink."] -> ".$newSite." [".$newLink."]");
    }
    header("location: admin.php?op=editLinks");
}

//****************************************************************************
// addLink -- adding a link
//****************************************************************************
function addLink($newLink,$newSite)
{
    if(!empty($newLink))
    {
        if(strpos($newLink, "http://" ) !== 0 && strpos($newLink, "https://" ) !== 0 && strpos($newLink, "ftp://" ) !== 0)
        {
            $newLink = "http://".$newLink;
        }
        empty($newSite) && $newSite = $newLink;
        global $cfg;
        addNewLink($newLink, $newSite);
        AuditAction($cfg["constants"]["admin"], "New "._LINKS_MENU.": ".$newSite." [".$newLink."]");
    }
    header("location: admin.php?op=editLinks");
}

//****************************************************************************
// moveLink -- moving a link up or down in the list of links
//****************************************************************************
function moveLink($lid, $direction)
{
    global $db, $cfg;
    if  (!isset($lid) && !isset($direction)&& $direction !== "up" && $direction !== "down" )
    {
        header("location: admin.php?op=editLinks");
    }
    $idx = getLinkSortOrder($lid);
    $position = array("up"=>-1, "down"=>1);
    $new_idx = $idx+$position[$direction];
    $sql = "UPDATE tf_links SET sort_order=".$idx." WHERE sort_order=".$new_idx;
    $db->Execute($sql);
    showError($db, $sql);
    $sql = "UPDATE tf_links SET sort_order=".$new_idx." WHERE lid=".$lid;
    $db->Execute($sql);
    showError($db, $sql);
    header("Location: admin.php?op=editLinks");
}

//****************************************************************************
// addRSS -- adding a RSS link
//****************************************************************************
function addRSS($newRSS)
{
    if(!empty($newRSS)){
        global $cfg;
        addNewRSS($newRSS);
        AuditAction($cfg["constants"]["admin"], "New RSS: ".$newRSS);
    }
    header("location: admin.php?op=editRSS");
}




//****************************************************************************
// deleteLink -- delete a link
//****************************************************************************
function deleteLink($lid)
{
    global $cfg;
    AuditAction($cfg["constants"]["admin"], _DELETE." Link: ".getSite($lid)." [".getLink($lid)."]");
    deleteOldLink($lid);
    header("location: admin.php?op=editLinks");
}

//****************************************************************************
// deleteRSS -- delete a RSS link
//****************************************************************************
function deleteRSS($rid)
{
    global $cfg;
    AuditAction($cfg["constants"]["admin"], _DELETE." RSS: ".getRSS($rid));
    deleteOldRSS($rid);
    header("location: admin.php?op=editRSS");
}


//****************************************************************************
// showIndex -- default view
//****************************************************************************
function showIndex($min = 0)
{
    global $cfg;
    DisplayHead(_ADMINISTRATION);

    // Admin Menu
    


    echo "<br>";

    // Display Activity
    displayActivity($min);

    DisplayFoot();

}


//****************************************************************************
// showUserActivity -- Activity for a user
//****************************************************************************
function showUserActivity($min=0, $user_id="", $srchFile="", $srchAction="")
{
    global $cfg;


    // display Activity for user
    displayActivity($min, $user_id, $srchFile, $srchAction);


}



//****************************************************************************
// backupDatabase -- backup the database
//****************************************************************************
function backupDatabase()
{
    global $cfg;

    $file = $cfg["db_name"]."_".date("Ymd").".tar.gz";
    $back_file = $cfg["torrent_file_path"].$file;
    $sql_file = $cfg["torrent_file_path"].$cfg["db_name"].".sql";

    $sCommand = "";
    switch($cfg["db_type"])
    {
        case "mysql":
            $sCommand = "mysqldump -h ".$cfg["db_host"]." -u ".$cfg["db_user"]." --password=".$cfg["db_pass"]." --all -f ".$cfg["db_name"]." > ".$sql_file;
            break;
        default:
            // no support for backup-on-demand.
            $sCommand = "";
            break;
    }

    if($sCommand != "")
    {
        shell_exec($sCommand);
        shell_exec("tar -czvf ".$back_file." ".$sql_file);

        // Get the file size
        $file_size = filesize($back_file);

        // open the file to read
        $fo = fopen($back_file, 'r');
        $fr = fread($fo, $file_size);
        fclose($fo);

        // Set the headers
        header("Content-type: APPLICATION/OCTET-STREAM");
        header("Content-Length: ".$file_size.";");
        header("Content-Disposition: attachement; filename=".$file);

        // send the tar baby
        echo $fr;

        // Cleanup
        shell_exec("rm ".$sql_file);
        shell_exec("rm ".$back_file);
        AuditAction($cfg["constants"]["admin"], _BACKUP_MENU.": ".$file);
    }
}

//****************************************************************************
// displayActivity -- displays Activity
//****************************************************************************
function displayActivity($min=0, $user="", $srchFile="", $srchAction="")
{
    global $cfg, $db;

    $sqlForSearch = "";

    $userdisplay = $user;

    if($user != "")
    {
        $sqlForSearch .= "user_id='".$user."' AND ";
    }
    else
    {
        $userdisplay = _ALLUSERS;
    }

    if($srchFile != "")
    {
        $sqlForSearch .= "file like '%".$srchFile."%' AND ";
    }

    if($srchAction != "")
    {
        $sqlForSearch .= "action like '%".$srchAction."%' AND ";
    }

    $offset = 50;
    $inx = 0;
    if (!isset($min)) $min=0;
    $max = $min+$offset;
    $output = "";
    $morelink = "";

    $sql = "SELECT user_id, file, action, ip, ip_resolved, user_agent, time FROM tf_log WHERE ".$sqlForSearch."action!=".$db->qstr($cfg["constants"]["hit"])." ORDER BY time desc";

    $result = $db->SelectLimit($sql, $offset, $min);
    while(list($user_id, $file, $action, $ip, $ip_resolved, $user_agent, $time) = $result->FetchRow())
    {
        $user_icon = "images/user_0.gif";
        if (IsOnline($user_id))
        {
            $user_icon = "images/user_1.gif";
        }

        $ip_info = htmlentities($ip_resolved, ENT_QUOTES)."<br>".htmlentities($user_agent, ENT_QUOTES);

        $output .= "<tr>";
        if (IsUser($user_id))
        {
            $output .= "<td><a href=\"message.php?to_user=".$user_id."\"><img src=\"".$user_icon."\" width=17 height=14 title=\""._SENDMESSAGETO." ".$user_id."\" border=0 align=\"bottom\">".$user_id."</a>&nbsp;&nbsp;</td>";
        }
        else
        {
            $output .= "<td><img src=\"".$user_icon."\" width=17 height=14 title=\"n/a\" border=0 align=\"bottom\">".$user_id."&nbsp;&nbsp;</td>";
        }
        $output .= "<td><div class=\"tiny\">".htmlentities($action, ENT_QUOTES)."</div></td>";
        $output .= "<td><div align=center><div class=\"tiny\" align=\"left\">";
        $output .= htmlentities($file, ENT_QUOTES);
        $output .= "</div></td>";
        $output .= "<td><div class=\"tiny\" align=\"left\"><a href=\"javascript:void(0)\" onclick=\"return overlib('".$ip_info."<br>', STICKY, CSSCLASS);\" onmouseover=\"return overlib('".$ip_info."<br>', CSSCLASS);\" onmouseout=\"return nd();\"><img src=\"images/properties.png\" width=\"18\" height=\"13\" border=\"0\"><font class=tiny>".htmlentities($ip, ENT_QUOTES)."</font></a></div></td>";
        $output .= "<td><div class=\"tiny\" align=\"center\">".date(_DATETIMEFORMAT, $time)."</div></td>";
        $output .= "</tr>";

        $inx++;
    }

    if($inx == 0)
    {
        $output = "<tr><td colspan=6><center><strong>-- "._NORECORDSFOUND." --</strong></center></td></tr>";
    }

    $prev = ($min-$offset);
    if ($prev>=0)
    {
        $prevlink = "<a href=\"admin.php?op=showUserActivity&min=".$prev."&user_id=".$user."&srchFile=".$srchFile."&srchAction=".$srchAction."\">";
        $prevlink .= "<font class=\"TinyWhite\">&lt;&lt;".$min." "._SHOWPREVIOUS."]</font></a> &nbsp;";
    }
    if ($inx>=$offset)
    {
        $morelink = "<a href=\"admin.php?op=showUserActivity&min=".$max."&user_id=".$user."&srchFile=".$srchFile."&srchAction=".$srchAction."\">";
        $morelink .= "<font class=\"TinyWhite\">["._SHOWMORE."&gt;&gt;</font></a>";
    }
?>
 
    <div align="center">
    <table>
    <form action="admin.php?op=showUserActivity" name="searchForm" method="post">
    <tr>
        <td>
        <strong><?php echo _ACTIVITYSEARCH ?></strong>&nbsp;&nbsp;&nbsp;
        <?php echo _FILE ?>:
        <input type="Text" name="srchFile" value="<?php echo $srchFile ?>" width="30"> &nbsp;&nbsp;
        <?php echo _ACTION ?>:
        <select name="srchAction">
        <option value="">-- <?php echo _ALL ?> --</option>
<?php
        $selected = "";
        if(is_array($cfg["constants"] ))
        {
            foreach ($cfg["constants"] as $action)
            {
                $selected = "";
                if($action != $cfg["constants"]["hit"])
                {
                    if($srchAction == $action)
                    {
                        $selected = "selected";
                    }
                    echo "<option value=\"".htmlentities($action, ENT_QUOTES)."\" ".$selected.">".htmlentities($action, ENT_QUOTES)."</option>";
                }
            }
        }
?>
        </select>&nbsp;&nbsp;
        <?php echo _USER ?>:
        <select name="user_id">
        <option value="">-- <?php echo _ALL ?> --</option>
<?php
        $users = GetUsers();
        $selected = "";
        for($inx = 0; $inx < sizeof($users); $inx++)
        {
            $selected = "";
            if($user == $users[$inx])
            {
                $selected = "selected";
            }
            echo "<option value=\"".htmlentities($users[$inx], ENT_QUOTES)."\" ".$selected.">".htmlentities($users[$inx], ENT_QUOTES)."</option>";
        }
?>
        </select>
        <input type="Submit" value="<?php echo _SEARCH ?>">

        </td>
    </tr>
    </form>
    </table>
    </div>


<?php
    echo "<table width=\"760\" border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
    echo "<tr><td colspan=6 bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
    echo "<table width=\"100%\" cellpadding=0 cellspacing=0 border=0><tr><td>";
    echo "<img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\">"._ACTIVITYLOG." ".$cfg["days_to_keep"]." "._DAYS." (".$userdisplay.")</font>";
    if(!empty($prevlink) && !empty($morelink))
        echo "</td><td align=\"right\">".$prevlink.$morelink."</td></tr></table>";
    elseif(!empty($prevlink))
        echo "</td><td align=\"right\">".$prevlink."</td></tr></table>";
    elseif(!empty($prevlink))
        echo "</td><td align=\"right\">".$morelink."</td></tr></table>";
    else
        echo "</td><td align=\"right\"></td></tr></table>";
    echo "</td></tr>";
    echo "<tr>";
    echo "<td bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._USER."</div></td>";
    echo "<td bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._ACTION."</div></td>";
    echo "<td bgcolor=\"".$cfg["table_header_bg"]."\"><div align=center class=\"title\">"._FILE."</div></td>";
    echo "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"13%\"><div align=center class=\"title\">"._IP."</div></td>";
    echo "<td bgcolor=\"".$cfg["table_header_bg"]."\" width=\"15%\"><div align=center class=\"title\">"._TIMESTAMP."</div></td>";
    echo "</tr>";

    echo $output;

    if(!empty($prevlink) || !empty($morelink))
    {
        echo "<tr><td colspan=6 bgcolor=\"".$cfg["table_header_bg"]."\">";
        echo "<table width=\"100%\" cellpadding=0 cellspacing=0 border=0><tr><td align=\"left\">";
        if(!empty($prevlink)) echo $prevlink;
        echo "</td><td align=\"right\">";
        if(!empty($morelink)) echo $morelink;
        echo "</td></tr></table>";
        echo "</td></tr>";
    }

    echo "</table>";

}


//****************************************************************************
// editLinks -- Edit Links
//****************************************************************************
function editLinks()
{
    global $cfg;
    DisplayHead(_ADMINEDITLINKS);

    // Admin Menu
    
    echo "<div align=\"center\">";
    echo "<table border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
    echo "<tr><td colspan=\"2\" bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
    echo "<img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\">"._ADMINEDITLINKS."</font>";
    echo "</td></tr><tr><td colspan=2 align=\"center\">";
?>
    <form action="admin.php?op=addLink" method="post">
    <?php echo _FULLURLLINK ?>:
    <input type="Text" size="30" maxlength="255" name="newLink">
    Site Name:
    <input type="Text" size="30" maxlength="255" name="newSite">
    <input type="Submit" value="<?php echo _UPDATE ?>"><br>
    </form>
<?php
    echo "</td></tr>";
    $arLinks = GetLinks();

    if (is_array($arLinks))
    {
        $arLid = Array_Keys($arLinks);
        $inx = 0;
        $link_count = count($arLinks);

        foreach($arLinks as $link)
        {
            $lid = $arLid[$inx++];
            $ed = getRequestVar("edit");
            if (!empty($ed) && $ed == $link['lid'])
            {
                echo "<tr><td colspan=\"2\">";
    ?>
      <form action="admin.php?op=editLink" method="post">
      <?php echo _FULLURLLINK ?>:
      <input type="Text" size="30" maxlength="255" name="editLink" value="<?php echo $link['url'] ?>">
      Site Name:
      <input type="Text" size="30" maxlength="255" name="editSite" value="<?php echo $link['sitename'] ?>">
      <input type="hidden" name="lid" value="<?php echo $lid ?>">
      <input type="Submit" value="<?php echo _UPDATE ?>"><br>
      </form>
    <?php
            }
            else
            {
                echo "<tr><td>";
                echo "<a href=\"admin.php?op=deleteLink&lid=".$lid."\"><img src=\"images/delete_on.gif\" width=16 height=16 border=0 title=\""._DELETE." ".$lid."\" align=\"absmiddle\"></a>&nbsp;";
                echo "<a href=\"admin.php?op=editLinks&edit=".$lid."\"><img src=\"images/properties.png\" width=18 height=13 border=0 title=\""._EDIT." ".$lid."\" align=\"absmiddle\"></a>&nbsp;";
                echo "<a href=\"".$link['url']."\" target=\"_blank\">".$link['sitename']."</a></td>\n";
                echo "<td align=center width='36'>";

                if ($inx > 1 ){
                    // Only put an 'up' arrow if this isn't the first entry:
                    echo "<a href='admin.php?op=moveLink&amp;direction=up&amp;lid=".$lid."'>";
                    echo "<img src='images/uparrow.png' width='16' height='16' ";
                    echo "border='0' title='Move link up' align='absmiddle' alt='Up'></a>";
                }

                if ($inx != count($arLinks)) {
                    // Only put a 'down' arrow if this isn't the last item:
                    echo "<a href='admin.php?op=moveLink&amp;direction=down&amp;lid=".$lid."'>";
                    echo "<img src='images/downarrow.png' width='16' height='16' ";
                    echo "border='0' title='Move link down' align='absmiddle' alt='Down'></a>";
                }
                echo "</td></tr>";
            }
        }
    }
    echo "</table></div><br><br><br>";

    DisplayFoot();

}


//****************************************************************************
// editRSS -- Edit RSS Feeds
//****************************************************************************
function editRSS()
{
    global $cfg;
    DisplayHead("Administration - RSS");

    // Admin Menu
    
    echo "<div align=\"center\">";
    echo "<table border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
    echo "<tr><td bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
    echo "<img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\">RSS Feeds</font>";
    echo "</td></tr><tr><td align=\"center\">";
?>
    <form action="admin.php?op=addRSS" method="post">
    <?php echo _FULLURLLINK ?>:
    <input type="Text" size="50" maxlength="255" name="newRSS">
    <input type="Submit" value="<?php echo _UPDATE ?>"><br>
    </form>
<?php
    echo "</td></tr>";
    $arLinks = GetRSSLinks();
    $arRid = Array_Keys($arLinks);
    $inx = 0;
    if(is_array($arLinks))
    {
        foreach($arLinks as $link)
        {
            $rid = $arRid[$inx++];
            echo "<tr><td><a href=\"admin.php?op=deleteRSS&rid=".$rid."\"><img src=\"images/delete_on.gif\" width=16 height=16 border=0 title=\""._DELETE." ".$rid."\" align=\"absmiddle\"></a>&nbsp;";
            echo "<a href=\"".$link."\" target=\"_blank\">".$link."</a></td></tr>\n";
        }
    }
    echo "</table></div><br><br><br>";

    DisplayFoot();

}

function ClearCronRobotLog(){
	global $cfg;
	unlink($cfg['cronwork_log']);
	touch($cfg['cronwork_log']);
}

//****************************************************************************
// updateConfigSettings -- updating App Settings
//****************************************************************************
function updateConfigSettings(){
    global $cfg;
    $tmpPath = getRequestVar("path");
    if (!empty($tmpPath) && substr( $tmpPath, -1 )  != "/"){
        // path requires a / on the end
        $_POST["path"] = $_POST["path"] . "/";
    }
	$AllowQueing = getRequestVar("AllowQueing");
	if(!$AllowQueing){
		forceStartAllQueue();
	}
	saveSettings($_POST);
    AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Settings");
	showmessage('',1,1);
}


//****************************************************************************
// searchSettings -- Config the Search Engine Settings
//****************************************************************************
function searchSettings()
{
    global $cfg;
    include_once("AliasFile.php");
    include_once("RunningTorrent.php");
    include_once("searchEngines/SearchEngineBase.php");

    DisplayHead("Administration - Search Settings");

    // Admin Menu
    

    // Main Settings Section
    echo "<div align=\"center\">";
    echo "<table width=\"100%\" border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\">";
    echo "<tr><td bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
    echo "<img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\">Search Settings</font>";
    echo "</td></tr><tr><td align=\"center\">";

?>
    <div align="center">

        <table cellpadding="5" cellspacing="0" border="0" width="100%">
        <form name="theForm" action="admin.php?op=searchSettings" method="post">
        <tr>
            <td align="right" width="350" valign="top"><strong>Select Search Engine</strong><br>
            </td>
            <td valign="top">
<?php
                $searchEngine = getRequestVar('searchEngine');
                if (empty($searchEngine)) $searchEngine = $cfg["searchEngine"];
                echo buildSearchEngineDDL($searchEngine,true)
?>
            </td>
        </tr>
        </form>
        </table>

        <table cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr><td>
<?php
    if (is_file('searchEngines/'.$searchEngine.'Engine.php'))
    {
        include_once('searchEngines/'.$searchEngine.'Engine.php');
        $sEngine = new SearchEngine(serialize($cfg));
        if ($sEngine->initialized)
        {
            echo "<table width=\"100%\" border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\"><tr>";
            echo "<td bgcolor=\"".$cfg["table_header_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\"><img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\">".$sEngine->mainTitle." Search Settings</font></td>";
            echo "</tr></table></td>";
            echo "<form name=\"theSearchEngineSettings\" action=\"admin.php?op=updateSearchSettings\" method=\"post\">\n";
            echo "<input type=\"hidden\" name=\"searchEngine\" value=\"".$searchEngine."\">";
?>
            </td>
        </tr>
        <tr>
            <td>

        <table cellpadding="5" cellspacing="0" border="0" width="100%">
        <tr>
            <td align="left" width="350" valign="top"><strong>Search Engine URL:</strong></td>
            <td valign="top">
                <?php echo "<a href=\"http://".$sEngine->mainURL."\" target=\"_blank\">".$sEngine->mainTitle."</a>"; ?>
            </td>
        </tr>
        <tr>
            <td align="left" width="350" valign="top"><strong>Search Module Author:</strong></td>
            <td valign="top">
                <?php echo $sEngine->author; ?>
            </td>
        </tr>
        <tr>
            <td align="left" width="350" valign="top"><strong>Version:</strong></td>
            <td valign="top">
                <?php echo $sEngine->version; ?>
            </td>
        </tr>
<?php
        if(strlen($sEngine->updateURL)>0)
        {
?>
        <tr>
            <td align="left" width="350" valign="top"><strong>Update Location:</strong></td>
            <td valign="top">
                <?php echo "<a href=\"".$sEngine->updateURL."\" target=\"_blank\">Check for Update</a>"; ?>
            </td>
        </tr>
<?php
        }
            if (! $sEngine->catFilterName == '')
            {
?>
        <tr>
            <td align="left" width="350" valign="top"><strong>Search Filter:</strong><br>
            Select the items that you DO NOT want to show in the torrent search:
            </td>
            <td valign="top">
<?php
                echo "<select multiple name=\"".$sEngine->catFilterName."[]\" size=\"8\" STYLE=\"width: 125px\">";
                echo "<option value=\"-1\">[NO FILTER]</option>";
                foreach ($sEngine->getMainCategories(false) as $mainId => $mainName)
                {
                    echo "<option value=\"".$mainId."\" ";
                    if (@in_array($mainId, $sEngine->catFilter))
                    {
                        echo " selected";
                    }
                    echo ">".$mainName."</option>";
                }
                echo "</select>";
                echo "            </td>\n";
                echo "        </tr>\n";
            }
        }
    }

    echo "        </table>\n";
    echo "         </td></tr></table>";
    echo "        <br>\n";
    echo "        <input type=\"Submit\" value=\"Update Settings\">";
    echo "        </form>\n";
    echo "    </div>\n";
    echo "    <br>\n";
    echo "</td></tr>";
    echo "</table></div>";

    DisplayFoot();
}

//****************************************************************************
// updateSearchSettings -- updating Search Engine Settings
//****************************************************************************
function updateSearchSettings()
{
    global $cfg;

    foreach ($_POST as $key => $value)
    {
        if ($key != "searchEngine")
        {
            $settings[$key] = $value;
        }
    }

    saveSettings($settings);
    AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Search Settings");

    $searchEngine = getRequestVar('searchEngine');
    if (empty($searchEngine)) $searchEngine = $cfg["searchEngine"];
    header("location: admin.php?op=searchSettings&searchEngine=".$searchEngine);
}

//****************************************************************************
//****************************************************************************
//****************************************************************************
//****************************************************************************
// TRAFFIC CONTROLER
$op = getRequestVar('op');
switch ($op)
{

    default:
		exit();
        $min = getRequestVar('min');
        if(empty($min)) $min=0;
        showIndex($min);
    break;

    case "showUserActivity":
        $min = getRequestVar('min');
        if(empty($min)) $min=0;
        $user_id = getRequestVar('user_id');
        $srchFile = getRequestVar('srchFile');
        $srchAction = getRequestVar('srchAction');
        showUserActivity($min, $user_id, $srchFile, $srchAction);
    break;

    case "backupDatabase":
        backupDatabase();
    break;

    case "editRSS":
        editRSS();
    break;

    case "addRSS":
        $newRSS = getRequestVar('newRSS');
        addRSS($newRSS);
    break;

    case "deleteRSS":
        $rid = getRequestVar('rid');
        deleteRSS($rid);
    break;

    case "editLink":
        $lid = getRequestVar('lid');
        $editLink = getRequestVar('editLink');
        $editSite = getRequestVar('editSite');
        editLink($lid, $editLink, $editSite);
    break;

    case "editLinks":
        editLinks();
    break;

    case "addLink":
        $newLink = getRequestVar('newLink');
        $newSite = getRequestVar('newSite');
        addLink($newLink,$newSite);
    break;

    case "moveLink":
        $lid = getRequestVar('lid');
        $direction = getRequestVar('direction');
        moveLink($lid, $direction);
    break;

    case "deleteLink":
        $lid = getRequestVar('lid');
        deleteLink($lid);
    break;

    case "addUser":
        $newUser = getRequestVar('newUser');
        $pass1 = getRequestVar('pass1');
        $pass2 = getRequestVar('pass2');
        $userType = getRequestVar('userType');
			if($pass1!==$pass2){
				showmessage(_PASSWORDNOTMATCH,1);
			}
        $newUser = strtolower($newUser);
		$newuid=addNewUser($newUser, $pass1, $userType);
		AuditAction($cfg["constants"]["admin"], _NEWUSER.": ".$newUser);
		echo("adduser2ms(6,'$newUser');");
		showmessage(_user_created,1,1);
    break;

    case "deleteUser":
        $uid = getRequestVar('uid');
        DeleteThisUser($uid);
    break;

    case "editUser":
        $user_id = getRequestVar('user_id');
        editUser($user_id);
    break;

    case "updateUser":
        $user_id = getRequestVar('user_id');
        $org_user_id = getRequestVar('org_user_id');
        $pass1 = getRequestVar('pass1');
        $userType = getRequestVar('userType');
        $hideOffline = getRequestVar('hideOffline');
        $allow_view_other_torrent = getRequestVar('allow_view_other_torrent');
        $torrentlimit_period = getRequestVar('torrentlimit_period');
        $torrentlimit_number = getRequestVar('torrentlimit_number');
        $transferlimit_period = getRequestVar('transferlimit_period');
        $transferlimit_number = getRequestVar('transferlimit_number');
        updateThisUser($user_id, $org_user_id, $pass1, $userType, $hideOffline,$allow_view_other_torrent,$torrentlimit_period,$torrentlimit_number,$transferlimit_period,$transferlimit_number);
		echo "window.location.reload(true);";
    break;

    case "configSettings":
        configSettings();
    break;

    case "updateConfigSettings":
        if (! array_key_exists("debugTorrents", $_REQUEST)) {
            $_REQUEST["debugTorrents"] = false;
        }
        updateConfigSettings();
    break;


    case "updateSearchSettings":
        updateSearchSettings();
    break;
    case "ClearCronRobotLog":
        ClearCronRobotLog();
	break;
	case "checkpath":
		echo validatePath( getRequestVar('path'));
	break;
	case "checkfile":
		echo validateFile( getRequestVar('file'));
	break;
}
//****************************************************************************
//****************************************************************************
//****************************************************************************
//****************************************************************************

?>
