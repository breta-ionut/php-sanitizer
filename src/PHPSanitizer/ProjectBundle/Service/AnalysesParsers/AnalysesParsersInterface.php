<?php

namespace PHPSanitizer\ProjectBundle\Service\AnalysesParsers;

/**
 * An interface which all analyses parsers should implement.
 */
interface AnalysesParsersInterface
{    
    /**
     * Determines if the regarded result of the given analysis is empty.
     * 
     * @param mixed $resultContainer
     * Can be an analysis or an analysis set.
     */
    public function isResultEmpty($resultContainer);
}
