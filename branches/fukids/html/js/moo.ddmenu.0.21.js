/*
Script: moo.ddmenu.0.21.js
 ddmenu is a simple MooTools-based script to create you're own context menus
 
License:
 MIT-style license.

Author:
janlee <email@webhike.de>
 
 <http://webhike.de/scripts/dd/ddmenu.html>
 <http://webhike.de/moo>

Use enableThisItemsOnly and enableItems to enable or disable menu items by they're ids

    enableThisItemsOnly ([item1,item2], true)   -> disable all other
    enableThisItemsOnly (item)                    	-> same
    enableThisItemsOnly ([item1,item2], false)  -> enable all other

    enableItems ([item1,item2])           		-> enable this items
    enableItems (item1)                   		-> same 
    enableItems ([item1,item2], false)    		-> disable this thems
    enableItems ()                        		-> enable all items    
    enableItems (false)                   		-> disable all      
*/

var DDMenu = new Class ({

    Implements: [Events, Options],
    
    options: {
        onOpen: $empty,
        onClose: $empty,
        onItemSelect: $empty, 
        observe_disabled_items: false,                                          //call onItemSelect() on disabled items?
        rightclick_to_open: true,                                               //open menu on rightclick (if browser supports contextmenu-events)
        crtl_switch: true,                                                      //allow to switch between default menu and dd
        shift_with_contentmenu: true, //not ie                                  						//show default & dd menu together
        fade_in: true,
        cursorx: 2,                                                             //distance to cursors-coords
        cursory: 1,
        opacity: 0.95                                                           //menu transparency
    },
    
    initialize: function (menu, bindon, options) {
    
        this.setOptions (options);
        this.eMenu = $(menu);
        this.eBindon = $(bindon);
        
        this.eMenu.setStyles ({ 
            position: 'absolute', 
            'z-index': 9999, 
            display: 'none'
        });

        this.open = this.open.bindWithEvent(this);
        this.close = this.close.bind(this);
        this.preOpenEvent = this.preOpenEvent.bind(this)
        this.menuEvent = this.menuEvent.bindWithEvent(this);
        
        this.clickedElement = $empty;
        
        this.eBindon.addEvents ({
            'mousedown': function () { console.log('clicked: '+bindon); this.eBindon.addEvent ('contextmenu', $break) }.bind(this),
            'mouseup': this.preOpenEvent
        });
        
        //this.eMenu.getElements('li.item a').addEvent('click', $break); //safari bug :(
        $$('#'+menu+' li a').addEvent('click', $break);
    },

    //while hidden

    preOpenEvent: function (event) {  
	console.log('called: '+this.eBindon.id);
        if (event.shift) {
            this.eBindon.removeEvent ('contextmenu', $break);
        }
        else if (this.options.crtl_switch && event.rightClick && event.control) { //open browser default contextmenu
            this.eBindon.removeEvent ('contextmenu', $break);
            return true; 
        }
        
        event.preventDefault();
        if (this.eMenu.style.display == 'block') this.close(event);
        
        this.clickedElement = $(event.target);

        if (event.rightClick) {
            if (this.options.crtl_switch && event.control) return true;
            else if (this.options.rightclick_to_open) this.open(event);
        }
        else 
        { 
            if (event.control) this.open(event);
            else return true;
        }

        return false;        
    },
    
    
    open: function (event) { 


        this.eMenu.setStyles ({opacity: 0, display: 'block', 'z-index':99999, top:event.page.y + this.options.cursory, left:event.page.x + this.options.cursorx});
        var coords = {};
     //   this.eMenu.getPosition().y-$$('body').getScroll().y+this.eMenu.getSize().y < $$('body').getSize().y ?
       //     coords.y = event.page.y + this.options.cursory :
         //   coords.y = event.page.y - this.eMenu.getSize().y + this.options.cursory;
coords.y = event.page.y + this.options.cursory;
        if (coords.y<$$('body').getScroll().y+1) coords.y = $$('body').getScroll().y+1;
        
        //this.eMenu.getPosition().x-$$('body').getScroll().x+this.eMenu.getSize().x < $$('body').getSize().x ?
        //    coords.x = event.page.x + this.options.cursorx :
        //    coords.x = event.page.x - this.eMenu.getSize().x + this.options.cursorx;
coords.x = event.page.x + this.options.cursorx;
        if (coords.x<$$('body').getScroll().x+1) coords.x = $$('body').getScroll().x+1;
        
        if (event.shift) coords.x = event.page.x - this.eMenu.getSize().x - this.options.cursorx;

        this.eMenu.setStyles({ top: coords.y, left: coords.x });
        
        if (this.options.fade_in) {
            var op = this.options.opacity;
            var fadein = new Fx.Morph (this.eMenu, {duration:200}).start({ 'opacity': [.32, op] });
        }
        else this.eMenu.style.opacity = this.options.opacity;
        


        window.addEvent ('blur', function () { if (!Browser.Engine.trident) this.close() }.bind(this)); //ie throws currious blur events
        document.addEvent ('mousedown', this.menuEvent);
        
        this.eMenu.addEvents({
            'contextmenu': function () {return false},
            'mouseup': this.menuEvent
        });
        

        this.fireEvent('onOpen', event);
    },
    
    
    //while opened
    
    menuEvent: function (event) { 
        
        event.preventDefault();
        
        var item = $(event.target);

        if (item == this.eMenu || item == this.eMenu.getElement('ul')) return false; 
        
        item = this.ascendTo(item, ['item','sepline','title']); 
        
        if (item === false) {
            this.close(event); //outer event
        }
        else if (item.hasClass('item') && event.type == 'mouseup') {
            if (!(item.getElement('a').hasClass('disabled') && !this.options.observe_disabled_items)) {
                this.action(item); 
                this.close(event); 
            }
        }
    },    
    
    
    close: function (event) {
            
        this.eMenu.style.display = 'none';         
          
        document.removeEvent ('mousedown', this.menuEvent);
        window.removeEvent ('blur', function () {if (!Browser.Engine.trident) this.close()}.bind(this));
        this.eMenu.removeEvents();
                     
        this.fireEvent('onClose', event);    
    },
    
    
    action: function (item) {
        
        //this.clickedElement.focus();
        this.fireEvent('onItemSelect', [item.get('id'), this.clickedElement, this.eBindon]);
        return;
    },
    
    
    ascendTo: function (el, peakto) {
    
        if (el == window) return false;
        
        var ascel = el;
                
        while (ascel.get('tag') != 'html') { 
            
            for (var i=0; i<peakto.length; i++) {
                if (ascel.hasClass(peakto[i])) return ascel;
            }
            ascel = ascel.getParent(); 
        }
        
        return false;        
    },
    
      
    

    
    enableThisItemsOnly: function (items, enable) {
        
        if (!$chk(enable) && enable!=false) enable = true;
        if ($type(items) == 'string') items = [items];
        if (!items.length) return;
        
        enable == true ?
            this.eMenu.getElements ('li.item a').addClass('disabled') :
            this.eMenu.getElements ('li.item a').removeClass('disabled');                

        items.each (function (item) {
            enable == true ? 
                this.eMenu.getElement('li#'+item+' a').removeClass('disabled') :
                this.eMenu.getElement('li#'+item+' a').addClass('disabled');
        }.bind(this));            
    },
    
    
    enableItems: function (items, enable) {
    
        if (!$chk(items) && items!=false) items = true;
        if ($type(items) == 'boolean') {
            items == true ? 
                this.eMenu.getElements ('li.item a').removeClass('disabled') : 
                this.eMenu.getElements ('li.item a').addClass('disabled');
            return;
        }     
    
        if (!$chk(enable) && enable!=false) enable = true;
        if ($type(items) == 'string') items = [items];

        items.each (function (item) {
            enable == true ? 
                this.eMenu.getElement('li#'+item+' a').removeClass('disabled') :
                this.eMenu.getElement('li#'+item+' a').addClass('disabled');
        }.bind(this));
    }
    
});


$break = function () {return false;}


