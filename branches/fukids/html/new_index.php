<?php
include_once('db.php');
include_once("settingsfunctions.php");
include_once("functions.php");
session_name("TorrentFlux");
session_start();
$db = getdb();
loadSettings();
$Update_interval = 5000;
?>
<html>
<head>
<script type="text/javascript" src="js/mootools-1.2-core.js"></script>
<script type="text/javascript" src="js/mootools-1.2-more.js"></script>
<script type="text/javascript" src="js/sortableTable.js"></script>
<script type="text/javascript" src="js/mootabs1.2.js"></script>
<script type="text/javascript" src="js/webtoolkit.aim.js"></script>
<script type="text/javascript" src="js/moo.ddmenu.0.21.js"></script>		
<!--[if IE]>
	<script type="text/javascript" src="js/excanvas-compressed.js"></script>		
<![endif]-->
<script type="text/javascript" src="js/mocha.js" charset="utf-8"></script>	


<link href="css/mocha.css" rel="stylesheet" type="text/css" />
<link href="css/sortableTable.css" rel="stylesheet" type="text/css" />
<link href="css/mootabs1.2.css" rel="stylesheet" type="text/css" />
<link href="css/ddmenu.css" rel="stylesheet" type="text/css" media="screen" />
</head>
<body>
<script  type="text/javascript">
function echo (a){
	console.log(a);
}
window.addEvent('domready', function() {
	forceUpdate=function(){
		clearTimeout(timer);
		get_data();
	}
	get_data=function(){
		var request = new Request.JSON({url:'list_torrent.php?feeds='+selected_feeds+'&status='+selected_status+'&tags='+selected_tags, 
			onComplete: function(data) {
				update_data(data['torrents']);
				document.title='TorrentFlux----  Total Upload :'+data['global']['totalUpSpeed']+'kB/s   Total Download :'+data['global']['totalDownSpeed']+'kB/s';
				timer=setTimeout('get_data()', <?php echo $Update_interval; ?>);
			}
		}).get();
	}
	update_data =function (Torrents){
		var thisarray=new Array();
		Torrents.each(function(torrent){
		thisarray[torrent.id]=1
			if($defined($(torrent.id))){
				ChangeTorrent(torrent);
			}else{
				NewTorrent(torrent);
			}
		});
		$$('#tbody div.rows').each(function(item){
			if(thisarray[item.id] !==1) $(item.id).destroy();
		});
	}
	NewTorrent=function(torrent){
		var tr =  new Element('div', {'class': 'rows','id':torrent.id}).injectInside($('tbody'));
		new Element('div',{'class':'tl_name'}).set('html',torrent.title).injectInside(tr);
		new Element('div',{'class':'tl_percent'}).set('html',torrent.percent).injectInside(tr);
		new Element('div',{'class':'tl_filesize'}).set('html',torrent.size).injectInside(tr);
		new Element('div',{'class':'tl_status'}).set('html',torrent.status).injectInside(tr);
		new Element('div',{'class':'tl_seeds'}).set('html',torrent.seeds).injectInside(tr);
		new Element('div',{'class':'tl_peers'}).set('html',torrent.peers).injectInside(tr);
		new Element('div',{'class':'tl_downloadspeed'}).set('html',torrent.down_speed).injectInside(tr);
		new Element('div',{'class':'tl_uploadspeed'}).set('html',torrent.up_speed).injectInside(tr);
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
		new DDMenu ('ddmenu_torrentAction', tr, {
		onOpen: function (e) { 
			this.enableItems(true);
		}, 
		onItemSelect: function (act_id, act_el, menu_bindon) {
			torrentControl(act_id,tr.id);
		}
		});
	}
	ChangeTorrent=function(torrent){

		$$('div#tbody div#'+torrent.id+' .tl_name').set('html',torrent.title);
		$$('div#tbody div#'+torrent.id+' .tl_percent').set('html',torrent.percent);
		$$('div#tbody div#'+torrent.id+' .tl_filesize').set('html',torrent.size);
		$$('div#tbody div#'+torrent.id+' .tl_status').set('html',torrent.status);
		$$('div#tbody div#'+torrent.id+' .tl_seeds').set('html',torrent.seeds);
		$$('div#tbody div#'+torrent.id+' .tl_peers').set('html',torrent.peers);
		$$('div#tbody div#'+torrent.id+' .tl_downloadspeed').set('html',torrent.down_speed);
		$$('div#tbody div#'+torrent.id+' .tl_uploadspeed').set('html',torrent.up_speed);
		$$('div#tbody div#'+torrent.id+' .tl_estime').set('html',torrent.estTime);
		$$('div#tbody div#'+torrent.id+' .tl_totalupload').set('html',torrent.totalUpload);
	}
	torrentControl=function(action,id){
		new Request.HTML({complete:function(){echo('213123');}}).post('action.php?action='+action+'&usejs=1&torrentid='+id);
		forceUpdate();
	}
	sorted1 = new tableSoort('list_torrent')
	myTabs1 = new mootabs('torrent_info', {height: '300px', width: '100%', useAjax: '1', ajaxUrl: 'ajax.php'});
	function OpenWindow(id,title){
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
								thisform.set('send', {evalResponse: 1,
									url:thisform.action+'&usejs=1',
								}).send();
								return false;
							})
					}
				})
				}
			});
	}

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
	pagemenu = new DDMenu ('ddmenu_torrentList', $('torrent_list_div'), {			
		onOpen: function (e) { 
			this.enableItems(true);						  //enable all 
		}, 
		onItemSelect: function (act_id, act_el, menu_bindon) {
			OpenWindow(act_id,act_id);
			console.info("menu action -> item id: \"%s\" from: %o in %o", act_id, act_el, menu_bindon) 
		}
	});
	get_data();
});
var selecting;
var down_selecting_tab;
var selected_feeds =0;
var selected_status =0;
var selected_tags =0;
</script>
<div id="Mother">
<div id="top_icon_Bar">
<div class="icon icon_window" id="Upload_Torrent" title="Upload Torrent"><img src="images/icon/Upload_Torrent.PNG" /></div>
<div class="icon icon_window" id="Url_Torrent" title="Url Torrent"><img src="images/icon/Url_Torrent.PNG" /></div>
<div class="icon icon_window" id="Creat_Torrent"><img src="images/icon/Creat_Torrent.PNG" /></div>
<div class="icon-seperator"></div>
<div class="icon icon_control" id="Start" title="Start"><img src="images/icon/Start.PNG" /></div>
<div class="icon icon_control" id="Stop" title="Kill"><img src="images/icon/Stop.PNG" /></div>
<div class="icon icon_control" id="Del" title="Del"><img src="images/icon/Del.PNG" /></div>
<div class="icon-seperator"></div>
<div class="icon icon_window" id="New_Feed"><img src="images/icon/New_Feed.PNG" /></div>
</div>

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
		</ul>
		<div id="tab1" class="mootabs_panel">
		</div>
		<div id="tab2" class="mootabs_panel">
		</div>
		<div id="tab3" class="mootabs_panel">
		</div>
		<div id="tab4" class="mootabs_panel">
		</div>
		<div id="tab5" class="mootabs_panel">
		</div>
	</div>
</div>
<div id="down_left">
<SELECT MULTIPLE size="5" id="torrent_multiselect1">
<OPTION VALUE="0">all</OPTION>
 <OPTION VALUE="1">downloading</OPTION>
 <OPTION VALUE="2">finished</OPTION>
</SELECT>
<SELECT MULTIPLE size="2" id="torrent_multiselect2">
<option >user......</option>
</SELECT>
<SELECT MULTIPLE  id="torrent_multiselect3" size="20">
<OPTION VALUE="0">all feed</OPTION>
</SELECT>

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

<div class="ddmenu def" id="ddmenu_torrentAction" style="display:none">
<ul>
<li class="item" id="Start"><a href="#"><?php echo _Start ?></a></li>
<li class="item" id="Kill"><a href="#"><?php echo _Kill ?></a></li>
<li class="item" id="Del"><a href="#"><?php echo _Del ?></a></li>
<li class="sepline"></li>
</ul>
</div>

</body>
</html>
