<?php

namespace PHPSanitizer\ProjectBundle\Entity\AnalyzerResponse;

/**
 * Every analyzer response type must implement this interface.
 */
interface AnalyzerResponseInterface
{
    /**
     * Determines if the response is empty.
     * 
     * @return boolean
     */
    public function isEmpty();
    
    /**
     * Creates an empty response.
     * 
     * @return AnalyzerResponseInterface
     */
    public static function createEmptyResponse();
}
