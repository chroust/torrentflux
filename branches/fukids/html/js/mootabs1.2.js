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
			mouseOverClass:		'active',
			useAjax: 			false,
			ajaxUrl: 			'',
			ajaxOptions: 		{method:'get'},
			ajaxLoadingText: 	'Loading...'
		}, options || {});
		this.el = $(element);
		this.elid = element;
		this.el.setStyles({
			height: this.options.height,
			width: this.options.width
		});
		
		this.titles = $$('#' + this.elid + ' ul.mootabs_title li');
		this.panelHeight = (this.options.height);
		this.panels = $$('#' + this.elid + ' .mootabs_panel');
		
		this.panels.setStyle('height', this.panelHeight);
		
		this.titles.each(function(item) {
			item.addEvent('click', function(){
					item.removeClass(this.options.mouseOverClass);
					this.activate(item);
				}.bind(this)
			);
			
			item.addEvent('mouseover', function() {
				if(item != this.activeTitle)
				{
					item.addClass(this.options.mouseOverClass);
				}
			}.bind(this));
			
			item.addEvent('mouseout', function() {
				if(item != this.activeTitle){
					item.removeClass(this.options.mouseOverClass);
				}
			}.bind(this));
		}.bind(this));
	},
	activate: function(tab, skipAnim){	
		if(!$defined(selecting))
				return false;
		window.fireEvent('TabExit');
		window.removeEvents('TabExit').removeEvents('TabReady');	
		if(! $defined(skipAnim))
		{
			skipAnim = false;
		}
		if($type(tab) == 'string') 
		{
			myTab = $$('#' + this.elid + ' ul li').filterByAttribute('title', '=', tab)[0];
			tab = myTab;
		}
		
		if($type(tab) == 'element')
		{
			down_selecting_tab = tab;
			var newTab = tab.getProperty('title');
			this.panels.removeClass('active');
			this.activePanel = this.panels.filter('#'+newTab)[0];
			this.activePanel.addClass('active');
			$$('#' + this.elid + ' ul.mootabs_title li').removeClass('active');
			$$('#' + this.elid + ' ul.mootabs_title li a').removeClass('active');
			tab.addClass('active');
			tab.getChildren('a').addClass('active');
			this.activeTitle = tab;
			new Request.HTML({
				evalScripts:true,
				update:this.activePanel,
				onComplete:function(data){
					window.fireEvent('TabReady');
				}
			}).get('ajax.php?action=tabs&tab='+this.activeTitle.getProperty('title')+'&torrentId='+selecting);
		}
	}
});
