(function () {

    /**
     * Widget which enables the Bootstrap tooltip functionality in the pages where is added.
     */
    var TooltipEnabler;
    
    /**
     * The widget constructor.
     */
    TooltipEnabler = function () {};
    
    /**
     * Runs the main logic of the entrypoint.
     */
    TooltipEnabler.prototype.run = function () {
        $('[data-toggle="tooltip"]').tooltip();
    };
    
    // Register the widget to the global namespace.
    PHPS.Widgets.TooltipEnabler = TooltipEnabler;
    
})();
