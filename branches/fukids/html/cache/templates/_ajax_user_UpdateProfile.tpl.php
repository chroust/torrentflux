<form name="this" method="post" action="action.php?id=user&action=_EDITUSER"  onsubmit="return validateProfile()">
<table border="0" cellpadding="3" cellspacing="2" width="100%">
<tr>
<td align="right"><?=_USER?>:</td>
<td>
<input type="Text" id="username" value="<?=$cfg["user"]?>" size="15" />
</td>
</tr>
<tr>
<td align="right"><?=_NEWPASSWORD?>:</td>
<td>
<input name="pass1" id="pass1" type="Password"size="15" />
</td>
</tr>
<tr>
<td align="right"><?=_CONFIRMPASSWORD?>:</td>
<td>
<input name="pass2" id="pass2" type="Password" size="15" />
</td>
</tr>
<tr>
<td align="right"><?=_THEME?>:</td>
<td>
<select name="theme" id="theme">
<? if(isset($arThemes) && is_array($arThemes)) { foreach($arThemes as $inx => $theme) { ?>
<option value="<?=$theme?>" 
<? if($cfg["theme"] == $theme) { ?>
$selected = "selected"
<? } ?>
><?=$theme?></option>
<? } } ?>
</select>
</td>
</tr>
<tr>
<td align="right"><?=_LANGUAGE?>:</td>
<td>
<select name="language" id="language">
<? if(isset($arLanguage) && is_array($arLanguage)) { foreach($arLanguage as $inx => $Language) { ?>
<option value="<?=$Language?>" 
<? if($cfg["language_file"] == $Language) { ?>
$selected = "selected"
<? } ?>
>
<? echo GetLanguageFromFile($Language); ?>
</option>
<? } } ?>
</select>
</td>
</tr>
<tr>
<td>
</td>
<td>
<input name="hideOffline" id="hideOffline" type="Checkbox" value="1" <?=$hideChecked?>> <?=_HIDEOFFLINEUSERS?><br>
</td>
</tr>
<tr>
<td align="center" colspan="2">
<input type="Submit" value="<?=_UPDATE?>">
</td>
</tr>
</form>
</table>
    <script language="JavaScript">
    function validateProfile()
    {
        var msg = ""
        if ($('pass1').value != "" || $('pass2').value != "")
        {
            if ($('pass1').value.length <= 5 || $('pass2').value.length <= 5)
            {
                msg = msg + "* <?=_PASSWORDLENGTH?>\n";
                $('pass1').focus();
            }
            if ($('pass1').value != $('pass2').value)
            {
                msg = msg + "* <?=_PASSWORDNOTMATCH?>\n";
                $('pass1').value = "";
                $('pass2').value = "";
                $('pass1').focus();
            }
        }

        if (msg != "")
        {
            alert("<?=_PLEASECHECKFOLLOWING?>:\n\n" + msg);
            return false;
        }
        else
        {
            return true;
        }
    }
    </script>