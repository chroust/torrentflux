<table>
<tr><td>Torrent File:</td><td> <?=$torrentfile?> </td></tr>
<tr><td>Directory Name:</td><td> <?=$info['info']['name.utf-8'], ENT_QUOTES?></td></tr>
<? if(array_key_exists('comment',$info)) { ?>
<tr><td>Comment:</td><td>
<? echo $info['comment.utf-8']; ?>
</td></tr>
<? } ?>
<tr><td>Created:</td><td>
<? echo nl2br($info['creation date_text']); ?>
</td></tr>
<tr><td>Chunk size:</td><td>
<? echo $info['info']['piece length']; ?>
 (
<? echo formatBytesToKBMGGB($info['info']['piece length']); ?>
)</td></tr>
</table>