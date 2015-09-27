(function () {
    
    /**
     * Widget which has the role of continously updating the states of a set of registered projects,
     * in a time interval loop. The states refer to a project being 'empty' or 'analyzed' and they
     * are marked through CSS classes on the representing HTML elements. The information for state
     * updating is received from the server. For having its state updated, the HTML element
     * representing the project should have the value of the project id set on the
     * data-project-watcher-widget-id attribute.
     */
    var ProjectWatcher;
    
    /**
     * The class constructor.
     */
    ProjectWatcher = function () {
        /**
         * The url of the data endpoint.
         * 
         * @var {string}
         */
        this.url = null;
    };
    
    /**
     * The time interval to analyze the subscribed projects.
     */
    ProjectWatcher.WATCH_TIMEOUT = 5000;
    
    /**
     * Updates the subscribed projects states by reading the data received from the server.
     * 
     * @param {Object} data
     */
    ProjectWatcher.prototype.update = function (data) {
        // Loop through the subscribed projects.
        $('[data-project-watcher-widget-id]').each(function () {
            var $this = $(this), projectId = parseInt($this.attr('data-project-watcher-widget-id'));
            
            if (typeof data.projects[projectId] === 'undefined') {
                return;
            }
            
            // If we received data for the current project in loop, update its state.            
            if (data.projects[projectId].isAnalyzed) {
                $this.addClass('analyzing');
            } else {
                $this.removeClass('analyzing');
            }
            
            if (data.projects[projectId].isEmpty) {
                $this.addClass('empty');
            } else {
                $this.removeClass('empty');
            }
        });
    };
    
    /**
     * Retrieves the necessary information from the data endpoint and updates the projects.
     */
    ProjectWatcher.prototype.watch = function () {
        var self = this;
        
        $.ajax(this.url)
            .success(function (data) {
                self.update(data);
            });
    };
    
    /**
     * Runs the logic of the widget.
     * 
     * @param {Object} data
     * A configuration object with the following properties:
     * - url*: the server endpoint which offers informations about the projects states;
     * 
     * The key marked with * are mandatory.
     * 
     * @throws {Error}
     * If the configuration object is invalid.
     */
    ProjectWatcher.prototype.run = function (data) {
        var self = this;
        
        // Validate the configuration object.
        if (typeof data !== 'object' || data === null) {
            throw new Error('In order to run the widget, a valid configuration object must be passed!');
        }
        
        if (typeof data.url !== 'string') {
            throw new Error('The "url" key of the configuration object must be a valid URL represented as a string!');
        }
        
        this.url = data.url;
        
        // Register the project watching operation.
        setInterval(function () {
            self.watch();
        }, this.constructor.WATCH_TIMEOUT);
    };
    
    // Register the widget to the global namespace.
    PHPS.Widgets.ProjectWatcher = ProjectWatcher;
    
})();
