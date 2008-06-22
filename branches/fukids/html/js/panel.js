var currheight;
var tabstatues;
window.addEvent('domready', function(){
	window.addEvent('resize', function(){
		if(currheight !=window.getSize().x){
			goresize();
		}
	currheight = window.getSize().x;
	});
	goresize();
});
var goresize=function(){
	windowwidth=window.getSize().x;
	windowheight=window.getSize().y;
		if(windowwidth > 1000){
			MaxWidth();
		}else{
			MinWidth();
		}
		if(windowwidth >700){
			$('searchdiv').setStyle('display','inline');
		}else{
			$('searchdiv').setStyle('display','none');
		}
		if(windowheight < 600){
			CloseTabTable();
		}else{
			OpenTabTable();
		}
		if(windowwidth < 500){
			$$('img.progressbar').setStyle('display','none');
			$$('.tl_percent').setStyle('width','50px');
		}else{
			$$('img.progressbar').setStyle('display','inline');
			$$('.tl_percent').setStyle('width','170px');
		}
}
var MaxWidth=function(){
	$('down_right').setStyle('width','85%');
	$('down_left').setStyle('display','inline');
}
var MinWidth=function(){
	$('down_left').setStyle('display','none');
	$('down_right').setStyle('width','100%');
}
var OpenTabTable=function(){
	var thisheight=windowheight-300;
	$('torrent_list_div').setStyle('height',thisheight+'px');
	$('torrent_info').setStyle('display','inline');
}
var CloseTabTable=function(){
	var thisheight=windowheight-60;
	$('torrent_list_div').setStyle('height',thisheight+'px');
	$('torrent_info').setStyle('display','none');
}