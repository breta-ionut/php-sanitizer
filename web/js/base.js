/**
 * This file is used for defining the base structure of the application.
 * The app is basically a system of decoupled and independent widgets which perform various actions in a page.
 * A central service, the executor, aggregates them and when the page is ready, sequentially initiates every
 * widget. We also have a set of services, which are available everywhere and are used to perform global tasks.
 */
(function () {
    
    /**
     * Define the base namespaces of the application.
     */
    PHPS = {
        Services: {},
        Widgets: {},
        Executor: null
    };
    
})();
