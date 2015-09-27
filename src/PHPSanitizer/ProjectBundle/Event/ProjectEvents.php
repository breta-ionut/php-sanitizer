<?php

namespace PHPSanitizer\ProjectBundle\Event;

/**
 * Configuration class for the projects related events.
 */
final class ProjectEvents
{
    /**
     * An event which states that the source code of the project was changed.
     */
    const SOURCE_CHANGE = 'php_sanitizer_project.project.source_change';
}
