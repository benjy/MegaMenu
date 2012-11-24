jQuery.noConflict();

(function($) {
    debug = false;

    /**
     * We overwrite the global mainNav function that is added by Magento for its own drop down menu.
     * @param menuId
     */
    mainNav = function(menuId) {
        // Magento gives us nav by default so make the nav an Id for jQuery.
        var menu = "#"+menuId;

        // Initialise the Mega Men.
        if(debug) {
            $(menu).megaMenu('debug', 2);
        }
        else {
            $(menu).megaMenu();
        }

    }

    /* Mega Menu Methods. */
    var hideTimer = null,
        showTimer = null,
        menuEnabled = false;
    methods = {

        // Initialise the mega menu
        init: function() {

            // We have the menu disabled when the page loads to prevent the drop down appearing
            // on a new page load when they've clicked a parent item and then not moved their mouse.
            $("body").mousemove(function() {
                menuEnabled = true;

                // Unbind for performance
                $("body").unbind("mousemove");
            });

            return this.each(function() {

                // This is our top level ul and we look for the anchors to bind
                // our show and hide events.
                $(this).find("li.level0 > a").hover(
                    function() {
                        methods.show($(this));
                    },
                    function() {
                        methods.hide($(this));
                    }
                );

                // We don't want the menu to hide when we're hovering over the drop down so
                // we clear the timeout on mouseover and trigger the hide on mouseout.
                $(this).find(".menu-columns").hover(
                    function() {
                        clearTimeout(hideTimer);
                    },
                    function() {
                        methods.hide($(this).prev("a"));
                    }
                );
            });
        },

        show: function(anchor) {

            if(menuEnabled) {
                // Clear the show timer. This stops the menu flickering when you hover over one
                // menu item and then straight onto the next without letting the previous menu appear.
                clearTimeout(showTimer);

                showTimer = setTimeout(function() {
                    // Select the menu columns to show.
                    var menuColumns = anchor.next(".menu-columns");

                    // Clear the timeout in case a hide had already been activated.
                    clearTimeout(hideTimer);

                    // Make sure the menu is not already been displayed
                    if(!menuColumns.hasClass('active')) {

                        // Hide any other menu items that may be displayed.
                        $(".menu-columns").slideUp();
                        $(".menu-columns").removeClass('active');

                        // Show our new menu
                        menuColumns.slideDown();
                        menuColumns.addClass('active');
                    }
                },300);
            }
        },

        hide: function(anchor) {

            // Clear the show timer in case they hover off before the item was ever shown.
            clearTimeout(showTimer);

            hideTimer = setTimeout(function() {
                var menuColumns = anchor.next(".menu-columns");

                // Hide this menu item.
                menuColumns.slideUp();

                // remove the active class from the menu so it gets re-shown.
                menuColumns.removeClass('active');
            }, 400);
        },

        // Helps for styling.
        debug : function(index) {
            menuEnabled = true;
            methods.show($(this).find("a").eq(index));
        }
    };

    // Mega menu jQuery plugin.
    $.fn.megaMenu = function(method) {

        // Check if the method exists on the plugin.
        if(methods[method]) {

            // Remove argument 1 which was the method name.
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        }
        else if(typeof method === 'object' || !method) {

            //If no method was supplied then we initalise the menu.
            return methods.init.apply(this, arguments);
        }
        else {

            // undefined method so set the error
            $.error('Undefined method ' + method + ' in Mega Menu.');
        }

    }

}(jQuery));
