var MultipleSelect = new Class ({
	initialize: function () {
		var zIndex = 1000;
		$$('select.multipleSelect').forEach (
		function ( sel ) {
			var top= sel.getPosition().y + 'px';
			var left= sel.getPosition().x + 'px';
			var width= sel.getSize().x+'px';
			var height=sel.getSize().y+ 'px';
			var container = new Element ( 'div', {
				class: 'MScontainer',
				id: 'ms_' + sel.id,
				styles: {
					'width': width+'px',
					'height':height,
					'overflow':'auto'
				}
			}).cloneEvents(sel);
			var i = 0;
			var thislength =sel.options.length;
			for ( i = 0; i < thislength; i++){
				var option = sel.options[i];
				var item = new Element('div',{
					class: 'MSitem',
						id: 'ms_' + sel.id + '_' + i,
					events: {
						'click': function (aa) {
							sel.options[this.index].selected = $('ms_' + sel.id + '_' + this.index).hasClass ( 'MSselected' )?false:true;
							$('ms_' + sel.id + '_' + this.index).toggleClass('MSselected');
						}.bind(option),
						'mouseover':function(){
							this.addClass('MSover');
						},
						'mouseout':function(){
							this.removeClass('MSover');
						},
						'mousedown':function(){
							return false;
						}
					}
				} );
				if($defined($(sel.id+'_'+i))){
					item.set('html',$(sel.id+'_'+i).innerHTML).cloneEvents($(sel.id+'_'+i));
					$(sel.id+'_'+i).setStyle('display','none');
				}else{
					item.set('html',option.innerHTML);
				}
				item.injectInside(container);
			}
			container.setStyles ( {
				'top':top,
				'left': left,
				'width': width
			});
			container.inject(sel,'after');
			sel.setStyle('display','none').getProperty('multiple','multiple');
			sel.removeEvent();
		} );
	}
});
