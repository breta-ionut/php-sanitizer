<?php

namespace PHPSanitizer\ProjectBundle\Service\AnalysesParsers;

/**
 * General parser settings.
 */
final class Settings
{
    /**
     * @defgroup rendering-opacities
     * @{
     * 
     * The opacities used in rendering a chart.
     */
    const CHART_FILL_OPACITY = 0.5;
    const CHART_STROKE_OPACITY = 0.8;
    const CHART_HIGHLIGHT_FILL_OPACITY = 0.75;
    const CHART_HIGHLIGHT_STROKE_OPACITY = 1;
    /**
     * @} endof "defgroup opacities"
     */
    
    /**
     * The time format used in history charts.
     */
    const HISTORY_CHART_TIME_FORMAT = 'Y-m-d H.i';
    
    /**
     * The prefix of a graph node's id.
     */
    const GRAPH_NODE_ID_PREFIX = 'node-';

    /**
     * The default 'size' property of a graph node.
     */
    const GRAPH_NODE_SIZE = 3;
    
    /**
     * The prefix of the graph edge's id.
     */
    const GRAPH_EDGE_ID_PREFIX = 'edge-';
    
    /**
     * The default value of the edge type property.
     */
    const GRAPH_EDGE_TYPE = 'arrow';
    
    /**
     * The analysis label date format.
     */
    const ANALYSIS_LABEL_DATE_FORMAT = 'Y-m-d H:i:s';
    
    /**
     * The default precision of random numbers generated through the generateRandomNumber method of the parsers
     * helper.
     */
    const RANDOM_NUMBER_DEFAULT_PRECISION = 6;
}
