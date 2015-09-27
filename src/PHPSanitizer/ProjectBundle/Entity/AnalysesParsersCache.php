<?php

namespace PHPSanitizer\ProjectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The data model for the analyses parsers cache entries.
 * 
 * @ORM\Table(name="analyses_parsers_cache")
 * @ORM\Entity
 */
class AnalysesParsersCache
{
    /**
     * The internal unique id of the analyses parsers cache entry.
     * 
     * @var int
     * 
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * The key of the cache entry.
     * 
     * @var string
     * 
     * @ORM\Column(type="string", name="cache_key", length=32, unique=true, nullable=false)
     */
    protected $key;
    
    /**
     * The stored data.
     * 
     * @var mixed
     * 
     * @ORM\Column(type="object", nullable=false)
     */
    protected $data;
    
    /**
     * Id getter.
     * 
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Cache key getter.
     * 
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }
    
    /**
     * Cache key setter.
     * 
     * @param string $key
     * 
     * @return AnalysesParsersCache
     */
    public function setKey($key)
    {
        $this->key = $key;
        
        return $this;
    }
    
    /**
     * Cache data getter.
     * 
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
    
    /**
     * Cache data setter.
     * 
     * @param mixed $data
     * 
     * @return AnalysesParsersCache
     */
    public function setData($data)
    {
        $this->data = $data;
        
        return $this;
    }   
}
