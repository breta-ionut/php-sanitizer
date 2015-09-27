(function () {

    /**
     * Widget which performs iframe lazy-loading into modals.
     * A modal element which wants an iframe loaded into it on its show-up should have the following
     * attributes:
     * - data-iframe-widget-src: the src attribute of the iframe;
     * - data-iframe-widget-properties: a JSON string which specifies additional attributes for the iframe
     *   (e.g. width, height). This attribute is not mandatory;
     * Also, the modal element should have a child with the attribute 'data-iframe-target'. This element will
     * be the parent of newly added iframe.
     */
    var IFrame;
    
    /**
     * The widget constructor.
     */
    IFrame = function () {};
    
    /**
     * The name of the attribute which will be used to mark the appended iframes by our widget.
     */
    IFrame.IFRAME_CHILD_ATTRIBUTE = 'data-iframe-child';
    
    /**
     * Adds the iframe with the demanded properties to the given element.
     * 
     * @param {jQuery} element
     */
    IFrame.prototype.addIframe = function (element) {
        var iframeSrc = element.attr('data-iframe-widget-src'),
            iframeRawAttrs = element.attr('data-iframe-widget-properties'),
            iframeAttrs = {},
            iframe,
            attr,
            // The target is the child of the element where the iframe will be appended.
            target = element.find('[data-iframe-target]');
    
        // The element may demand additional properties for the iframe. 
        if (typeof iframeRawAttrs === 'string') {
            iframeAttrs = JSON.parse(iframeRawAttrs);
        }
        
        // Build the iframe element.
        iframe = $('<iframe></iframe');
        iframe.attr('src', iframeSrc);
        // Mark the iframe with a special attribute to identify it later for removal.
        iframe.attr(this.constructor.IFRAME_CHILD_ATTRIBUTE, '');
        for (attr in iframeAttrs) {
            iframe.attr(attr, iframeAttrs[attr]);
        }
        iframe.on('load', function () {
            element.removeClass('loading');
        });
        
        // Append the iframe.
        element.addClass('loading');
        target.append(iframe);
    };
    
    /**
     * Removes the added iframe from the modal element.
     * 
     * @param {jQuery} element
     */
    IFrame.prototype.removeIframe = function (element) {
        element.find('[' + this.constructor.IFRAME_CHILD_ATTRIBUTE + ']').remove();
    };
    
    /**
     * Given a modal element, listens to its show/hide events and attaches/detaches the associated iframe. 
     * 
     * @param {jQuery} element
     */
    IFrame.prototype.handleElement = function (element) {
        var self = this;
        
        element.on('shown.bs.modal', function () {
            self.addIframe($(this));
        });
        
        element.on('hidden.bs.modal', function () {
            self.removeIframe($(this));
        });
    };
    
    /**
     * Runs the logic of the widget.
     */
    IFrame.prototype.run = function () {
        var self = this;
        
        // Scan the DOM for modal elements that want to use our widget and handle them.
        $('.modal[data-iframe-widget-src]').each(function () {
            self.handleElement($(this));
        });
    };
    
    // Register the widget to the global namespace.
    PHPS.Widgets.IFrame = IFrame;
    
})();
