Element.extend({disableSelection: function () { if (window.ActiveXObject) this.onselectstart = function() {return false}; this.style.MozUserSelect = "none"; return this; } }); 
var currheight;
var tabstatues;
window.addEvent('domready', function(){
	window.addEvent('resize', function(){
		if(currheight !=window.getSize().x){
			goresize();
		}
	currheight = window.getSize().x;
	});
	setTimeout("goresize()",1000);
});

var goresize=function(){
	windowwidth=window.getSize().x;
	windowheight=window.getSize().y;
		if(windowwidth > 1000){
			OpenSideBar();
		}else{
			CloseSideBar();
		}
		if(windowheight < 600){
			CloseTabTable();
		}else{echo (windowheight);
			OpenTabTable();
		}
}
var OpenSideBar=function(){
	$('down_right').setStyle('width','85%');
	$('down_left').setStyle('display','inline');
}
var CloseSideBar=function(){
	$('down_left').setStyle('display','none');
	$('down_right').setStyle('width','100%');
}
var OpenTabTable=function(){
	$('torrent_list_div').setStyle('height',windowheight-300);
	$('torrent_info').setStyle('display','inline');
}
var CloseTabTable=function(){
	$('torrent_list_div').setStyle('height',windowheight-50);
	$('torrent_info').setStyle('display','none');

}