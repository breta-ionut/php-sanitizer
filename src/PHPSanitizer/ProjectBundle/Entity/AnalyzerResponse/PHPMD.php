<?php

namespace PHPSanitizer\ProjectBundle\Entity\AnalyzerResponse;

/**
 * Encapsulates the PHPMD analysis results.
 */
class PHPMD implements AnalyzerResponseInterface
{
    /**
     * The PHPMD reported errors.
     * 
     * @var array
     */
    protected $errors;
    
    /**
     * Determines if the response is empty.
     * 
     * @var boolean
     */
    protected $empty;
    
    /**
     * The class constructor.
     * 
     * @param array $errors
     */
    public function __construct(array $errors, $empty = false)
    {
        $this->errors = $errors;
        $this->empty = (bool) $empty;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return $this->empty;
    }
    
    /**
     * {@inheritdoc}
     */
    public static function createEmptyResponse()
    {
        return new static(array(), true);
    }
    
    /**
     * Errors getter.
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
