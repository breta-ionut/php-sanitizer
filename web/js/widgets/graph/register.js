(function () {
   
    var graphWidget = new PHPS.Widgets.Graph();
    
    // Register the graph widget to the executor.
    PHPS.Executor.registerWidget('graph', graphWidget);
    
})();