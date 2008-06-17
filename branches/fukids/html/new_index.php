<?php

include_once("include/functions.php");

$Update_interval = 5;
$maxsaveTime=1000;
// grab the user list
$userlist=GetUserList();
include template('new_index');
?>
