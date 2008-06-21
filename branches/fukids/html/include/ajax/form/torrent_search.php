<?php

/*************************************************************
*  TorrentFlux PHP Torrent Manager
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

include_once(ENGINE_ROOT."searchEngines/SearchEngineBase.php");

    // Go get the if this is a search request. go get the data and produce output.

    $hideSeedless = getRequestVar('hideSeedless');
    if(!empty($hideSeedless)){
       $_SESSION['hideSeedless'] = $hideSeedless;
    }

    if (!isset($_SESSION['hideSeedless'])){
        $_SESSION['hideSeedless'] = 'no';
    }

    $hideSeedless = $_SESSION['hideSeedless'];

    $pg = getRequestVar('pg');

    $searchEngine = getRequestVar('searchEngine');
    if (empty($searchEngine)) $searchEngine = $cfg["searchEngine"];

    $searchterm = getRequestVar('searchterm');
    if(empty($searchterm))
        $searchterm = getRequestVar('query');

    $searchterm = str_replace(" ", "+",$searchterm);

    // Check to see if there was a searchterm.
    // if not set the get latest flag.
    if (strlen($searchterm) == 0) {
        if (!array_key_exists("LATEST",$_REQUEST)){
            $_REQUEST["LATEST"] = "1";
        }
    }
	
    if(!is_file(ENGINE_ROOT.'searchEngines/'.$searchEngine.'Engine.php')){
		echo 'Search Engine not installed';
	}else{ 
		include('searchEngines/'.$searchEngine.'Engine.php');
        $sEngine = new SearchEngine(serialize($cfg));
        if (!$sEngine->initialized){
		    echo($sEngine->msg);
		}else{
            $mainStart = true;
            $catLinks = '';
            $tmpCatLinks = '';
            $tmpLen = 0;
			// get the cat list
            foreach ($sEngine->getMainCategories() as $mainId => $mainName){
                if (strlen($tmpCatLinks) >= 500 && $mainStart == false){
                    $catLinks .= $tmpCatLinks . "<br>";
                    $tmpCatLinks = '';
                    $mainStart = true;
                }
                if ($mainStart == false) $tmpCatLinks .= " | ";

                $tmpCatLinks .=  "<a href=\"ajax.php?action=form&usejs=1&id=Torrent_Search&searchEngine=".$searchEngine."&mainGenre=".$mainId."\">".$mainName."</a>";
                $mainStart = false;
            }
            $mainGenre = getRequestVar('mainGenre');
            if (!empty($mainGenre) && !array_key_exists("subGenre",$_REQUEST)){
			// sublist
                $subCats = $sEngine->getSubCategories($mainGenre);
                if (count($subCats) > 0){
                    echo "<form method=get id=\"subLatest\" name=\"subLatest\" action=torrentSearch.php?>";
                    echo "<input type=hidden name=\"searchEngine\" value=\"".$searchEngine."\">";

                    $mainGenreName = $sEngine->GetMainCatName($mainGenre);

                    echo "Category: <b>".$mainGenreName."</a></b> -> ";
                    echo "<select name=subGenre>";

                    foreach ($subCats as $subId => $subName) {
                        echo "<option value=".$subId.">".$subName."</option>\n";
                    }
                    echo "</select> ";
                    echo "<input type=submit value='Show Latest'>";
                    echo "</form>\n";
                }else{
                    echo "</td></tr></table></div>";
                    // Set the Sub to equal the main for groups that don't have subs.
                    $_REQUEST["subGenre"] = $mainGenre;
                    echo $sEngine->getLatest();
                }
            }
        }
    }
	include template('torrent_search');
?>
