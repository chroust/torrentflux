<form name="theForm" method="post" action="action.php?id=user&action=Send_PM">
<table border="0" cellpadding="3" cellspacing="2" width="100%">
<tr>
    <td><?=_TO?>:</td>
    <td><input type="Text" name="to_user" value="<?=$to_user?>" size="20" readonly="true" /></td>
</tr>
<tr>
    <td><?=_FROM?>:</td>
    <td><input type="Text" name="from_user" value="<?=$cfg['user']?>" size="20" readonly="true" /></td>
</tr>
<tr>
    <td valign="top"><?=_YOURMESSAGE?>:</td>
<td><textarea cols="30" rows="10" name="message" id="message"><?=$message?></textarea><br>
<input type="Checkbox" name="to_all" id="to_all" value="1" /><label for="to_all"><?=_SENDTOALLUSERS?></label>
<? if((IsAdmin())) { ?>
<input type="Checkbox" name="force_read" id="force_read" value="1" /><label for="force_read"><?=_FORCEUSERSTOREAD?></label>
<? } ?>
</td>
</tr>
<tr>
<td align="center" colspan="2">
        <input type="Submit" name="Submit" value="<?=_SEND?>" />
    </td>
</tr>
</table>
</form>
<script type="text/javascript">$('message').focus();</script>