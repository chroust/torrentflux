<?php
include_once('db.php');
include_once("settingsfunctions.php");
include_once("functions.php");
session_name("TorrentFlux");
session_start();
$db = getdb();
loadSettings();
$Update_interval = 5;
$maxsaveTime=1000;
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<script type="text/javascript" src="js/mootools-1.2-core.js"></script>
<script type="text/javascript" src="js/mootools-1.2-more.js"></script>
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
		var request = new Request.JSON({url:'list_torrent.php?feeds='+selected_feeds+'&status='+selected_status+'&users='+selected_user,onComplete:function(data){
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
	NewTorrent=function(torrent){
		var tr =  new Element('div', {'class': 'rows','id':torrent.id}).injectInside($('tbody')).addEvent ('mousedown', $break);
		new Element('div',{'class':'tl_name'}).set('html',torrent.title).injectInside(tr);
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
			torrentControl(act_id,menu_bindon.id);
        }
    });
		progressbar[torrent.id]=new Fx.ProgressBar('div#tbody div#'+torrent.id+' .tl_percent img',{text:'div#tbody div#'+torrent.id+' .tl_percent span'}).start(torrent.percent,100);
	}
	ChangeTorrent=function(torrent){
		$$('div#tbody div#'+torrent.id+' .tl_name').set('html',torrent.title);
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
		}).post('action.php?action='+action+'&usejs=1&torrentid='+id);
	}
	OpenWindow=function(id,title){
				new MochaUI.Window({
				id: 'window_',
				title: title,
				evalScripts:1,
				resizable:0,
				loadMethod: 'xhr',
				contentURL: 'ajax.php?action=icon&usejs=1&id='+id,
				width: 500,
				height: 350,
				onContentLoaded:function(e){
				$$('form').each(function(thisform){
					if(thisform.enctype){
						thisform.action+='&usejs=1';
						thisform.addEvent('submit',function(){
							AIM.submit(this, {'onStart' : function(){console.log('start torrent upload')}, 'onComplete' : function(e){ console.log(e)
							evalscript(e);
							}})
						});
					}else{
							thisform.addEvent('submit',function(){
								thisform.set('send', {evalResponse: 1,url:thisform.action+'&usejs=1'}).send();
								return false;
							});
					}
				})
				}
			});
	}
window.addEvent('domready', function() {
	sorted1		=	new tableSoort('list_torrent')
	myTabs1		=	new mootabs('torrent_info', {height: '300px', width: '100%', useAjax: '1', ajaxUrl: 'ajax.php'});
	$$('div.icon_window').each(function(item){
		item.addEvent('click', function(){
			OpenWindow(item.id,item.title);
		});
		item.addEvent('mouseover',function(){
			item.setStyle('border','1px solid #ccc');
		}).addEvent('mouseout',function(){
			item.setStyle('border','1px solid #fff');
		});
	});
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
	window.addEvent('keydown', function(e){
		e = new Event(e); 
			if(e.key=='esc'){
				window.MochaUI.closeAll();
			}
	});
	// right click menu
    pagemenu = new DDMenu ('ListLeftClick', 'list_torrent', {
        onItemSelect: function (act_id, act_el, menu_bindon) {
		OpenWindow(act_id,act_id);
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

	get_data();
	
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
var UpdateInterval=<?php echo $Update_interval?>;

</script>
<div id="Mother">
<div id="top_icon_Bar">
<div class="icon icon_window" id="Upload_Torrent" title="Upload Torrent"><img src="images/icon/Upload_Torrent.PNG" alt="upload_torrent" /></div>
<div class="icon icon_window" id="Url_Torrent" title="Url Torrent"><img src="images/icon/Url_Torrent.PNG" alt="uplaod torrent from url" /></div>
<div class="icon icon_window" id="Creat_Torrent"><img src="images/icon/Creat_Torrent.PNG" alt="creat torrent" /></div>
<div class="icon-seperator"></div>
<div class="icon icon_control" id="Start" title="Start"><img src="images/icon/Start.PNG" alt="start" /></div>
<div class="icon icon_control" id="Stop" title="Kill"><img src="images/icon/Stop.PNG" alt="stop"/></div>
<div class="icon icon_control" id="Del" title="Del"><img src="images/icon/Del.PNG" alt="del"/></div>
<div class="icon-seperator"></div>
<div class="icon icon_window" id="New_Feed"><img src="images/icon/New_Feed.PNG" alt="new Feed"/></div>
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
<select size="5" id="torrent_multiselect1" class="multipleSelect" multiple="multiple">
	<option value="1">1</option>
	<option value="2">1</option>
	<option value="3">1</option>
	<option value="4">1</option>
</select>
<div id="torrent_multiselect1_0" class="invisible">_downloading (<span id="sdownloading">0</span>)</div>
<div id="torrent_multiselect1_1" class="invisible">_finished (<span id="sfinished">0</span>)</div>
<div id="torrent_multiselect1_2" class="invisible">_active (<span id="sactive">0</span>)</div>
<div id="torrent_multiselect1_3" class="invisible">_inactive (<span id="sinactive">0</span>)</div>

<?php
	$userlist=GetUserList();
?>
<select size="5" id="torrent_multiselect2" class="multipleSelect" multiple="multiple">
<?php 
	foreach($userlist as $user){
		?><option value="<?=$user['uid']?>">1</option>
		<?
	}
?>
</select>
<?php 
	foreach($userlist as $index =>$user){
		?><div id="torrent_multiselect2_<?=$index?>" class="invisible"><?=$user['user_id']?>(<span id="susering_<?=$user['uid']?>">0</span>)</div><?
	}
?>
</div>

</div>
</div>
<div class="ddmenu def" id="ListLeftClick" style="display:none">
<ul>
<li class="item" id="Upload_Torrent"><a href="#">_Upload_Torrent</a></li>
<li class="item" id="Url_Torrent"><a href="#">_Url_Torrent</a></li>
<li class="item" id="Creat_Torrent"><a href="#">_Creat_Torrent</a></li>
<li class="sepline"></li>
<li class="item" id="_Add_Feed"><a href="#">_Add_Feed</a></li>
</ul>
</div>
<div class="ddmenu def" id="torrentclick" style="display:none">
<ul>
<li class="item" id="Start"><a href="#">_Start</a></li>
<li class="item" id="Kill"><a href="#">_Kill</a></li>
<li class="item" id="Del"><a href="#">_Del</a></li>
</ul>
</div>




</body>
</html>