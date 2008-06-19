<script type="text/javascript">

</script>
<form method="POST" id="UploadTorrentForm" action="action.php?id=torrent&action=UrlUpload">
<table style="width:100%;border:1">
<tr>
<td style="width:50%"><?=_Url?> :</td>
<td><input type="text" name="torrent" /></td>
</tr>
<tr>
<td><?=_AutoStart?> :</td>
<td><input type="checkbox" name="autostart" class="num" size="5" value="1" checked /></td>
</tr>
</table>	<fieldset>
<legend accesskey="d"><?=_TRAFFIC_SETTING?></legend>
<table style="width:100%;border:1">
<tr>
<td style="width:50%"><?=_Max_Upload_Rate?> :</td>
<td><input type="text" name="rate" class="num" size="5" value="<?=$Default_max_upload_rate?>" />KB/s</td>
</tr>
<tr>
<td><?=_Max_Download_Rate?> :</td>
<td><input type="text" name="drate" class="num" size="5" value="<?=$Default_max_download_rate?>"  />KB/s</td>
</tr>
<tr>
<td style="width:50%"><?=_Max_Uploads_Connections?> :</td>
<td><input type="text" name="maxuploads" class="num" size="5" value="<?=$Default_max_uploads?>" /></td>
</tr>
</table>
</fieldset>

<fieldset>
<legend accesskey="d"><?=_SEEDING_SETTING?></legend>
<table style="width:100%;border:1">
<tr>
<td><?=_KillWhen?> :</td>
<td><input type="radio" name="runtime" id="runtime_done" 
<? if($Default_sharekill <0) { ?>
 checked="checked" 
<? } ?>
 /><label for="runtime_done"><?=_Die_When_Done?></label>
<input type="radio" name="runtime" id="runtime_until" 
<? if($Default_sharekill >=0) { ?>
 checked="checked" 
<? } ?>
 /><label for="runtime_until"><?=_Die_Until_Ratio?></label>
<input type="text"  name="sharekill" class="num" size="6" value="<?=$Default_sharekill?>" />%
</td>
</tr>
</table>
</fieldset>
<fieldset>
<legend accesskey="d"><?=_OTHER_SETTING?></legend>
<table style="width:100%;border:1">
<tr>
<td><?=_Port?> :</td>
<td><input type="text" name="minport" class="num" size="5" value="<?=$Default_minport?>" /> -<input type="text" name="maxport" class="num" size="5" value="<?=$Default_maxport?>" /></td>
</tr>
<tr>
<td><?=_Rerequest_Interval?> :</td>
<td><input type="text" name="rerequest" class="num" size="5" value="<?=$Default_rerequest_interval?>" />s</td>
</tr>
</table>
</fieldset><input type="submit" value="<?=_START?>">

</form>