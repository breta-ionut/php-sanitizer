(function () {

    /**
     * The application's executor.
     */
    PHPS.Executor = {
        /**
         * The registered configuration data for the widgets.
         * 
         * @var Object
         */
        data: {},
        
        /**
         * The registered widgets.
         * 
         * @var Object
         */
        widgets: {},
        
        /**
         * Method which registers the given data set.
         * 
         * @param {Object} data
         */
        addData: function (data) {
            this.data = $.extend(this.data, data);
        },
        
        /**
         * Registers the given widget under the given name. The name is used to identify the data corresponding
         * to the widget.
         * 
         * @param {string} name
         * @param {Object} widget
         * 
         * @throws {Error}
         * If the name or the widget don't have correct definitions.
         */
        registerWidget: function (name, widget) {
            if (typeof name !== 'string') {
                throw new Error('The name of the widget must be a valid string!');
            }
            
            if (typeof widget !== 'object' || widget === null || typeof widget.run !== 'function') {
                throw new Error('The widget must be a valid JS object with the "run" method implemented!');
            }
            
            this.widgets[name] = widget;
        },
        
        /**
         * Walks through the widgets and initiates them.
         */
        start: function () {
            var name;
            
            for (name in this.widgets) {
                this.widgets[name].run(this.data[name]);
            }
        }
    };

})();
