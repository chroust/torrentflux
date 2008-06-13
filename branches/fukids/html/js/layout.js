/*
Script: 
	mui-layout.js v.0.1
License:
	MIT-style license.

Copyright:
	copyright (c) 2007 jander, <jander.sy@163.com>	
*/

/**
 * 
 */
Mui.Box=new Class({
	Implements: [Events, Options],
	options:{
		width:'auto',
		height:'auto',
		margin:[0,0,0,0]
	},
	initialize: function(options){
		this.setOptions(options);
		this.id=this.options.el;
		this.el=$(this.id);
		if(this.el!=document.body)
			this.el.setStyles({
				'width':this.options.width,
				'height':this.options.height
			});
		this.el.setStyles({
			margin:this.options.margin[0]+'px '+this.options.margin[1]+'px '+this.options.margin[2]+'px '+this.options.margin[3]+'px'
		})
	},	
	render:function(){
		if(this.splitbar)
			this.splitbar.render();
	}
});

/*
there are headerEl(or not),footerEl(or not) and contentEl(must) in a Panel.
a panel can change the contentEl height according to the heights of it's headerEl and footerEl when it's height changed. 
 var panel=new Panel({
 	el:'myid',
 	headerEl:'headerEl',
 	contentEl:'contentEl',
 	footerEl:'footerEl',
	margin:[0,0,0,0]
	width:200,
 	height:200,
 	split:true,       //for creating splitbar
	orientation:left  //for creating splitbar	
 }); 	
 */
Mui.Panel=new Class({
	Extends: Mui.Box,	
	initialize: function(options){
		this.parent(options);
		this.el.addClass('m-panel');
		this.headerHeight=0;
		this.footerHeight=0;
		if(this.options.headerEl){			
			$(this.options.headerEl).addClass('m-panel-header');
			$(this.options.headerEl).addClass('unselectable');
			this.headerHeight=$(this.options.headerEl).getBoundHeight();
		}
		if(this.options.footerEl){
			$(this.options.footerEl).addClass('m-panel-footer');
			$(this.options.footerEl).addClass('unselectable');			
			this.footerHeight=$(this.options.footerEl).getBoundHeight();
		}
		
		this.contentEl=$(this.options.contentEl);
		this.contentEl.addClass('m-panel-body');
		this.contentEl.setStyle('overflow','auto');
	},
	render: function(){
		var cs=this.el.getClientSize();
		this.contentEl.setBoundHeight(cs.height-this.headerHeight-this.footerHeight);
		if (this.contentEl.tagName.toLowerCase() == 'iframe') {
			this.contentEl.set('width',cs.width);
		}
		this.parent();
	}
})

/*
when a splitbar creat ,the splitbar options can get from the box options.  
*/
Mui.Splitbar=new Class({
	Implements: [Events, Options],
	options:{
		orientation:'left',
		maxSize:800,
		minSize:0,
		splitSize:5,
		collopsedSize:24
		
	},
	initialize: function(resizeBox, options){
		this.setOptions(options);
		
		this.resizeBox=resizeBox;
		this.resizeEl=this.resizeBox.el;		
		this.splitEl=new Element('div',{
			'id':this.resizeEl.get('id')+'-split',
			'class':'m-split-'+this.options.orientation
		}).inject(this.resizeEl,'after');

		this.document=$(document);	
		this.bound = {
			'dragStart': this.dragStart.bind(this),
			'dragging': this.dragging.bind(this),
			'dragged': this.dragged.bind(this),
			'onToggle':  this.onToggle.bind(this),
			'onMiniToggle':this.onMiniToggle.bind(this)
		};
		this.splitEl.addEvent('mousedown',this.bound.dragStart);	
		
		//init toggle
		this.collapsedEl=new Element('a',{
			href:'#',
			id:this.resizeEl.get('id')+'-collapse',
			styles:{				
				'display':'none',
				'margin':resizeBox.options.margin[0]+'px '+resizeBox.options.margin[1]+'px '+resizeBox.options.margin[2]+'px '+resizeBox.options.margin[3]+'px'
			},
			events: {
				'click': function(event){
					event.stop();
					return false;
				}
			},
			'class':'m-split-collapsed'			
		}).inject(this.resizeEl,'after');	
		
		this.isMiniMode=(this.resizeBox.options.headerEl==null);
		if(!this.isMiniMode){
			this.initToggle();
		}else{
			this.initMiniToggle();
		}
		
		resizeBox.splitbar=this;	
	},
	render:function(){
		var resizeSize=this.resizeEl.getBoundSize();		
		switch(this.options.orientation){
			case 'left':
				this.splitEl.setStyles({
					'left':resizeSize.width,
					'width':this.options.splitSize,
					'height':resizeSize.height
				});
				this.collapsedEl.setBoundHeight(resizeSize.height);
				if(this.isMiniMode){
					var toggleSize=Math.max(this.toggleEl.getBoundWidth(),this.toggleEl.getBoundHeight());
					this.toggleEl.setStyles({
						'left':resizeSize.width,
						'top':(resizeSize.height-toggleSize)/2
					})
				}
				break;
			case 'right':
				this.splitEl.setStyles({
					'left':this.resizeEl.getStyle('left').toInt()-this.options.splitSize,	
					'width':this.options.splitSize,
					'height':resizeSize.height
				});
				this.collapsedEl.setBoundHeight(resizeSize.height);
				if(this.isMiniMode){
					var toggleSize=Math.max(this.toggleEl.getBoundWidth(),this.toggleEl.getBoundHeight());
					this.toggleEl.setStyles({
						'right':resizeSize.width,
						'top':(resizeSize.height-toggleSize)/2
					})					
				}
				break;
			case 'top':						
				this.splitEl.setStyles({
					'top':resizeSize.height,
					'height':this.options.splitSize,
					'width':resizeSize.width
				});
				this.collapsedEl.setBoundWidth(resizeSize.width);
				if (this.isMiniMode) {
					var toggleSize=Math.max(this.toggleEl.getBoundWidth(),this.toggleEl.getBoundHeight());
					this.toggleEl.setStyles({
						'left': (resizeSize.width - toggleSize) / 2,
						'top': resizeSize.height
					})
				}
				break;
			case 'bottom':
				this.splitEl.setStyles({
					'top':this.resizeEl.getStyle('top').toInt()-this.options.splitSize,	
					'height':this.options.splitSize,
					'width':resizeSize.width
				});
				this.collapsedEl.setBoundWidth(resizeSize.width);
				if (this.isMiniMode) {
					var toggleSize=Math.max(this.toggleEl.getBoundWidth(),this.toggleEl.getBoundHeight());
					this.toggleEl.setStyles({
						'left': (resizeSize.width - toggleSize) / 2,
						'bottom': resizeSize.height
					})
				}
				break;			
		}		
	},
	dragStart:function(event){
		event.stop();
		var cs=$(document.body).getClientSize();
		this.maskEl=new Element("div",{
			'class': 'm-split-mask'
		}).inject(this.resizeEl,'after');
		
		this.document.addEvent('mousemove',this.bound.dragging);
		this.document.addEvent('mouseup',this.bound.dragged);
		this.splitEl.addClass('m-split-dragging'); 
	},
	
	dragging:function(event){
		var event = new Event(event);
		event.stop();				
		var sw=this.options.splitSize;
		var container=this.resizeEl.getParent();
		switch(this.options.orientation){
			case 'right':
				var cs=container.getClientSize();
				var now=event.page.x-container.getPosition().x;

				var sideSize=cs.width-now-sw;
				if( sideSize >this.options.minSize && sideSize<this.options.maxSize ) {
					this.sideSize=sideSize;
					this.splitEl.style.left=now+'px';
				}
				break;
			case 'top':				
				var now=event.page.y-container.getPosition().y;
				if(now >this.options.minSize && now<this.options.maxSize) {
					this.sideSize=now;
					this.splitEl.style.top=now+'px';	
				}
				break;
			case 'bottom':
				var cs=container.getClientSize();
				var now=event.page.y-container.getPosition().y;
				var sideSize=cs.height-now-sw;
				if( sideSize >this.options.minSize && sideSize<this.options.maxSize){
					this.sideSize=sideSize;
					this.splitEl.style.top=now+'px';
				}
				break;

			default:
				var now=event.page.x-container.getPosition().x;
				if(now >this.options.minSize && now<this.options.maxSize) {
					this.sideSize=now;
					this.splitEl.style.left=now+'px';
				}
				break;
		}
	},
	
	dragged:function(event){
		event.stop();
		this.splitEl.removeClass('m-split-dragging');
		this.maskEl.remove();
		this.document.removeEvent('mousemove',this.bound.dragging);
		this.document.removeEvent('mouseup',this.bound.dragged);

		var cs = this.resizeEl.getParent().getClientSize();
		var sw=this.options.splitSize;
		switch(this.options.orientation){
			case 'left':
				this.resizeEl.setBoundWidth(this.sideSize);
				this.splitEl.setStyle('left',this.sideSize);
				break;
			case 'right':
				this.resizeEl.setBoundWidth(this.sideSize);
				this.splitEl.setStyle('left',cs.width-this.sideSize-sw);
				break;
			case 'top':	
				this.resizeEl.setBoundHeight(this.sideSize);
				this.splitEl.setStyles('top',this.sideSize);
				break;
			case 'bottom':
				this.resizeEl.setBoundHeight(this.sideSize);
				this.splitEl.setStyle('top',cs.height-this.sideSize-sw);	
				break;
		}
		this.resizeBox.container.render();
	},
	initToggle:function(){
		this.toggleEl=new Element("a", {
			href:'#',
			'id':this.resizeEl.get('id')+'-toggle',
			'class':'m-split-toggle-' + this.options.orientation,
			events: {
				'click': this.bound.onToggle
			}
		}).inject($(this.resizeBox.options.headerEl),'top');		

		this.toggleProxyEl = new Element("a", {
			'id':this.resizeEl.get('id')+'-toggle-proxy',
			href:'#',
			events: {
				'click': this.bound.onToggle
			}
		}).inject(this.collapsedEl);	

		switch(this.options.orientation){
			case 'left':
				this.toggleProxyEl.addClass('m-split-toggle-right');
				this.collapsedEl.setStyle('width',this.options.collopsedSize);
				this.collapsedEl.setStyle('left',0);
				break;
			case 'right':
				this.toggleProxyEl.addClass('m-split-toggle-left');	
				this.collapsedEl.setStyle('width',this.options.collopsedSize);
				this.collapsedEl.setStyle('right',0);
				break;
			case 'top':
				this.toggleProxyEl.addClass('m-split-toggle-bottom');
				this.collapsedEl.setStyle('height',this.options.collopsedSize);
				this.collapsedEl.setStyle('top',0);				
				break;
			case 'bottom':
				this.toggleProxyEl.addClass('m-split-toggle-top');
				this.collapsedEl.setStyle('height',this.options.collopsedSize);
				this.collapsedEl.setStyle('bottom',0);
				break;
			default:
				throw 'Not insist the orientation "'+this.options.orientation
		}
	},
	
	initMiniToggle:function(){
		this.toggleEl=new Element("a", {
			href:'#',
			'class':'m-split-mini-toggle-' + this.options.orientation,
			events: {
				'click': this.bound.onMiniToggle
			}
		}).inject(this.resizeEl,'after');
		switch(this.options.orientation){
			case 'left':
				this.collapsedEl.setStyle('width',this.options.collopsedSize);
				this.collapsedEl.setStyle('left',0);
				break;
			case 'right':
				this.collapsedEl.setStyle('width',this.options.collopsedSize);
				this.collapsedEl.setStyle('right',0);
				break;
			case 'top':
				this.collapsedEl.setStyle('height',this.options.collopsedSize);
				this.collapsedEl.setStyle('top',0);		
				break;
			case 'bottom':
				this.collapsedEl.setStyle('height',this.options.collopsedSize);
				this.collapsedEl.setStyle('bottom',0);
				break;
			default:
				throw 'Not insist the orientation "'+this.options.orientation
		}			
	},
	
	onToggle:function(event){
		event.stop();
		if (this.resizeBox.collapsed) {
			if(this.options.orientation=='left' || this.options.orientation=='right')
				this.resizeEl.setBoundWidth(this.resizeBox.options.width);
			else
				this.resizeEl.setBoundHeight(this.resizeBox.options.height);
			this.resizeEl.setStyles({
				'visibility':'visible'
			});
			this.collapsedEl.setStyle('display','none');
			this.splitEl.addEvent('mousedown',this.bound.dragStart);
			this.splitEl.setStyle('cursor','');	
			this.resizeBox.collapsed=false;
		}else {
			if(this.options.orientation=='left' || this.options.orientation=='right')
				this.resizeEl.setStyle('width',this.options.collopsedSize);
			else
				this.resizeEl.setStyle('height',this.options.collopsedSize);
			this.resizeEl.setStyles({
				'visibility':'hidden'
			});
			this.collapsedEl.setStyle('display','block');
			
			this.splitEl.removeEvent('mousedown',this.bound.dragStart);	
			this.splitEl.setStyle('cursor','default');
			this.resizeBox.collapsed=true;
		}
		this.resizeBox.container.render();
	},
	
	onMiniToggle:function(event){
		event.stop();
		if (this.resizeBox.collapsed) {
			if(this.options.orientation=='left' || this.options.orientation=='right')
				this.resizeEl.setBoundWidth(this.resizeBox.options.width);
			else
				this.resizeEl.setBoundHeight(this.resizeBox.options.height);
			this.resizeEl.setStyle('dispaly','block');
			this.collapsedEl.setStyle('display','none');
			this.toggleEl.set('class','m-split-mini-toggle-'+this.options.orientation);
			this.splitEl.addEvent('mousedown',this.bound.dragStart);
			this.splitEl.setStyle('cursor','');	
			this.resizeBox.collapsed=false;
		}else {	
			switch(this.options.orientation){
				case 'left':
					this.resizeEl.setStyle('width',this.options.collopsedSize);
					this.toggleEl.set('class','m-split-mini-toggle-right');
					break;
				case 'right':
					this.resizeEl.setStyle('width',this.options.collopsedSize);
					this.toggleEl.className='m-split-mini-toggle-left';
					break;
				case 'top':
					this.resizeEl.setStyle('height',this.options.collopsedSize);
					this.toggleEl.className='m-split-mini-toggle-bottom';
					break;

				case 'bottom':
					this.resizeEl.setStyle('height',this.options.collopsedSize);
					this.toggleEl.className='m-split-mini-toggle-top';
					break;
			}
			this.resizeEl.setStyle('dispaly','none');			
			this.collapsedEl.setStyle('display','block');
			this.splitEl.removeEvent('mousedown',this.bound.dragStart);	
			this.splitEl.setStyle('cursor','default');
			this.resizeBox.collapsed=true;
		}
		this.resizeBox.container.render();	
	}
});

Mui.Container=new Class({
	Extends: Mui.Box,
	options:{
		layout:'default',
		items:{}
	},	
	initialize: function(options){
		this.parent(options);
		this.el.addClass('m-container');
			
		var items=this.options.items;
		if (this.options.layout == 'border') {
			for(var key in items){
				var item=items[key];
				item.el.addClass('m-absolute');	
				item.container=this;
			};
			this.wrapperEl=new Element('div',{
				'id':this.el.get('id')+'-wrapper',
				'style':'position:relative;'
			}).inject(this.el);
			
			
			if(items.left)
				this.wrapperEl.grab(items.left.el);
				
			this.wrapperEl.grab(items.center.el);	
			
			if(items.right)
				this.wrapperEl.grab(items.right.el);
			
			if(items.left && items.left.options.split===true){
				new Mui.Splitbar(items.left,items.left.options);
			}
			if(items.right && items.right.options.split===true){
				new Mui.Splitbar(items.right,items.right.options);
			}
			if(items.top && items.top.options.split===true){
				new Mui.Splitbar(items.top,items.top.options);
			}
			if(items.bottom && items.bottom.options.split===true){
				new Mui.Splitbar(items.bottom,items.bottom.options);
			}						
		}
		/*
		for(var i=0,j=this.options.items.length;i<j;i++){
			var region=this.options.items[i].options.region|| this.options.items[i].id;
			this.children[region]=this.options.items[i];
			this.options.items[i].container=this;
			this.options.items[i].el.addClass('m-absolute');
		}*/
		
		if (this.el == document.body)
			$(window).addEvent('resize', this.render.bindWithEvent(this));		
	},
	render:function(box){		
		if (this.options.layout == 'border') {				
			var layout = Mui.LayoutFactory.getBorderLayout();
			layout.onLayout(this)
			$each(this.options.items, function(child, key){
				child.render();
			});
		}
	}	
});

Mui.Layout =new Class({
	onLayout:function(container){
		
	}	
});

Mui.BorderLayout=new Class({
	Extends: Mui.Layout,
	name:'border',
	onLayout:function(container){		
		var cs = container.el.getClientSize();
		var items=container.options.items;						
		var topBox=items['top'];
		var rightBox=items['right'];
		var leftBox=items['left'];
		var bottomBox=items['bottom'];
		var centerBox=items['center'];
		var wrapperEl=container.wrapperEl;
		
		if(!centerBox){
            throw 'No center pane defined in BorderLayout ' + container.id;
        }
		
		//top height,righ width,bottom height,left width
		var th=0,rw=0,bh=0,lw=0;

		if(topBox){
			th=topBox.el.getBoundHeight();
		}
		if(rightBox){
			rw=rightBox.el.getBoundWidth();
		}
		if(bottomBox){
			bh=bottomBox.el.getBoundHeight();
		}
		if(leftBox){
			lw=leftBox.el.getBoundWidth();
		}
		
		var wrapperheight=cs.height-th-bh;
		
	
		var cw=cs.width-lw-rw;
		var ch=cs.height-th-bh;
		
		wrapperEl.setRect(0,th,cs.width,ch);
		
		cw=cw>10?cw:10;
		ch=ch>10?ch:10;

		centerBox.el.setRect(lw,0,cw,ch);
				
		if(topBox){
			topBox.el.setRect(0,0,cs.width,th);
		}
		if(bottomBox){
			bottomBox.el.setRect(0,cs.height-bh,cs.width,bh);
		}		
		if(rightBox){
			rightBox.el.setRect(cw+lw,0,rw,ch);
		}
		if(leftBox){
			leftBox.el.setRect(0,0,lw,ch);
		}
	}				
});

Mui.LayoutFactory=new function(){
	this.getBorderLayout=function(){
		if(!this.border)
			this.border=new Mui.BorderLayout();
		return this.border;
	}
}