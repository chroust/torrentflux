// added javascript function: 
// window.TabReady : called when a tab is ready
// window.TabExit : called when a tab is removed
var mootabs = new Class({
	initialize: function(element, options) {
		this.options = Object.extend({
			width:				'100%',
			height:				'170px',
			changeTransition:	Fx.Transitions.Bounce.easeOut,
			duration:			1000,
			mouseOverClass:		'hover',
			useAjax: 			false,
			ajaxUrl: 			'',
			ajaxOptions: 		{method:'get'},
			ajaxLoadingText: 	'Loading...',
			ultitle:			'mootabs_title',
			panelsclass:		'mootabs_panel'
		}, options || {});
		this.el = $(element);
		this.elid = element;
		this.el.setStyles({
			height: this.options.height,
			width: this.options.width
		});
		this.titles = $$('#'+this.elid+' div ul.'+this.options.ultitle+' li');
		this.panelHeight = (this.options.height);

		this.titles.each(function(item) {
			item.addEvent('click', function(e){
					item.getChildren('div').removeClass(this.options.mouseOverClass);
					this.activate(item);
				}.bind(this)
			);
			
			item.addEvent('mouseover', function() {
				if(item != this.activeTitle)
				{
					item.getChildren('div').addClass(this.options.mouseOverClass);
				}
			}.bind(this));
			
			item.addEvent('mouseout', function() {
				if(item != this.activeTitle){
					item.getChildren('div').removeClass(this.options.mouseOverClass);
				}
			}.bind(this));
		}.bind(this));
	},
	activate: function(tab, skipAnim){
			if(!selecting && this.elid =='torrent_info')
					return false;
		window.fireEvent('TabExit');
		window.removeEvents('TabExit').removeEvents('TabReady');	
			if(! $defined(skipAnim)){
				skipAnim = false;
			}
			if($type(tab) == 'string'){
				tab = $$('#'+this.elid+' div ul li').filter(function(item){
					return item.getProperty('title')==tab;
				});
			}
		down_selecting_tab = tab;
		this.activeTitle = tab;
		$$('#' + this.elid + ' div ul.mootabs_title li').removeClass('active');
		tab.addClass('active');
		$$('#'+this.elid+' .mootabs_panel').removeClass('active');
			if(this.elid =='torrent_info'){
				new Request.HTML({
					evalScripts:true,
					update:$('mootabs_panel'),
					onComplete:function(data){
						window.fireEvent('TabReady');
					}
				}).get('ajax.php?action=tabs&tab='+this.activeTitle.getProperty('title')+'&torrentId='+selecting);
			}
			if($defined($(this.elid).getElement('div div#'+tab.getProperty('title')))){
				$(this.elid).getElement('div#'+tab.getProperty('title')).addClass('active');
			}
	}
});
