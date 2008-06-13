/*
Script: 
	elementxy.js v.0.1
License:
	MIT-style license.

Copyright:
	copyright (c) 2007 jander, <jander.sy@163.com>
*/

/* this code is from google*/
Mui.getWindowSize=function(){
	var w=0,h=0;		 
	//IE
	if(!window.innerWidth){			
		if(!(document.documentElement.clientWidth == 0)){
			//strict mode
			w = document.documentElement.clientWidth;
			h = document.documentElement.clientHeight;
		}else{
			//quirks mode
			w = document.body.clientWidth;
			h = document.body.clientHeight;
		}
	}else{
		//w3c
		w = window.innerWidth;
		h = window.innerHeight;
	}
	return {'width':w,'height':h};
};

Element.implement({
	getBorderSizes:function(){
		return{
			top:this.getStyle('border-top-width').toInt(),
			right:this.getStyle('border-right-width').toInt(),
			bottom:this.getStyle('border-bottom-width').toInt(),
			left:this.getStyle('border-left-width').toInt()
		}
	},
	getMarginSizes:function(){
		return{
			top:this.getStyle('margin-top').toInt(),
			right:this.getStyle('margin-right').toInt(),
			bottom:this.getStyle('margin-bottom').toInt(),
			left:this.getStyle('margin-left').toInt()
		}
	},
	getPaddingSizes:function(){
		return{
			top:this.getStyle('padding-top').toInt(),
			right:this.getStyle('padding-right').toInt(),
			bottom:this.getStyle('padding-bottom').toInt(),
			left:this.getStyle('padding-left').toInt()
		}	
	},		
	getClientSize:function(){
		if(this == document.body) return Mui.getWindowSize();
		return {
			'width':this.getStyle('width').toInt(),
			'height':this.getStyle('height').toInt()
		};
	},
	
	getBoundWidth:function(){
		var cs=this.getClientSize();
		var ms=this.getMarginSizes();		
		var bs=this.getBorderSizes();
		var ps=this.getPaddingSizes();		
		return 	cs.width+ms.left+ms.right+bs.left+bs.right+ps.left+ps.right;	
	},
	
	getBoundHeight:function(){
		var cs=this.getClientSize();
		var ms=this.getMarginSizes();		
		var bs=this.getBorderSizes();
		var ps=this.getPaddingSizes();		
		return 	cs.height+ms.top+ms.bottom+bs.top+bs.bottom+ps.top+ps.bottom;	
	},

	getBoundSize:function(){
		var cs=this.getClientSize();
		var ms=this.getMarginSizes();		
		var bs=this.getBorderSizes();
		var ps=this.getPaddingSizes();
		return {
			'width':cs.width+ms.left+ms.right+bs.left+bs.right+ps.left+ps.right,	
			'height':cs.height+ms.top+ms.bottom+bs.top+bs.bottom+ps.top+ps.bottom
		};
	},
	
	setBoundWidth:function(boundWidth){
		var ms=this.getMarginSizes();
		var bs=this.getBorderSizes();
		var ps=this.getPaddingSizes();
		var w=Math.max(boundWidth-bs.left-bs.right-ms.left-ms.right-ps.left-ps.right,0);
		this.setStyle('width',w);
						
	},
	setBoundHeight:function(boundHeight){
		var ms=this.getMarginSizes();
		var bs=this.getBorderSizes();
		var ps=this.getPaddingSizes();
		var h=Math.max(boundHeight-bs.top-bs.bottom-ms.top-ms.bottom-ps.top-ps.bottom,0);
		this.setStyle('height',h);
						
	},
	setRect:function(x,y,boundWidth,boundHeight){
		var ms=this.getMarginSizes();		
		var bs=this.getBorderSizes();
		var ps=this.getPaddingSizes();
		var w=Math.max(boundWidth-bs.left-bs.right-ms.left-ms.right-ps.left-ps.right,0);
		var h=Math.max(boundHeight-bs.top-bs.bottom-ms.top-ms.bottom-ps.top-ps.bottom,0);
		this.setStyles({'left':x,'top':y,'width':w,'height':h});
	}
});