(function () {
    
    var tooltipEnablerWidget = new PHPS.Widgets.TooltipEnabler();
    
    // Register the tooltip enabler widget to the executor.
    PHPS.Executor.registerWidget('tooltip_enabler', tooltipEnablerWidget);
    
})();
