(function () {
    /**
     * The Graph widget. It is used to render graph structures using sigma.js.
     */
    var Graph;
    
    /**
     * The widget constructor.
     */
    Graph = function () {};
    
    /**
     * Renders the legend associated to the graph.
     * 
     * @param {Object} data
     */
    Graph.prototype.renderLegend = function (data) {
        var container = $(data.container), legend = data.legend, toggleButton, items, i, l, element, color, label;
        
        // Add a button which will toggle the legend's visibility. By default, the legend should be hidden.
        toggleButton = $('<a></a>').addClass('show-hide-legend')
            .text('Show legend')
            .on('click', function (event) {
                var $this = $(this);
                        
                event.preventDefault();
        
                container.toggleClass('expanded');
                if ($this.text() === 'Show legend') {
                    $this.text('Hide legend');
                } else {
                    $this.text('Show legend');
                }
            });
        container.append(toggleButton);
        
        // Render the actual items of the widget.
        items = $('<ul></ul>');
        for (i = 0, l = legend.length; i < l; i++) {
            color = $('<div></div>').addClass('element')
                .css({ 'background-color' : legend[i].color });
            label = $('<span></span>').addClass('translation')
                .text(legend[i].label);
            
            element = $('<li></li>').append(color)
                .append(label);
            items.append(element);
        }
        container.append(items);
    };
    
    /**
     * Runs the logic of the widget.
     * 
     * @param {Object} data
     * The configuration object of the widget. Should have two properties:
     * - data*: the data which the graph will represent. The format of this object should conform to the
     *  structure demnanded by the sigma class constructor;
     * - legend: the configuration object for the graph legend. Should have two properties:
     *      - container;
     *      - legend: the data of the legend. It should be an array of elements, where every element has
     *        a color and a label property, as every figure in the graph should be associated to a
     *        description;
     *
     * The elements marked with * are mandatory.
     * 
     * @see http://sigmajs.org/
     * 
     * @throws {Error}
     */
    Graph.prototype.run = function (data) {
        var graph;
        
        // Validate the consistency of the configuration object.
        if (typeof data !== 'object' || data === null) {
            throw new Error('In order to display the graph, we need its configuation data!');
        }
        
        // Validate the presence of the required parameters.
        if (typeof data.data === 'undefined') {
            throw new Error('The "data" key of the configuration object is mandatory!');
        }
        
        // Render the graph.
        graph = new sigma(data.data);
        
        // Render the legend.
        if (data.legend) {
            this.renderLegend(data.legend);
        }
    };
    
    // Register the widget in the application namespace.
    PHPS.Widgets.Graph = Graph;
})();
