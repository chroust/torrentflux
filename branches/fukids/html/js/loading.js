loading = new Class({
    options:{
		id:'aaa',
		className:'no',
		loadingtext:'loading...',
		width:'80px',
		height:'20px',
		padding:'5px',
		backgroundcolor:'#FDFF37',
		left:window.getSize().x-150,
		top: '10px'
    },
	initialize:function(options){
		this.msgbox = new Element('div', {
			'class': this.options.className,
			'id':this.options.id,
			'styles': {
				'display': 'block',
				'position':'absolute',
				'display':'inline',
				'width': this.options.width,
				'height': this.options.height,
				'padding':this.options.padding,
				'background-color': this.options.backgroundcolor,
				'left': this.options.left,
				'top':this.options.top
			}
		}).inject($('Mother'),'before').set('html',this.options.loadingtext);
	},
	show:function(){
		this.msgbox.setStyles({'display':'inline','top': '10px','left':window.getSize().x-150});
	},
	hide:function(){
		this.msgbox.setStyle('display','none');
	}
});

