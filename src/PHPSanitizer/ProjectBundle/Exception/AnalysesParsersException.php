<?php

namespace PHPSanitizer\ProjectBundle\Exception;

/**
 * Analyses parsers specific exception.
 */
class AnalysesParsersException extends \Exception
{
    /**
     * The code of the error thrown when passing an analysis with the regarded result empty to a parsing method.
     */
    const EMPTY_RESULT_ERROR_CODE = 1;
}
