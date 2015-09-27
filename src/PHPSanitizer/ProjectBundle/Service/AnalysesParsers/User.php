<?php

namespace PHPSanitizer\ProjectBundle\Service\AnalysesParsers;

use PHPSanitizer\UserBundle\Entity\User as UserEntity;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsers\Project;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsers\Helper;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsersCacheManager;

/**
 * The user analyses parser. It is used to parse user-wise project analyses results into different formats in order
 * to pass them to the renderers.
 */
class User extends Project
{
    /**
     * The class constructor.
     * 
     * @param AnalysesParsersCacheManager $cacheManager
     * @param Helper $helper
     */
    public function __construct(AnalysesParsersCacheManager $cacheManager, Helper $helper)
    {
        parent::__construct($cacheManager, $helper);
    }
    
    /**
     * {@inheritdoc}
     */
    public function isResultEmpty($resultContainer)
    {
        // We consider that a user always has information attached.
        return false;
    }
    
    /**
     * Computes the list of project ids that will be included in a user's project summary.
     * 
     * @param UserEntity $user
     * 
     * @return array
     */
    private function computeList(UserEntity $user)
    {
        $list = array();
        foreach ($user->getProjects() as $project) {
            $list[] = $project->getId();
        }
        
        return $list;
    }
    
    /**
     * Computes the names of the projects included in a user's project summary.
     * 
     * @param UserEntity $user
     * 
     * @return array
     */
    private function computeNames(UserEntity $user)
    {
        $names = array();
        foreach ($user->getProjects() as $project) {
            $names[$project->getId()] = $project->getName();
        }
        
        return $names;
    }
    
    /**
     * Computes the set of empty projects from a user's project summary.
     * 
     * @param UserEntity $user
     * 
     * @return array
     */
    private function computeEmptyProjects(UserEntity $user)
    {
        $emptyProjects = array();
        foreach ($user->getProjects() as $project) {
            if (parent::isResultEmpty($project)) {
                $emptyProjects[] = $project->getId();
            }
        }
        
        return $emptyProjects;
    }
    
    /**
     * Computes the set of currently analyzed projects from a user's project summary.
     * 
     * @param UserEntity $user
     * 
     * @return array
     */
    private function computeAnalyzedProjects(UserEntity $user)
    {
        $analyzedProjects = array();
        foreach ($user->getProjects() as $project) {
            if ($project->getAnalyzing()) {
                $analyzedProjects[] = $project->getId();
            }
        }
        
        return $analyzedProjects;
    }
    
    /**
     * Aggregates the user summary values into a much more readable form for the renderers.
     * 
     * @param array $rawData
     * 
     * @return array
     */
    private function aggregateSummaryValues(array $rawData)
    {
        $data = array('projects' => array());
        foreach ($rawData['list'] as $projectId) {
            $data['projects'][$projectId] = array(
                'name' => $rawData['names'][$projectId],
                'isEmpty' => in_array($projectId, $rawData['emptyProjects']),
                'isAnalyzed' => in_array($projectId, $rawData['analyzedProjects']),
            );
        }
        
        return $data;
    }
    
    /**
     * Prepares a user's projects complete summary for rendering.
     * 
     * @param UserEntity $user
     * 
     * @return array
     * 
     * @throws AnalysesParsersException
     */
    public function forUserSummary(UserEntity $user)
    {
        // Validate the arguments and throw the corresponding errors if there are problems.
        if ($this->isResultEmpty($user)) {
            throw new AnalysesParsersException(
                'The given user\'s data doesn\'t match the integrity requirements!',
                AnalysesParsersException::EMPTY_RESULT_ERROR_CODE
            );
        }
        
        // Compute and aggregate the summary's values.
        $rawData = array(
            'list' => $this->computeList($user),
            'names' => $this->computeNames($user),
            'emptyProjects' => $this->computeEmptyProjects($user),
            'analyzedProjects' => $this->computeAnalyzedProjects($user),
        );
        $data = $this->aggregateSummaryValues($rawData);
        
        return $data;
    }
}
