<script type="text/javascript">
$('fileupload').addEvent('change',function(){
AIM.submit($('UploadTorrentForm'), {
'onStart' : function(){
}, 
'onComplete' : function(e){ 
eval(e);
}
})
});
</script>
<form method="POST" id="UploadTorrentForm" action="action.php?id=torrent&action=Upload"  enctype="multipart/form-data" >
<table style="width:100%;border:1">
<tr>
<td style="width:50%">Torrent :</td>
<td><input type="file" name="torrents[]" id="fileupload" size="10" /></td>
</tr>
<tr>
<td><?=_AutoStart?> :</td>
<td><input type="checkbox" name="autostart" class="num" size="5" value="1" checked /></td>
</tr>
</table>

</form>