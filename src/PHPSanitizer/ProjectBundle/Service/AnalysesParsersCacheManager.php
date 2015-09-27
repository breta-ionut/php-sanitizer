<?php

namespace PHPSanitizer\ProjectBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use PHPSanitizer\ProjectBundle\Entity\User;
use PHPSanitizer\ProjectBundle\Entity\Project;
use PHPSanitizer\ProjectBundle\Entity\Analysis;
use PHPSanitizer\ProjectBundle\Entity\AnalysesParsersCache;

/**
 * Service which acts as cache manager for the analyses parsers.
 */
class AnalysesParsersCacheManager
{
    /**
     * The Doctrine service.
     * 
     * @var Registry
     */
    protected $doctrine;
    
    /**
     * The class constructor.
     * 
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    /**
     * Builds the cache key associated to the given parser parameters.
     * 
     * @param string $function
     * @param string $parser
     * @param object target
     * The object whose data is being cached. Must be an analysis, project or user entity.
     * 
     * @return string
     * 
     * @throws \InvalidArgumentException
     */
    protected function buildCacheKey($function, $parser, $target)
    {
        $key = $function;
        $key .= ':' . $parser;
        
        // Analyze the target.
        if ($target instanceof Analysis) {
            $project = $target->getProject();
            
            $key .= ':' . $project->getUser()->getId();
            $key .= ':' . $project->getId();
            $key .= ':' . $target->getId();
        } elseif ($target instanceof Project) {
            $key .= ':' . $target->getUser()->getId();
            $key .= ':' . $target->getId() . ':';
        } elseif ($target instanceof User) {
            $key .= ':' . $target->getId() . '::';
        } else {
            throw new \InvalidArgumentException('The target must be an user, project or analysis entity!');
        }
        
        return md5($key);
    }
    
    /**
     * Fetches the data associated to the given parser parameters.
     * 
     * @param string $function
     * @param string $parser
     * @param object $target
     * 
     * @return mixed
     * Returns the associated cache data or null if it not exists.
     */
    public function get($function, $parser, $target)
    {
        $cacheKey = $this->buildCacheKey($function, $parser, $target);
        
        $cacheEntry = $this->doctrine
            ->getRepository('PHPSanitizer\ProjectBundle\Entity\AnalysesParsersCache')
            ->findOneBy(array('key' => $cacheKey));
        if ($cacheEntry === null) {
            return null;
        }
        
        return $cacheEntry->getData();
    }
    
    /**
     * Caches the data associated to the given parser parameters.
     * 
     * @param mixed $data
     * @param string $function
     * @param string $parser
     * @param object $target
     */
    public function set($data, $function, $parser, $target)
    {
        $entityManager = $this->doctrine->getManager();
        $cacheKey = $this->buildCacheKey($function, $parser, $target);
        
        // Check if the entry with the computed key already exists. If not, create it.
        $cacheEntry = $entityManager->getRepository('PHPSanitizer\ProjectBundle\Entity\AnalysesParsersCache')
            ->findOneBy(array('key' => $cacheKey));
        if ($cacheEntry === null) {
            $cacheEntry = new AnalysesParsersCache();
            $cacheEntry->setKey($cacheKey)
                ->setData($data);
            
            $entityManager->persist($cacheEntry);
        } else {
            $cacheEntry->setData($data);
        }
        
        $entityManager->flush();
    }
}
