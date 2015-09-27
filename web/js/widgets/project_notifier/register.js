(function () {
    
    var projectNotifierWidget = new PHPS.Widgets.ProjectNotifier();
    
    // Register the project notifier widget to the executor.
    PHPS.Executor.registerWidget('project_notifier', projectNotifierWidget);
    
})();
