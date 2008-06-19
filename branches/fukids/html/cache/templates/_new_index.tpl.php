<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<link rel="icon" href="images/favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
<script type="text/javascript" src="js/mootools-1.2-core.js"></script>
<script type="text/javascript" src="js/mootools-1.2-more.js"></script>
<script type="text/javascript" src="js/ajaxtips.js"></script>
<script type="text/javascript" src="js/sortableTable.js"></script>
<script type="text/javascript" src="js/mootabs1.2.js"></script>
<script type="text/javascript" src="js/webtoolkit.aim.js"></script>
<script type="text/javascript" src="js/ddmenu.js"></script>			
<script type="text/javascript" src="js/panel.js"></script>		
<script type="text/javascript" src="js/multiselect.js"></script>	
<script type="text/javascript" src="js/Fx.ProgressBar.js"></script>	
<script type="text/javascript" src="js/loading.js"></script>			
<!--[if IE]>
<link href="css/ie.css" rel="stylesheet" type="text/css" media="screen" />
<script type="text/javascript" src="js/excanvas-compressed.js"></script>
<![endif]-->
<script type="text/javascript" src="js/mocha.js" charset="utf-8"></script>

<link href="css/mocha.css" rel="stylesheet" type="text/css" />
<link href="css/sortableTable.css" rel="stylesheet" type="text/css" />
<link href="css/mootabs1.2.css" rel="stylesheet" type="text/css" />
<link href="css/menu.css" rel="stylesheet" type="text/css" media="screen" />
<link href="css/multiselect.css" rel="stylesheet" type="text/css" media="screen" />
<link href="css/tree.css" rel="stylesheet" type="text/css" media="screen" />
<link href="css/pmlist.css" rel="stylesheet" type="text/css" />
<!--[if IE]>
<link href="css/ie.css" rel="stylesheet" type="text/css" media="screen" />
<![endif]-->
</head>
<body>
<script  type="text/javascript">
function echo (a){
console.log(a);
}
//function for grabing torrent list
get_data=function(){
Mainloading.show();
 request = new Request.JSON({url:'ajax.php?action=listtorrent&feeds='+selected_feeds+'&status='+selected_status+'&users='+selected_user,onComplete:function(data){
if($type(data['torrents'])!='array'){
data['torrents']=new Array();
}
update_data(data['torrents']);
//update title
document.title='TorrentFlux----  Total Upload :'+data['global']['totalUpSpeed']+'kB/s   Total Download :'+data['global']['totalDownSpeed']+'kB/s';
//update leftmenu 1
$('sdownloading').innerHTML=data['global']['totaldownloading'];
$('sfinished').innerHTML=data['global']['totalfinished'];
$('sactive').innerHTML=data['global']['totalactive'];
$('sinactive').innerHTML=data['global']['totalinactive'];
// update leftmenu 2

if($type(data['global']['usrtotal_str'])=='string'){
tmparray=data['global']['usrtotal_str'].split(',');
tmparray.forEach(function(e,i,a){
b=e.split(':',2);
$('susering_'+b[0]).innerHTML=b[1];
});
}
if($type(data['global']['useronline'])=='string'){
tmparray=data['global']['useronline'].split(',');
tmparray.forEach(function(e,i,a){
b=e.split(':',2);
$('useronline_'+b[0]).src='images/user_'+b[1]+'.gif';
});
}
timer=setTimeout('get_data()', UpdateInterval*1000);
Mainloading.hide();
}}).get();
}
forceUpdate=function(){
clearTimeout(timer);
get_data();
}
update_data =function (Torrents){
var thisarray=new Array();
var needclear=0;
if(uploadcount><?=$maxsaveTime?>){
uploadcount=0;
needclear=1
reloadedcount=1;
}
Torrents.each(function(torrent){
thisarray[torrent.id]=1
if($defined($(torrent.id))){
ChangeTorrent(torrent);
}else{
NewTorrent(torrent);
upSpeed[torrent.id]=new Array();
downSpeed[torrent.id]=new Array();
MaxdownSpeed[torrent.id]=0;
}
if(needclear){
upSpeed[torrent.id]=new Array();
downSpeed[torrent.id]=new Array();
}
upSpeed[torrent.id][uploadcount]=parseFloat(torrent.up_speed);
downSpeed[torrent.id][uploadcount]=parseFloat(torrent.down_speed);
MaxdownSpeed[torrent.id]=(downSpeed[torrent.id][uploadcount]>MaxdownSpeed[torrent.id])?downSpeed[torrent.id][uploadcount]:MaxdownSpeed[torrent.id];
});
//check if any displayed torrent need to be destory
$$('#tbody div.rows').each(function(item){
if(thisarray[item.id] !==1){
//destroy it
if(selecting==item.id){
$(selecting).removeClass('torrent_list_clicked');
selecting=$empty();
}
$(item.id).destroy();
downSpeed[item.id]=new Array();
upSpeed[item.id]=new Array();

}
});
uploadcount++;
}
// a function to display torrent if it is not exist yet
NewTorrent=function(torrent){
var tr =  new Element('div', {'class': 'rows','id':torrent.id}).injectInside($('tbody'));
var namediv=new Element('div',{'class':'tl_name'}).injectInside(tr);
new Element('span',{'class':'tl_name_span'}).injectInside(namediv).set('html',torrent.title);
new Element('img',{'class':'statusimg'}).setProperty('src', 'images/icon/status/'+torrent.statusid+'.png').inject(namediv,'top');
var percent=new Element('div',{'class':'tl_percent'}).injectInside(tr);
new Element('img',{'class':'progressbar'}).setProperty('src', 'images/percentImage.png').injectInside(percent);
new Element('span',{'class':'progressbar_text'}).injectInside(percent);
new Element('div',{'class':'tl_filesize'}).set('html',torrent.size).injectInside(tr);
new Element('div',{'class':'tl_status'}).set('html',torrent.status).injectInside(tr);
new Element('div',{'class':'tl_seeds'}).set('html',torrent.seeds).injectInside(tr);
new Element('div',{'class':'tl_peers'}).set('html',torrent.peers).injectInside(tr);
new Element('div',{'class':'tl_downloadspeed'}).set('html',torrent.down_speed+' KB/s').injectInside(tr);
new Element('div',{'class':'tl_uploadspeed'}).set('html',torrent.up_speed+' KB/s').injectInside(tr);
new Element('div',{'class':'tl_estime'}).set('html',torrent.estTime).injectInside(tr);
new Element('div',{'class':'tl_totalupload'}).set('html',torrent.totalUpload).injectInside(tr);
tr.addEvents({
'mouseup': function() {
if(selecting)$(selecting).removeClass('torrent_list_clicked');
tr.addClass('torrent_list_clicked');
selecting=torrent.id;
myTabs1.activate(down_selecting_tab);
}
});
if($defined($(selecting)) && selecting==torrent.id){
tr.addClass('torrent_list_clicked');
}
pagemenu = new DDMenu ('torrentclick', torrent.id, {
onItemSelect: function (act_id, act_el, menu_bindon) {
if(act_id=='Edit_Torrent'){
OpenWindow('Edit_Torrent','<?=_Edit_Torrent?>','icon','torrentid='+menu_bindon.id);
}else{
torrentControl(act_id,menu_bindon.id);
}
}
});
progressbar[torrent.id]=new Fx.ProgressBar('div#tbody div#'+torrent.id+' .tl_percent img',{text:'div#tbody div#'+torrent.id+' .tl_percent span'}).start(torrent.percent,100);
}
ChangeTorrent=function(torrent){
$$('div#tbody div#'+torrent.id+' .tl_name_span').set('html',torrent.title);
$$('div#tbody div#'+torrent.id+' .tl_name img.statusimg').setProperty('src', 'images/icon/status/'+torrent.statusid+'.png');
//$$('div#tbody div#'+torrent.id+' .tl_percent').set('html',torrent.percent);
progressbar[torrent.id].set(torrent.percent);
$$('div#tbody div#'+torrent.id+' .tl_filesize').set('html',torrent.size);
$$('div#tbody div#'+torrent.id+' .tl_status').set('html',torrent.status);
$$('div#tbody div#'+torrent.id+' .tl_seeds').set('html',torrent.seeds);
$$('div#tbody div#'+torrent.id+' .tl_peers').set('html',torrent.peers);
$$('div#tbody div#'+torrent.id+' .tl_downloadspeed').set('html',torrent.down_speed+' KB/s');
$$('div#tbody div#'+torrent.id+' .tl_uploadspeed').set('html',torrent.up_speed+' KB/s');
$$('div#tbody div#'+torrent.id+' .tl_estime').set('html',torrent.estTime);
$$('div#tbody div#'+torrent.id+' .tl_totalupload').set('html',torrent.totalUpload);
}

torrentControl=function(action,id){
Mainloading.show();
new Request.HTML({onComplete:function(data){
forceUpdate();
}
}).post('action.php?id=torrent&action='+action+'&usejs=1&torrentid='+id);
}
OpenWindow=function(id,title,action,extraurl,width,height){
var extraurl=$defined(extraurl)?'&'+extraurl:'';
var wwidth=width?width:500;
var wheight=height?height:400;
new MochaUI.Window({
id: 'window_'+title,
title: title,
evalScripts:1,
resizable:0,
loadMethod: 'xhr',
contentURL: 'ajax.php?action='+action+'&usejs=1&id='+id+extraurl,
width: wwidth,
height: wheight,
onContentLoaded:function(e){
$$('#window_'+title+' form').each(function(thisform){
if(thisform.enctype){
thisform.action+='&usejs=1';
}else{
thisform.addEvent('submit',function(){
thisform.set('send', {url:thisform.action+'&usejs=1',
onSuccess:function(e){eval(e);}}).send();
return false;
});
}
});
$$('#window_ input.num').each(function(thisform){
thisform.addEvent('keypress',function(event){
event = new Event(event);
var validChars = '0123456789';
if ( event.key.length == 1 && !validChars.contains(event.key) )
event.stop();
});
})
}
});
}
LeftMenuRightClick = function(id,act,bind){
// uid:   bind.retrieve('uid')
// id: id
OpenWindow(id,id,'rightclick','&uid='+ bind.retrieve('uid'));
}
window.addEvent('domready', function() {
sorted1		=	new tableSoort('list_torrent')
myTabs1		=	new mootabs('torrent_info', {height: '300px', width: '100%', useAjax: '1', ajaxUrl: 'ajax.php'});
//icon bar control
$$('div.icon_window').each(function(item){
item.addEvent('click', function(){
if(item.id=='Upload_Torrent'){
var width=300;
var height=100;
}else{
var width='';
var height='';
}
OpenWindow(item.id,item.title,'icon','',width,height);
});
item.addEvent('mouseover',function(){
item.setStyle('border','1px solid #ccc');
}).addEvent('mouseout',function(){
item.setStyle('border','1px solid #fff');
});
});
// icon control javascript
$$('div.icon_control').each(function(item){
item.addEvent('click',function(){
if($(selecting)){
torrentControl(item.title,selecting);
}
});
item.addEvent('mouseover',function(){
item.setStyle('border','1px solid #ccc');
}).addEvent('mouseout',function(){
item.setStyle('border','1px solid #fff');
});
});
//search bar javascript
$('searchterm').addEvent('focus',function(){
this.value='';
}).addEvent('blur',function(){
if(this.value=='')this.value='Torrent <?=_SEARCH?>';
});
window.addEvent('keydown', function(e){
e = new Event(e); 
if(e.key=='esc'){
window.MochaUI.closeAll();
}
});
// right click menu
new DDMenu ('ListLeftClick', 'tbody', {
onItemSelect: function(act_id, act_el, menu_bindon){
if(act_id=='Upload_Torrent'){
OpenWindow(act_id,act_id,'icon','',300,100);
}else{
OpenWindow(act_id,act_id,'icon');
}
}
});


//multiselect
$('ms_torrent_multiselect1').addEvent('click',function(){
var output='';
var comma='';
$('torrent_multiselect1').getSelected().each( function(option){
output+=comma+option.value;
comma=',';
})
selected_status=output?output:0;
forceUpdate();
});
$('ms_torrent_multiselect2').addEvent('click',function(){
var output='';
var comma='';
$('torrent_multiselect2').getSelected().each( function(option){
output+=comma+option.value;
comma=',';
})
selected_user=output?output:0;
forceUpdate();
});

//update data
get_data();

new DDMenu ('torrent_multiselect2RightClick', 'ms_torrent_multiselect2', {
onItemSelect: function(act_id, act_el, menu_bindon){
OpenWindow(act_id,act_id,'rightclick');
}
});
<? if(isset($userlist) && is_array($userlist)) { foreach($userlist as $index => $user) { ?>
// Add onmouse ajax tips on every user
Ajaxtips('ms_torrent_multiselect2_<?=$index?>','ajax.php?action=tips&id=user_profile&uid=<?=$user['uid']?>');
// add right click menu on every user
new DDMenu ('torrent_multiselect2_innerRightClick', 'ms_torrent_multiselect2_<?=$index?>', {
onOpen: function(){
<? if($myuid==$user['uid']) { ?>
this.hideItems(['_SEND_PM','_ADMIN_DELETEUSER','_ADMIN_EDITUSER']);
this.hideItems(['_VIEW_PM'],false);
<? } else { ?>
this.hideItems(['_VIEW_PM','_ADMIN_EDITUSER']);
this.hideItems(['_SEND_PM','_ADMIN_DELETEUSER'],false);
<? } ?>
this.setTitle('<?=$user['user_id']?>');
},
onItemSelect: function(act_id, act_el, menu_bindon){
OpenWindow(act_id,act_id,'rightclick','uid=<?=$user['uid']?>');
}
});
<? } } ?>
});
var progressbar=new Array();
var selecting;
var reloadedcount=0;
var uploadcount=0;
var down_selecting_tab;
var selected_feeds ='0';
var selected_status='0';
var selected_user ='0';
var upSpeed=new Array();
var downSpeed=new Array();
var MaxdownSpeed=new Array();
var UpdateInterval=<?=$Update_interval?>;
var myuid=<?=$cfg['uid']?>;
var myusername='<?=$cfg['user']?>';
</script>
<div id="Mother">
<div id="top_icon_Bar">
<div id="searchdiv">
<form name="form_search" action="torrentSearch.php" method="get">
<form name="form_search" action="torrentSearch.php" method="get">
<input type="text" name="searchterm" id='searchterm' value="Torrent <?=_SEARCH?>" size="20" maxlength="50">
<? echo buildSearchEngineDDL($cfg["searchEngine"]); ?>
<input type="Submit" value="<?=_SEARCH?>">
</form>
</div>
<div class="icon icon_window" id="Upload_Torrent" title="<?=_Upload_Torrent?>"><img src="images/icon/Upload_Torrent.PNG" alt="upload_torrent" /></div>
<div class="icon icon_window" id="Url_Torrent" 	title="<?=_Url_Torrent?>"><img src="images/icon/Url_Torrent.PNG" alt="uplaod torrent from url" /></div>
<div class="icon icon_window" id="Creat_Torrent" title="<?=_Creat_Torrent?>"><img src="images/icon/Creat_Torrent.PNG" alt="creat torrent" /></div>
<div class="icon-seperator"></div>
<div class="icon icon_control" id="Start" title="Start"><img src="images/icon/Start.PNG" alt="start" /></div>
<div class="icon icon_control" id="Stop" title="Kill"><img src="images/icon/Stop.PNG" alt="stop"/></div>
<div class="icon icon_control" id="Del" title="Del"><img src="images/icon/Del.PNG" alt="del"/></div>
<div class="icon-seperator"></div>
<div class="icon icon_window" id="New_Feed" <?=_New_Feed?>><img src="images/icon/New_Feed.PNG" alt="new Feed"/></div>

</div>
<div id="down">
<div id="down_right">
<div id="torrent_list_div">
<div id="list_torrent" >
<div class="rows th">
<div class="tl_name"><?php echo _NAME?></div>
<div class="tl_percent">%</div>
<div class="tl_filesize"><?php echo _FILESIZE?></div>
<div class="tl_status"><?php echo _STATUS?></div>
<div class="tl_seeds"><?php echo _SEEDS?></div>
<div class="tl_peers"><?php echo _PEERS?></div>
<div class="tl_downloadspeed"><?php echo _DOWNLOADSPEED?></div>
<div class="tl_uploadspeed"><?php echo _UPLOADSPEED?></div>
<div class="tl_estime"><?php echo _ESTIMATEDTIME?></div>
<div class="tl_totalupload">Total Upload</div>
</div>
<div class="tbody" id="tbody">
</div>
</div>
</div>
<div id="torrent_info">
<ul class="mootabs_title">
<li title="tab1">normal</li>
<li title="tab2">Tracker</li>
<li title="tab3">user</li>
<li title="tab4">file</li>
<li title="tab5">speed</li>
<li title="tab6">log</li>
</ul>
<div id="tab1" class="mootabs_panel"></div>
<div id="tab2" class="mootabs_panel"></div>
<div id="tab3" class="mootabs_panel"></div>
<div id="tab4" class="mootabs_panel"></div>
<div id="tab5" class="mootabs_panel"></div>
<div id="tab6" class="mootabs_panel"></div>
</div>
</div>

<div id="down_left">
<select size="6" id="torrent_multiselect1" class="multipleSelect" multiple="multiple">
<option value="1">1</option>
<option value="2">1</option>
<option value="3">1</option>
<option value="4">1</option>
</select>
<div id="torrent_multiselect1_0" class="invisible"><img src="images/icon/status/2.png" alt="d">_downloading (<span id="sdownloading">0</span>)</div>
<div id="torrent_multiselect1_1" class="invisible"><img src="images/icon/status/4.png" alt="d">_finished (<span id="sfinished">0</span>)</div>
<div id="torrent_multiselect1_2" class="invisible"><img src="images/icon/status/C_active.png" alt="d">_active (<span id="sactive">0</span>)</div>
<div id="torrent_multiselect1_3" class="invisible"><img src="images/icon/status/C_Inactive.png" alt="d">_inactive (<span id="sinactive">0</span>)</div>
<!--{ this is the DIV for displaying  left menu (user) right click menu }-->
<select size="5" id="torrent_multiselect2" class="multipleSelect" multiple="multiple">
<? if(isset($userlist) && is_array($userlist)) { foreach($userlist as $index => $user) { ?>
<option value="<?=$user['uid']?>">1</option>
<? } } ?>
</select>
<? if(isset($userlist) && is_array($userlist)) { foreach($userlist as $index => $user) { ?>
<div id="torrent_multiselect2_<?=$index?>" class="invisible"><img src="images/user_<?=$user['online']?>.gif" id="useronline_<?=$user['uid']?>"><?=$user['user_id']?>(<span id="susering_<?=$user['uid']?>">0</span>)</div>
<? } } ?>
</div>
</div>
</div>

<!--{ this is the DIV for displaying torrent_list   right click menu }-->
<div class="ddmenu def" id="ListLeftClick" style="display:none">
<ul>
<li class="item" id="Upload_Torrent"><a href="#"><?=_Upload_Torrent?></a></li>
<li class="item" id="Url_Torrent"><a href="#"><?=_Url_Torrent?></a></li>
<li class="item" id="Creat_Torrent"><a href="#"><?=_Creat_Torrent?></a></li>
<li class="sepline"></li>
<li class="item" id="_Add_Feed"><a href="#"><?=_Add_Feed?></a></li>
</ul>
</div>
<!--{ this is the DIV for displaying torrent  right click menu }-->
<div class="ddmenu def" id="torrentclick" style="display:none">
<ul>
<li class="item" id="Start"><a href="#"><?=_Start?></a></li>
<li class="item" id="Kill"><a href="#"><?=_Kill?></a></li>
<li class="item" id="Del"><a href="#"><?=_Del?></a></li>
<li class="sepline"></li>
<li class="item" id="Edit_Torrent"><a href="#"><?=_Edit_Torrent?></a></li>
</ul>
</div>
<!--{ this is the DIV for displaying user list right click menu }-->
<? if($isadmin>0) { ?>
<div class="ddmenu def" id="torrent_multiselect2RightClick" style="display:none">
<ul>
<li class="item" id="Start"><a href="#"><?=_ADD_USER?></a></li>
</ul>
</div>
<? } ?>
<div class="ddmenu def" id="torrent_multiselect2_innerRightClick" style="display:none">
<ul>
<li class="title"></li>
<li class="item" id="_VIEW_PM"><a href="#"><?=_VIEW_PM?></a></li>
<li class="item" id="_SEND_PM"><a href="#"><?=_SEND_PM?></a></li>
<? if($isadmin>0) { ?>
<li class="item" id="_ADMIN_VIEW_HISTORY"><a href="#"><?=_VIEW_HISTORY?></a></li>
<li class="item" id="_ADMIN_DELETEUSER"><a href="#"><?=_DELETEUSER?> </a></li>
<li class="item" id="_ADMIN_EDITUSER"><a href="#"><?=_EDITUSER?> </a></li>
<? } ?>
<li class="item" id="_EDITUSER"><a href="#"><?=_EDITUSER?> </a></li>
</ul>
</div>


</body>
</html>