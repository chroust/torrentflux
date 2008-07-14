/*
   tablesoort.js
   Javascript - fast sortable tables
   Author dirk hoeschen (www.dirk-hoeschen.de)
   this script is public domain
*/

tableSoort = new Class({
	options:{
		table: 'sorttable', column : -1
	},
	initialize: function (table) {
		this.options.table = table;
		// prepare table header cells
		this.titles = $$('#'+table+' div div div');
		i=0;
  	this.titles.each(function(cell) {
		// drag resize
			var handle = new Element('span').addClass('resize').injectTop( cell );
			handle.set('HTML','&nbsp;');
			var resizer = new Drag(cell, {
				handle: handle,
				modifiers:{x: 'width'},
				onComplete: function(){
					if( cell.getStyle('width')< 10 ) {
						cell.setStyle('width', '10px');
					}
					listwidth.set(cell.id,cell.getStyle('width'));
				}.bind(this),
				onStart: function(ele){
					rowslength=$$('#'+this.options.table+' .tbody div').length;
				}.bind(this),
				onDrag: function(ele){
					this.reesize(ele.getProperty('column'),rowslength,cell.getStyle('width'));
				}.bind(this)
			});
			cell.setProperty('column',i);
			cell.addEvent('click',function(){ this.sort(cell); }.bind(this));
			if (cell.hasClass("asc")) this.options.column = i;
			i++;
		}.bind(this));
	},
	reesize: function(iii,rowslength,width){
		var rows = $$('#'+this.options.table+' .tbody div.rows');
		for (var i = 0; i < rowslength; i++) {
				if($defined(rows[i]))
					rows[i].getElementsByTagName("div")[iii].setStyle('width', width);
		}
	},
	sort: function(cell) {
		var column = cell.getProperty('column');
		var rows = $$('#'+this.options.table+' .tbody div.rows');		
			if(!rows[0].childNodes[column]){
				//table is empty
				return;
			}
	// Fill array with - values and IDs *fast*
	var values = new Array;
	var rowslength= rows.length;
		for (var i = 0; i < rowslength; i++) {
			values.push(rows[i].getElementsByTagName("div")[column].get('text')+"|"+i);
		}
	this.asc = (cell.hasClass('desc')) ?  true : false;
	// reverse only if already sorted
		if (column==this.options.column) { 
			values.reverse();
		} else {
			// use internal array sort -  special handling for numeric values
				switch (cell.getProperty('axis')) {   		 
					case 'number': values.sort(this.numsort); break;
					case 'filesize': values.sort(this.filesizesort); break;
					default:values.sort();
				}
		}
		if(cell.hasClass('desc')==false && cell.hasClass('asc')==false){
			values.reverse();
			this.asc=false;
		}
	this.titles.removeClass('desc').removeClass('asc');
	// rebuild table body into tbody element
	var tBody = $$('#'+this.options.table+' .tbody')[0];
		for (var i = 0; i < values.length; i++) {
			n = values[i].split("|").pop(); // get index;
			tBody.appendChild(rows[n])
		}
	/* IE doesnt allow replace table innerHTML... therefore we use a trick */
	$(this.options.table).replaceChild(tBody,$(this.options.table).lastChild);
	this.options.column = column;
	// Change table header class
		if(this.asc){ console.log('1');
			cell.removeClass('desc').addClass('asc');
		}else{ console.log('2');
			cell.removeClass('asc').addClass('desc');
		}
	},
	numsort: function(a,b) {
		a = parseInt(a.split('|').shift());
		b = parseInt(b.split('|').shift());
		return a-b;
	},
	filesizesort: function(a,b) {
		a = this.formatSize(a.split('|').shift());
		b = this.formatSize(b.split('|').shift());
		return a-b;
	}
	
});
	formatSize= function(textsize){
		textsize = textsize.split(' ');
			if(textsize[1]=='KB'){
				return parseInt(textsize[0]*1024);
			}else if(textsize[1]=='MB'){
				return parseInt(textsize[0]*1024*1024);
			}else if(textsize[1]=='GB'){
				return parseInt(textsize[0]*1024*1024*1024);
			}else if(textsize[1]=='TB'){
				return parseInt(textsize[0]*1024*1024*1024*1024);
			}
		return parseInt(textsize[0]);
	}
	
function HumanSize(size) {
   var pos=0;
   while (size>1024) {
      size/=1024;
      pos++;
   }
   var prefix=getSizePrefix(pos);
   var sizeName=prefix;
   var num=Math.pow(10,2);
   return (Math.round(size*num)/num)+' '+sizeName;
}
function getSizePrefix(pos) {
   switch (pos) {
      case  0: return "B";
      case  1: return "KB";
      case  2: return "MB";
      case  3: return "GB";
      case  4: return "TB";
   }
}

/** init on screen keyboard on load */
window.addEvent('domready', function() {
	$$('table.sortTable').each(function(sort) { new tableSoort(sort.id);});
});
