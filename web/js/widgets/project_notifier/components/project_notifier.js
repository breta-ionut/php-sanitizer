(function () {
    
    /**
     * The project notifier widget. This widget is used to notify the client when the analysis of a currently
     * processed project has stopped. The notifying is being done through the removal of a CSS class from the
     * subscribed element. The subscribed element is a simple HTML element that will be used to display
     * information about the status of the project. To be recognized by our widget, it should have the
     * 'data-project-notifier-widget-subscriber' attribute attached.
     */
    var ProjectNotifier;
    
    /**
     * The widget constructor.
     */
    ProjectNotifier = function () {
        /**
         * The url of the endpoint.
         * 
         * @var {string}
         */
        this.url = null;
        /**
         * The element subscribed to updates.
         * 
         * @var {jQuery}
         */
        this.element = null;
        /**
         * The interval internal id.
         * 
         * @var {number}
         */
        this.intervalId = null;
    };
    
    /**
     * The update check time interval.
     */
    ProjectNotifier.NOTICE_TIMEOUT = 5000;
    
    /**
     * Performs the status updates.
     * 
     * @param {Object} data
     */
    ProjectNotifier.prototype.update = function (data) {
        if (data.analyzing) {
            this.element.addClass('analyzing');
        } else {
            this.element.removeClass('analyzing');
            // If the project isn't being analyzed anymore, stop checking for updates.
            clearTimeout(this.intervalId);
        }
    };
    
    /**
     * Checks for status updates by calling the endpoint.
     */
    ProjectNotifier.prototype.demandUpdate = function () {
        var self = this;
        
        $.ajax(this.url)
            .success(function (data) {
                self.update(data);
            });
    };
    
    /**
     * Runs the main logic of the widget.
     * 
     * @param {Object} data
     * The configuration object of the widget. It should have the following properties:
     * - url*: the URL of the endpoint which provides information about the current status of the project;
     * 
     * The elements marked with * are mandatory.
     * 
     * @throws {Error}
     * If the configuration object is invalid.
     */
    ProjectNotifier.prototype.run = function (data) {
        var self = this;
        
        // Validate the configuration object.
        if (typeof data !== 'object' || data === null) {
            throw new Error('In order to run the widget, a valid configuration object must be passed!');
        }
        
        if (typeof data.url !== 'string') {
            throw new Error('The "url" key of the configuration object must be a valid URL represented as a string!');
        }
        
        this.url = data.url;
        this.element = $('[data-project-notifier-widget-subscriber]');
        
        // If not a single element was registered to listen to project status changes, then do not perform
        // any action.
        if (this.element.length) {
            this.intervalId = setInterval(function () {
                self.demandUpdate();
            }, this.constructor.NOTICE_TIMEOUT);
        }
    };
    
    PHPS.Widgets.ProjectNotifier = ProjectNotifier;
    
})();
