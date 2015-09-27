(function () {
   
    var projectWatcherWidget = new PHPS.Widgets.ProjectWatcher();
    
    // Register the project watcher widget to the executor.
    PHPS.Executor.registerWidget('project_watcher', projectWatcherWidget);
    
})();
