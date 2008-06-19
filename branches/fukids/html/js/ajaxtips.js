Ajaxtips= function(element,url){
		var myTips = new Tips('#'+element).addEvent('show', function(tip){
			if($(element).retrieve('ajaxtips') !=1){
				tip.load(url);
			}
			$(element).store('ajaxtips',1);
		});
}