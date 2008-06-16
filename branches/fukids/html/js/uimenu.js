/*
    Script: UI.Menu.js

    Class: UI.Menu
        Creates a menu

    Syntax:
        >var myMenu = new UI.Menu( [options] );

    Arguments:
        options - (object, optional) An object with options for the menu. See below.

        options (continued):
            event       - (string: defaults to 'leftClick') The mouse event : leftClick or rightClick or mouseOver
            container   - (string: defaults to false (equal to document.body)) The container for the menu element
            clsPrefix   - (string: defaults to 'ui-') The class prefix for CSS modifications
            position    - (string or array: defaults to 'mouse') The position of the menu
            blankImg    - (string) Path to a blank img (like s.gif)
            autoHideDelay - (number: defaults to 2000) Time in ms before the menu hides when the mouse leaves the menu.
            subMenu     - (boolean: defaults to false) True if the menu is a submenu
            parentMenu  - (string: defaults to false) ID of the parent menu.
            id          - (string: defaults to Native.UID++) The id of the menu element.

    Returns:
        (class)  A new UI.Menu class instance.

    Example:
        [javascript]
            var myMenu = new UI.Menu();
        [/javascript]
*/
	var UI={};
UI.Menu = new Class ( {

    Implements : [ Options ],

    options : {
            event           :   'rightClick'         // leftClick || rightClick || mouseOver
        ,   id              :   null
        ,   container       :   null
        ,   clsPrefix       :   'ui-'
        ,   position        :   'mouse'             // mouse || [ xPos( 'right' || 'left' ) , yPos( 'top' || 'bottom' ) ]
        ,   blankImg        :   'images/rightmenu/s.gif'
        ,   subMenu         :   false
        ,   parentMenu      :   false
    },

    initialize : function ( trigger, options )
    {
        this.setOptions( options );
        this.items      = [];
        this.separators = [];
        this.submenus   = [];
        this.trigger    = $( trigger );
		this.checklayer = this.checklayer.bindWithEvent(this);
        //this.container  = $pick( this.options.container, document.body );
			if(!$defined($(this.options.clsPrefix+'menucontainer'))){
				this.container	=new Element( 'div', { id : this.options.clsPrefix+'menucontainer'} ).setStyle('width','1px').inject( document.body , 'inside' );
			}else{
				this.container= $(this.options.clsPrefix+'menucontainer');
			}
        this.id         = $pick( this.options.id, 'menu-' + (Native.UID++) );
        this.menu       = new Element( 'ul', { id : this.id, 'class' : this.options.clsPrefix + 'menu' } ).inject( this.container, 'inside' );
        $(this.id).store( 'menu-obj', this );

        this.onClickHideMenu    = this.hideMenu.bind( this );
	
        if ( !this.options.subMenu ) {
            document.addEvent( 'mousedown', function( ev ) {
                ev  = new Event(ev);
                //if (ev.target.id != this.id && !ev.target.getParents().contains( this.menu ) ) this.hideMenu();
				if (ev.target.id != this.id) this.hideMenu();
            }.bind( this ) );
        };

        if ( this.options.subMenu ) {
            this.menu.addEvents( {   'mouseenter' : function() {
                                                        var parentMenuObj = $( this.options.parentMenu ).retrieve( 'menu-obj' );
                                                        if ( parentMenuObj.getItemStatus( this.trigger.get( 'id' ) ) ) {
                                                            this.trigger.addClass( 'over');
                                                            if ( parentMenuObj.timeOut ) $clear( parentMenuObj.timeOut );
                                                        };
                                                    }.bind(this)
                                } );
        };
		this.trigger.addEvent( 'mouseup', function(event) {
			this.trigger.addEvent ('contextmenu', $break);
		}.bind(this));

		document.addEvent ('contextmenu', $break);
        if ( [ 'rightClick', 'leftClick' ].contains( this.options.event ) ) {
            this.trigger.addEvent( 'mouseup', function(event) {
                if ( ( this.options.event == 'rightClick' && event.rightClick ) || ( this.options.event == 'leftClick' && !event.rightClick ) ) this.showMenu( event );
            }.bind( this ).bind( this.trigger ) );
			this.trigger.addEvent('mousedown',function(){
			});
        };

        if ( this.options.event == 'mouseOver' ) {
            this.trigger.addEvent( 'mouseover', function(event) {
                if ( !this.options.subMenu || ( this.options.subMenu && $( this.options.parentMenu ).retrieve( 'menu-obj' ).getItemStatus( this.trigger.get( 'id' ) ) ) )
                    this.showMenu.delay( 15, this, event );
            }.bind( this ) );
        };

        this.hideMenu();

    },

/*
    Method: addItem
        Adds an item to the menu

    Syntax:
        >myMenu.addItem( options );

    Arguments:
        options - (object, optional) An object with options for the item. See below.

        options (continued):
            id          - (string: defaults to Native.UID++) The id of the item element.
            label       - (string: defaults to false) The label of the item.
            icon        - (string: defaults to false) The path to the icon of the item.
            onclick     - (function: false) The function to execute when user clicks on the item
            styles      - (object: defaults to false) An object containing all the styles to apply.
            status      - (boolean: defaults to true) True if the item is enable.
            separator   - (boolean: defaults to false) If true, adds a separator to the menu and ignores others options.

    Examples:
        [javascript]
            var myMenu = new UI.Menu( document.body );
            myMenu.addItem( { id : 'button1', label : 'Demo Button', icon : 'images/demo.png', onclick : function() { alert('demo button clicked') } } );
        [/javascript]

        [javascript]
            var myMenu = new UI.Menu( document.body );
            myMenu.addItem( { separator : true } ); // Adds a separator
        [/javascript]
*/
    addItem: function( item )
    {
        item    = $merge( { separator   :   false
                        ,   status      :   true
                        ,   onclick     :   false
                        ,   id          :   null 
						,	disabled		:	false}, item );

        if ( item.separator) { this.addSeparator(); }
        else {

            item.id = $pick( item.id, 'ui-menu-item-' + Native.UID++ );

            this.items.push( item.id );
            this.items[ item.id ] = item;
            this.element = new Element( 'li', { id : item.id, 'class' : this.options.clsPrefix + 'menu-item' } );

            this.element.adopt( new Element( 'span', { 'class' : this.options.clsPrefix + 'menu-label' } ).adopt( new Element( 'img' ) ) );

            this.element.getElement( 'span' ).appendText( item.label );
            this.element.getElement( 'img' ).set( 'src', ( item.icon ? item.icon : this.options.blankImg ) );

			if(item.disabled){
			this.element.addClass('disabled');
			}else{
            this.element.addEvents( {   'mouseenter' : function( el, menu ) {
                                                                                if ( menu.getItemStatus( el.get( 'id' ) ) ) {
                                                                                    menu.hideSubMenus();
                                                                                    menu.unselectItems();
                                                                                    el.addClass( 'over');
                                                                                };
                                                                            }.pass( [this.element, this] )
                                    ,   'mouseleave' : function( el, menu ) { if ( menu.getItemStatus( el.get( 'id' ) ) ) { el.removeClass( 'over'); } }.pass( [this.element, this] )
                                    ,   'click'      : function( el, menu ) { if ( menu.getItemStatus( el.get( 'id' ) ) ) { menu.onClickHideMenu(); } }.pass( [this.element, this] )
                                } );
            this.element.addEvent( 'click', function( el, menu ) { if ( menu.getItemStatus( el.get( 'id' ) ) && menu.items[ el.get( 'id' ) ].onclick ) { menu.items[ el.get( 'id' ) ].onclick.bind(menu)(); } }.pass( [this.element, this] ) );
            }
			if ( item.styles ) this.element.setStyles( item.styles );

            this.element.inject( this.menu, 'inside' );
            this.items[ item.id ].element = this.element;

            if ( Browser.Engine.trident ) { // Resizes items and separators width if browser = IE
                var maxWidth = 0;
                this.items.forEach( function( item ) {
                    maxWidth = ( maxWidth < this.items[ item ].element.getSize().x ) ? this.items[ item ].element.getSize().x : maxWidth;
                }.bind(this) );
                this.items.forEach( function( item ) { this.items[ item ].element.setStyle( 'width', maxWidth ); }.bind(this) );
                this.separators.forEach( function( item ) { item.element.setStyle( 'width', maxWidth ); }.bind(this) );
            };

            delete this.element;
        };
    },

/*
    Method: updateItemOnclick
        Updates an item onclick function

    Syntax:
        >myMenu.updateItemOnclick( el, onclick );

    Arguments:
        el      - (string or number) ID or index of the item in the menu
        onclick - (function) Function to execute when the user clicks on the item

    Example:
        [javascript]
            var myMenu = new UI.Menu( document.body );
            myMenu.addItem( { id : 'button1', label : 'Demo Button', icon : 'images/demo.png', onclick : function() { alert('demo button clicked') } } );
            myMenu.updateItemOnclick( 'button1', function() { alert( 'new function' ); } );
        [/javascript]
*/
    updateItemOnclick: function( ref, onclick )
    {
        var id  = ( $type( ref ) == 'string' ) ? ref : this.menu.getElements( '.' + this.options.clsPrefix + 'menu-item' )[ref].get( 'id' );
        this.items[ id ].onclick = onclick;
    },

/*
    Method: addItems
        Adds items to the menu

    Syntax:
        >myMenu.addItems( [ options[, options, ...] ] );

    Arguments:
        options - (object, optional) An object with options for the item. See addItem options.

    Example:
        [javascript]
            var myMenu = new UI.Menu( document.body );
            myMenu.addItems(    [ { id : 'button1', label : 'Demo Button 1', icon : 'images/demo1.png', onclick : function() { alert('demo button 1 clicked') } }
                            ,   { id : 'button2', label : 'Demo Button 2', icon : 'images/demo2.png', onclick : function() { alert('demo button 2 clicked') } } ] );
        [/javascript]
*/
    addItems: function( items )
    {
        if ( items.length > 0 ) {
            $each( items, function( item ) { this.addItem( item ); }, this );
        };
    },

/*
    Method: getItem
        Returns an item element

    Syntax:
        >myMenu.getItem(0);

    Example:
        [javascript]
            var myMenu = new UI.Menu( document.body );
            myMenu.addItem( { id : 'button1', label : 'Demo Button 1', icon : 'images/demo1.png', onclick : function() { alert('demo button 1 clicked') } );
            myMenu.getItem(0);
        [/javascript]
*/
    getItem: function( index )
    {
        var id  = ( $type( ref ) == 'string' ) ? ref : this.menu.getElements( '.' + this.options.clsPrefix + 'menu-item' )[ref].get( 'id' );
        return $( id );
    },

/*
    Method: getItemStatus
        Returns item status

    Syntax:
        >myMenu.getItemStatus( ref );

    Arguments:
        ref     - (string or number) ID or index of the item in the menu

    Returns:
        (boolean)  True if the item is enable.

    Example:
        [javascript]
            var myMenu = new UI.Menu( document.body );
            myMenu.addItems(    [ { id : 'button1', label : 'Demo Button 1', icon : 'images/demo1.png', onclick : function() { alert('demo button 1 clicked') } }
                            ,   { id : 'button2', label : 'Demo Button 2', icon : 'images/demo2.png', onclick : function() { alert('demo button 2 clicked') } } ] );
            alert( myMenu.getItemStatus( 'button2' ) ); // Returns true
        [/javascript]
*/
    getItemStatus: function( ref ) {
        var id  = ( $type( ref ) == 'string' ) ? ref : this.menu.getElements( '.' + this.options.clsPrefix + 'menu-item' )[ref].get( 'id' );
        return this.items[ id ].status;
    },

/*
    Method: removeItem
        Removes an item from the menu

    Syntax:
        >myMenu.removeItem( ref );

    Arguments:
        ref     - (string or number) ID or index of the item in the menu

    Example:
        [javascript]
            var myMenu = new UI.Menu( document.body );
            myMenu.addItem( { id : 'button1', label : 'Demo Button', icon : 'images/demo.png', onclick : function() { alert('demo button clicked') } } );
            myMenu.removeItem( 'button1' ); // or myMenu.removeItem( 0 );
        [/javascript]
*/
    removeItem: function( ref )
    {
        var id  = ( $type( ref ) == 'string' ) ? ref : this.menu.getElements( '.' + this.options.clsPrefix + 'menu-item' )[ref].get( 'id' );
        this.items[ id ].element.destroy();
        this.items.remove( id );
    },

/*
    Method: disableItem
        Disables an item

    Syntax:
        >myMenu.disableItem( ref );

    Arguments:
        ref     - (string or number) ID or index of the item in the menu

    Example:
        [javascript]
            var myMenu = new UI.Menu( document.body );
            myMenu.addItem( { id : 'button1', label : 'Demo Button', icon : 'images/demo.png', onclick : function() { alert('demo button clicked') } } );
            myMenu.disableItem( 'button1' ); // or myMenu.disableItem( 0 );
        [/javascript]
*/
    disableItem: function( ref )
    {
        var id  = ( $type( ref ) == 'string' ) ? ref : this.menu.getElements( '.' + this.options.clsPrefix + 'menu-item' )[ref].get( 'id' );

        if ( this.getItemStatus( id ) ) {
            this.items[ id ].element.addClass( 'disable' ).set( 'opacity', 0.4 );
            this.items[ id ].status = false;
        };
    },

/*
    Method: enableItem
        Enables an item

    Syntax:
        >myMenu.enableItem( ref );

    Arguments:
        ref     - (string or number) ID or index of the item in the menu

    Example:
        [javascript]
            var myMenu = new UI.Menu( document.body );
            myMenu.addItem( { id : 'button1', label : 'Demo Button', icon : 'images/demo.png', onclick : function() { alert('demo button clicked') } } );
            myMenu.enableItem( 'button1' ); // or myMenu.enableItem( 0 );
        [/javascript]
*/
    enableItem: function( ref )
    {
        var id  = ( $type( ref ) == 'string' ) ? ref : this.menu.getElements( '.' + this.options.clsPrefix + 'menu-item' )[ref].get( 'id' );
        if ( !this.getItemStatus( id ) ) {
            this.items[ id ].element.removeClass( 'disable' ).set( 'opacity', 1 );
            this.items[ id ].status = true;
        };
    },

/*
    Method: toggleItem
        Toggle an item status (enable/disable)

    Syntax:
        >myMenu.toggleItem( ref );

    Arguments:
        ref     - (string or number) ID or index of the item in the menu

    Example:
        [javascript]
            var myMenu = new UI.Menu( document.body );
            myMenu.addItem( { id : 'button1', label : 'Demo Button', icon : 'images/demo.png', onclick : function() { alert('demo button clicked') } } );
            myMenu.toggleItem( 'button1' ); // or myMenu.toggleItem( 0 );
        [/javascript]
*/
    toggleItem: function( ref )
    {
        var id  = ( $type( ref ) == 'string' ) ? ref : this.menu.getElements( '.' + this.options.clsPrefix + 'menu-item' )[ref].get( 'id' );
        if ( this.items[ id ].element.hasClass( 'disable' ) ) this.enableItem( id ); else this.disableItem( id );
    },

/*
    Method: addSeparator
        Adds a separator line to the menu

    Syntax:
        >myMenu.addSeparator();

    Example:
        [javascript]
            var myMenu = new UI.Menu( document.body );
            myMenu.addSeparator();
        [/javascript]
*/
    addSeparator: function()
    {
        this.element    = new Element( 'li', { 'class' : this.options.clsPrefix + 'menu-sep' } );
        this.element.adopt( new Element( 'span' ) );
        this.element.inject( this.menu, 'inside' );

        var index = this.separators.length == 0 ? 0 : this.separators.length + 1;
        this.separators.push( this.separators.length );
        this.separators[ index ] = {};
        this.separators[ index ].element = this.element;

        delete this.element;
    },

/*
    Method: removeSeparator
        Removes a separator line to the menu

    Syntax:
        >myMenu.removeSeparator();

    Example:
        [javascript]
            var myMenu = new UI.Menu( document.body );
            myMenu.addSeparator();
            myMenu.removeSeparator();
        [/javascript]
*/
    removeSeparator: function( index )
    {
        this.separators[ index ].element.destroy();
        this.separators.remove( index );
    },

/*
    Method: getSeparator
        Returns a separator line to the menu

    Syntax:
        >myMenu.getSeparator();

    Example:
        [javascript]
            var myMenu = new UI.Menu( document.body );
            myMenu.addSeparator();
            myMenu.getSeparator(0);
        [/javascript]
*/
    getSeparator: function( index )
    {
        return this.menu.getElements( '.' + this.options.clsPrefix + 'menu-sep' )[index];
    },

/*
    Method: addSubMenu
        Adds a sub menu to an item

    Syntax:
        >myMenu.addSubMenu( ref[, options ] );

    Arguments:
        ref     - (string or number) ID or index of the item in the menu
        options - (object, optional) Options for the sub menu. See the class options.

    Returns:
        (class)  A new UI.Menu class instance.

    Example:
        [javascript]
            var myMenu = new UI.Menu( document.body );
            myMenu.addItems(    [ { id : 'button1', label : 'Demo Button 1', icon : 'images/demo1.png' }
                            ,   { id : 'button2', label : 'Demo Button 2', icon : 'images/demo2.png', onclick : function() { alert('demo button 2 clicked') } } ] );
            var mySubMenu   = myMenu.addSubMenu( 'button1' );
            mySubMenu.addItem( { id : 'button11', label : 'Demo Sub Button', icon : 'images/demo.png', onclick : function() { alert('sub menu button clicked') } } );
        [/javascript]
*/
    addSubMenu: function( ref, options )
    {
        var id  = ( $type( ref ) == 'string' ) ? ref : this.menu.getElements( '.' + this.options.clsPrefix + 'menu-item' )[ref].get( 'id' );
        this.items[ id ].element.getElement( 'span' ).addClass( 'arrow' );
        this.items[ id ].element.removeEvent( 'click', this.onClickHideMenu );

        this.submenus.push( id );
        this.submenus[ id ] = {};
        this.submenus[ id ].obj = new UI.Menu( this.items[ id ].element, $merge( { position : ['top', 'right'], subMenu : true, parentMenu : this.id, event : 'mouseOver' }, options ) );
        return this.submenus[ id ].obj;
    },
	checklayer: function(event){
		this.hideMenu();
	},
    showMenu: function ( event )
	{
        this.hideMenu();
        if ( $type( this.options.position ) == 'string' && this.options.position == 'mouse' ) {
            var styleX = window.getWidth() - event.client.x < this.menu.getSize().x ? { 'left' : event.client.x - this.menu.getSize().x } : { 'left' : event.client.x };
            var styleY = window.getHeight() - event.client.y < this.menu.getSize().y ? { 'top'  : event.client.y - this.menu.getSize().y } : { 'top' : event.client.y };
        } else {

            if ( this.options.position.contains( 'right' ) ) {
                var styleX = window.getWidth() - this.trigger.getSize().x < this.menu.getSize().x ? { 'left' : this.trigger.getCoordinates().left - this.menu.getSize().x } : { 'left' : this.trigger.getCoordinates().right };
            };

            if ( this.options.position.contains( 'left' ) ) {
                var styleX = window.getWidth() - this.trigger.getSize().x < this.menu.getSize().x ? { 'left' : this.trigger.getCoordinates().left - this.menu.getSize().x } : { 'left' : this.trigger.getCoordinates().left };
            };

            if ( this.options.position.contains( 'top' ) ) {
                var styleY = window.getHeight() - this.trigger.getSize().y < this.menu.getSize().y ? { 'top'  : this.trigger.getCoordinates().top - this.menu.getSize().y } : { 'top' : this.trigger.getCoordinates().top };
            };

            if ( this.options.position.contains( 'bottom' ) ) {
                var styleY = window.getHeight() - this.trigger.getSize().y < this.menu.getSize().y ? { 'top' : this.trigger.getCoordinates().top + this.menu.getSize().y } : { 'top'  : this.trigger.getCoordinates().bottom };
            };

        };
		
        this.menu.addClass( this.options.clsPrefix + 'menu-visible' ).setStyles( $merge( styleX, styleY )).addEvent('mouseup',this.checklayer);
		 document.addEvent ('mousedown', this.checklayer);
    },

    hideMenu: function ()
    {
        this.hideSubMenus();
        this.menu.removeClass( this.options.clsPrefix + 'menu-visible' ).setStyle( 'left', '-600px' );
    },

    hideSubMenus: function ()
    {
        this.submenus.forEach( function( submenu ) {
                                                        this.submenus[ submenu ].obj.hideMenu();
                                                        this.submenus[ submenu ].obj.unselectItems();
                                                    }.bind(this) );
    },

    unselectItems: function()
    {
        this.items.forEach( function( item ) { this.items[ item ].element.removeClass( 'over' ); }.bind(this) );
    }

} );
$break = function () {
return false;}