<div id="pmlist">
<div id="thead">
<div><?=_FROM?></div>
<div class="msg"><?=_MESSAGE?></div>
<div><?=_DATE?></div>
<div class="admintd"><?=_ADMIN?></div>
</div>
<div id="tbody">
<? if(isset($pmlist) && is_array($pmlist)) { foreach($pmlist as $index => $pm) { ?>
<div class="tr" id="pmlist_<?=$pm['mid']?>">
<div><img src="<?=$mail_image?>"><?=$pm['from_user']?></div>
<div class="msg"><?=$pm['display_message']?></div>
<div><?=$pm['time_text']?></div>
<div class="admintd">
<a href="#" class="replypm" title="<?=$pm['uid']?>"><img src="images/reply.gif" border="0"></a>
<a href="#" class="delpm" title="<?=$pm['mid']?>"><img src="images/delete_on.gif" border="0"></a>
</div>
</div>
<? } } ?>
</div>
</div>
<? if($pmcount==0) { ?>
<div><strong>-- <?=_NORECORDSFOUND?> --</strong></div>
<? } ?>
<script type="text/javascript">
var pmrow_selecting=0;
function delpm(mid){
new Request({method: 'get', url: 'action.php?id=user&action=Del_PM&delete='+mid,
onSuccess:function(){
$('pmlist_'+mid).destroy();
}
}).send();
}
function reply(uid){
OpenWindow('_SEND_PM','<?=_SEND_PM?>','rightclick','&uid='+uid);
}

$$('.delpm').each(function(a){
a.addEvent('click',function(){
arrayaa=split('|',a.title);
delpm(arrayaa[0],arrayaa[1]);
});
});
$$('.replypm').each(function(a){
a.addEvent('click',function(){
reply(a.title);
});
});
/////////////////////////////////////////////////////////////////////////////////
$$('.tr').each(function(tr){
tr.addEvents({
'mouseover':function(){
tr.addClass('pmrow_hover');
},
'mouseout':function(){
tr.removeClass('pmrow_hover');
},
'click':function(){
if(pmrow_selecting==tr.id){
return false;
}
if(pmrow_selecting){
$(pmrow_selecting).setStyle('height','2em').setStyles({'background':'#fff','overflow':'hidden'});
}
pmrow_selecting=tr.id;
this.tween('height', '200px').setStyles({'background':'#FFD8AF','overflow':'auto'});
}
});
});

</script>