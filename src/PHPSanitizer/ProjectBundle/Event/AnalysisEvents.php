<?php

namespace PHPSanitizer\ProjectBundle\Event;

/**
 * Configuration class for the analyses related events.
 */
final class AnalysisEvents
{    
    /**
     * An event which marks the end of an analysis process.
     */
    const END = 'php_sanitizer_project.analysis.end';
}
