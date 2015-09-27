(function () {
    
    /**
     * The Charter widget, used to render charts using the chart library.
     */
    var Charter;
    
    /**
     * The widget constructor.
     */
    Charter = function () {};
    
    /**
     * Renders the legend.
     * 
     * @param {string} container
     * @param {Object} legend
     * 
     * @private
     */
    Charter.prototype.renderLegend = function (container, legend)
    {
        var index, output, element, color, label;
        
        output = $('<ul></ul>');
        for (index in legend) {
            color = $('<div></div>').addClass('element')
                .css({ 'background-color' : legend[index].color });
            label = $('<span></span>').addClass('translation').
                text(legend[index].label);
            
            element = $('<li></li>').append(color)
                .append(label);
        
            output.append(element);
        }
        
        $(container).append(output);
    };
    
    /**
     * Runs the main logic of the widget.
     * 
     * @param {Object} data
     * The configuration object of the widget. It should have the following properties:
     * - container*: the HTML canvas element which will wrap the chart;
     * - data*: the data which the chart will represent. The format of this object should
     *   conform to the structure demanded by the Chart library renderers;
     * - type: the chart type (Bar, Line, etc.). By default, it will be 'Bar';
     * - options: other options to pass to the Chart library renderers;
     * - legendContainer: the HTML container of the chart legend;
     * - legend: the data to represent in the legend. Should be an array of objects which
     *   have the color and the label properties, as every element of the legend must
     *   associate a figure with a description;
     *   
     *   The properties marked with * are mandatory.
     *   
     * @throws {Error}
     * If the passed configuration object does not match the requirements.
     */
    Charter.prototype.run = function (data) {
        var container, type, chart;
        
        // Check the integrity of the configuration object.
        if (typeof data !== 'object' || data === null) {
            throw new Error('In order to display the chart, we need its configuation data!');
        }
        
        // Validate the presence of the required parameters.
        if (!data.container && !data.data) {
            throw new Error('The "container" and "data" options of the chart configuration are mandatory!');
        }
        
        // Render the chart.
        container = $(data.container).get(0).getContext('2d');
        
        type = 'Bar';
        if (data.type) {
            type = data.type;
        }
        
        chart = new Chart(container)[type](data.data, data.options);
        
        // Render the legend.
        if (data.legendContainer && data.legend) {
            this.renderLegend(data.legendContainer, data.legend);
        }
    };
    
    // Attach the widget to the global namespace.
    PHPS.Widgets.Charter = Charter;

})();
