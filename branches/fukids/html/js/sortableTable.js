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
		this.titles = $$('#'+table+' div div');
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
		if(!rows[0].childNodes[column]) return; //table is empty
    // Fill array with - values and IDs *fast*
    var values = new Array;
    var rowslength= rows.length;
    for (var i = 0; i < rowslength; i++) {
       	values.push(rows[i].getElementsByTagName("div")[column].innerHTML+"|"+i);
    }
    this.asc = (cell.hasClass('desc')) ?  false : true;
	this.titles.removeClass('desc').removeClass('asc');
    // reverse only if already sorted
    if (column==this.options.column) { 
   	   	   values.reverse();
    } else {
      // use internal array sort -  special handling for numeric values
      switch (cell.getProperty('axis')) {
       	case 'string': values.sort(); break;       	     
       	case 'number': values.sort(this.numsort); break;       	     
      }
	   }
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
    	if(this.asc){
			cell.removeClass('asc').addClass('desc');
		}else{
			cell.removeClass('desc').addClass('asc');
		}
	},
	numsort: function(a,b) {
		a = parseInt(a.split("|").shift());
		b = parseInt(b.split("|").shift());
		return a-b;
	}
	
});

/** init on screen keyboard on load */
window.addEvent('domready', function() {
    $$('table.sortTable').each(function(sort) { new tableSoort(sort.id);});
});
