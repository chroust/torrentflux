<?php
include_once('db.php');
include_once("settingsfunctions.php");
include_once("functions.php");
session_name("TorrentFlux");
session_start();
$db = getdb();
loadSettings();
$Update_interval = 5;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<script type="text/javascript" src="js/mootools-1.2-core.js"></script>
<script type="text/javascript" src="js/mootools-1.2-more.js"></script>
<script type="text/javascript" src="js/sortableTable.js"></script>
<script type="text/javascript" src="js/mootabs1.2.js"></script>
<script type="text/javascript" src="js/webtoolkit.aim.js"></script>
<script type="text/javascript" src="js/uimenu.js"></script>		
<!--[if IE]>
	<link href="css/ie.css" rel="stylesheet" type="text/css" media="screen" />
	<script type="text/javascript" src="js/excanvas-compressed.js"></script>
<![endif]-->
<script type="text/javascript" src="js/mocha.js" charset="utf-8"></script>
<link href="css/mocha.css" rel="stylesheet" type="text/css" />
<link href="css/sortableTable.css" rel="stylesheet" type="text/css" />
<link href="css/mootabs1.2.css" rel="stylesheet" type="text/css" />
<link href="css/menu.css" rel="stylesheet" type="text/css" media="screen" />
<!--[if IE]>
	<link href="css/ie.css" rel="stylesheet" type="text/css" media="screen" />
<![endif]-->
</head>
<body>
<script  type="text/javascript">
function echo (a){
	console.log(a);
}
function getSelText(){
	var txt = '';
	if (window.getSelection){
		txt = window.getSelection();
	}else if (document.getSelection){
		txt = document.getSelection();
	}else if (document.selection){
		txt = document.selection.createRange().text;
	}else return;
}
document.onselectstart=new Function ("return false");
if (window.sidebar){
	document.onmousedown=$break;
}
	var get_data=function(){
		var request = new Request.JSON({url:'list_torrent.php?feeds='+selected_feeds+'&status='+selected_status+'&tags='+selected_tags,onComplete:function(data){
				update_data(data['torrents']);
				document.title='TorrentFlux----  Total Upload :'+data['global']['totalUpSpeed']+'kB/s   Total Download :'+data['global']['totalDownSpeed']+'kB/s';
				timer=setTimeout('get_data()', UpdateInterval*1000);
			}}).get();
	}
	forceUpdate=function(){
		clearTimeout(timer);
		get_data();
	}
	update_data =function (Torrents){
		var thisarray=new Array();
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
			upSpeed[torrent.id][uploadcount]=parseFloat(torrent.up_speed);
			downSpeed[torrent.id][uploadcount]=parseFloat(torrent.down_speed);
			MaxdownSpeed[torrent.id]=(downSpeed[torrent.id][uploadcount]>MaxdownSpeed[torrent.id])?downSpeed[torrent.id][uploadcount]:MaxdownSpeed[torrent.id];
		
		});
		$$('#tbody div.rows').each(function(item){
			if(thisarray[item.id] !==1){
				$(item.id).destroy();
				downSpeed[item.id]=new Array();
				upSpeed[item.id]=new Array();
			}
		});
		uploadcount++;
	}
	NewTorrent=function(torrent){
		var tr =  new Element('div', {'class': 'rows','id':torrent.id}).injectInside($('tbody'));
		new Element('div',{'class':'tl_name'}).set('html',torrent.title).injectInside(tr);
		new Element('div',{'class':'tl_percent'}).set('html',torrent.percent).injectInside(tr);
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
					if(selecting)selecting.removeClass('torrent_list_clicked');
				tr.addClass('torrent_list_clicked');
				selecting=tr;
				myTabs1.activate(down_selecting_tab);
			}
		});
		torrent_RightMenu  = new UI.Menu( torrent.id, { event : 'rightClick' } );
		torrent_RightMenu.addItems( [
		{ label :'_Start', onclick : function() {}},
		{ label :'_Kill', onclick : function() {}},
		{ label :'_Del', onclick : function() {}},
		{ label :'_DelAnd',id:torrent.id+'_DelAnd'}
		]);
		demoMenu3 = torrent_RightMenu.addSubMenu( torrent.id+'_DelAnd');
		demoMenu3.addItems([
		{label : '_RemoveTorrentAndFile'},
		{label : '_RemoveTorrent'},
		{label : '_RemoveFile'},
		]);
	}
	ChangeTorrent=function(torrent){
		$$('div#tbody div#'+torrent.id+' .tl_name').set('html',torrent.title);
		$$('div#tbody div#'+torrent.id+' .tl_percent').set('html',torrent.percent);
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
		new Request.HTML({complete:function(){echo('213123');}}).post('action.php?action='+action+'&usejs=1&torrentid='+id);
		forceUpdate();
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
	sorted1 = new tableSoort('list_torrent')
	myTabs1 = new mootabs('torrent_info', {height: '300px', width: '100%', useAjax: '1', ajaxUrl: 'ajax.php'});
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
			if(selecting){
				var myHTMLRequest = new Request.HTML({}).post("action.php?action="+item.title+"&usejs=1&torrentid="+selecting.id);
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
				console.log($('torrent_multiselect1').getSelected());			
				MochaUI.closeAll();
			}
	});
	
	// right click menu
	uiMenu_listTorrent  = new UI.Menu( 'torrent_list_div', { event : 'rightClick' } );
	uiMenu_listTorrent.addItems([
			{label :'_Upload_Torrent', onclick : function(){}}
		,   {label :'_Url_Torrent',  onclick : function(){} }
		,   {label :'_Creat_Torrent',  onclick : function(){} }
		,   {separator : true}
		,   {label :'_Add_Feed', icon : 'menu/add.png' }
		]);

	//uiMenu_listTorrent.updateItemOnclick( 'button11', function() { if ( uiMenu_listTorrent.getSeparator(0) ) uiMenu_listTorrent.removeSeparator(0); else alert('already removed !'); } );

	get_data();
});
var selecting;
var uploadcount=0;
var down_selecting_tab;
var selected_feeds =0;
var selected_status =0;
var selected_tags =0;
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
				<div axis="string" class="tl_name"><?php echo _NAME?></div>
				<div axis="number" class="tl_percent">%</div>
				<div axis="string" class="tl_filesize"><?php echo _FILESIZE?></div>
				<div axis="string" class="tl_status"><?php echo _STATUS?></div>
				<div axis="number" class="tl_seeds"><?php echo _SEEDS?></div>
				<div axis="number" class="tl_peers"><?php echo _PEERS?></div>
				<div axis="string" class="tl_downloadspeed"><?php echo _DOWNLOADSPEED?></div>
				<div axis="string" class="tl_uploadspeed"><?php echo _UPLOADSPEED?></div>
				<div axis="string" class="tl_estime"><?php echo _ESTIMATEDTIME?></div>
				<div axis="string" class="tl_totalupload">Total Upload</div>
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
<select size="5" id="torrent_multiselect1">
<option VALUE="0">all</option>
 <option VALUE="1">downloading</option>
 <option VALUE="2">finished</option>
</select>
<select size="2" id="torrent_multiselect2">
<option >user......</option>
</select>
<select id="torrent_multiselect3" size="20">
<option VALUE="0">all feed</option>
</select>
</div>
</div>
</div>

<div class="ddmenu def" id="ddmenu_torrentList" style="display:none">
<ul>
<li class="item" id="Upload_Torrent"><a href="#"><?php echo _Upload_Torrent ?></a></li>
<li class="item" id="Url_Torrent"><a href="#"><?php echo _Url_Torrent ?></a></li>
<li class="item" id="Creat_Torrent"><a href="#"><?php echo _Creat_Torrent ?></a></li>
<li class="sepline"></li>
<li class="item" id="New_Feed"><a href="#"><?php echo _New_Feed ?></a></li>
</ul>
</div>

</body>
</html>