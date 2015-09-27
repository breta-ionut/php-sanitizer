<?php

namespace PHPSanitizer\ProjectBundle\Service\AnalysesParsers;

use Doctrine\Common\Collections\Criteria;
use PHPSanitizer\ProjectBundle\Entity\Project as ProjectEntity;
use PHPSanitizer\ProjectBundle\Exception\AnalysesParsersException;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsers\AnalysesParsersInterface;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsers\ProjectPHPMD;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsers\ProjectDependency;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsers\Helper;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsers\Settings;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsersCacheManager;

/**
 * The project analyses parser. It is used to parse project-wise analyses results into different formats in order to
 * pass them to the renderers.
 */
class Project implements AnalysesParsersInterface
{
    /**
     * The name of the parser.
     */
    const NAME = 'project';
    
    /**
     * The cache manager service.
     * 
     * @var AnalysesParsersCacheManager
     */
    protected $cacheManager;
    
    /**
     * The parsers helper.
     * 
     * @var Helper
     */
    protected $helper;
    
    /**
     * The project PHPMD analyses parser.
     * 
     * @var ProjectPHPMD
     */
    protected $projectPHPMDParser;
    
    /**
     * The project dependency analyses parser.
     * 
     * @var ProjectDependency
     */
    protected $projectDependencyParser;
    
    /**
     * A list of callback definitions to be called when computing the summary. It was designed to be used
     * with the computeSummary method.
     * 
     * @var array
     */
    private $summaryCallbacks = array(
        'projectId' => array(array(null, 'computeProjectId'), null),
        'list' => array(array(null, 'computeList'), array()),
        'labels' => array(array(null, 'computeLabels'), array()),
        'new' => array(array(null, 'computeNew'), array()),
        'isAnalyzed' => array(array(null, 'computeIsAnalyzed'), false),
        'hasValidPHPMDAnalyses' => array(array(null, 'computeExistenceOfValidPHPMDAnalyses'), false),
        'hasValidDependencyAnalyses' => array(array(null, 'computeExistenceOfValidDependencyAnalyses'), false),
        'validPHPMDAnalyses' => array(array('projectPHPMDParser', 'forProjectValidation'), array()),
        'validDependencyAnalyses' => array(array('projectDependencyParser', 'forProjectValidation'), array()),
        'phpMDScores' => array(array('projectPHPMDParser', 'forProjectScores'), array()),
        'dependencyScores' => array(array('projectDependencyParser', 'forProjectScores'), array()),
    );
    
    /**
     * The class constructor.
     * 
     * @param AnalysesParsersCacheManager $cacheManager
     * @param Helper $helper
     */
    public function __construct(AnalysesParsersCacheManager $cacheManager, Helper $helper)
    {
        $this->cacheManager = $cacheManager;
        $this->helper = $helper;
    }
    
    /**
     * The PHPMD analyses parser setter.
     * 
     * @param ProjectPHPMD $projectPHPMDParser
     */
    public function setProjectPHPMDParser(ProjectPHPMD $projectPHPMDParser)
    {
        $this->projectPHPMDParser = $projectPHPMDParser;
    }
    
    /**
     * The dependency analyses parser setter.
     * 
     * @param ProjectDependency $projectDependencyParser
     */
    public function setProjectDependencyParser(ProjectDependency $projectDependencyParser)
    {
        $this->projectDependencyParser = $projectDependencyParser;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isResultEmpty($resultContainer)
    {
        return $resultContainer->getAnalyses()->isEmpty();
    }
    
    /**
     * Computes the id of the project whose analyses summary will be built.
     * 
     * @param ProjectEntity $project
     * 
     * @return int
     */
    private function computeProjectId(ProjectEntity $project)
    {
        return $project->getId();
    }
    
    /**
     * Computes the ids of the analyses that will be included in the summary.
     * 
     * @param ProjectEntity $project
     * 
     * @return array
     */
    private function computeList(ProjectEntity $project)
    {
        $list = array();
        
        // The newest analyses should be displayed first in the list.
        $orderByCondition = Criteria::create()->orderBy(array('created' => Criteria::DESC));
        $analyses = $project->getAnalyses()->matching($orderByCondition);
        foreach ($analyses as $analysis) {
            $list[] = $analysis->getId();
        }
        
        return $list;
    }
    
    /**
     * Computes a project analyses's labels.
     * 
     * @param ProjectEntity $project
     * 
     * @return array
     */
    private function computeLabels(ProjectEntity $project)
    {
        $labels = array();
        foreach ($project->getAnalyses() as $analysis) {
            $labels[$analysis->getId()] = $analysis->getCreated()->format(Settings::ANALYSIS_LABEL_DATE_FORMAT);
        }
        
        return $labels;
    }
    
    /**
     * Computes a project's analyses marked with the 'new' flag.
     * 
     * @param ProjectEntity $project
     * 
     * @return array
     */
    private function computeNew(ProjectEntity $project)
    {
        $new = array();
        foreach ($project->getAnalyses() as $analysis) {
            if ($analysis->getNew()) {
                $new[] = $analysis->getId();
            }
        }
        
        return $new;
    }
    
    /**
     * Computes the current status of the project.
     * 
     * @param ProjectEntity $project
     * 
     * @return boolean
     */
    private function computeIsAnalyzed(ProjectEntity $project)
    {
        return $project->getAnalyzing();
    }
    
    /**
     * Determines if there are valid PHPMD analyses attached to the given project.
     * 
     * @param ProjectEntity $project
     * 
     * @return boolean
     */
    private function computeExistenceOfValidPHPMDAnalyses(ProjectEntity $project)
    {
        return !$this->projectPHPMDParser->isResultEmpty($project);
    }
    
    /**
     * Determines if there are valid dependency analyses attached to the given project.
     * 
     * @param ProjectEntity $project
     * 
     * @return boolean
     */
    private function computeExistenceOfValidDependencyAnalyses(ProjectEntity $project)
    {
        return !$this->projectDependencyParser->isResultEmpty($project);
    }
    
    /**
     * Aggregates the summary computed values into a much more readable form.
     * 
     * @param array $rawData
     * 
     * @return array
     */
    private function aggregateSummaryValues(array $rawData)
    {
        $data = array();
        
        // The following list of properties will be copied directly.
        $directlyCopied = array('projectId', 'isAnalyzed', 'hasValidPHPMDAnalyses', 'hasValidDependencyAnalyses');
        foreach ($directlyCopied as $item) {
            $data[$item] = $rawData[$item];
        }
        
        foreach ($rawData['list'] as $analysisId) {
            $data['analyses'][$analysisId] = array(
                'label' => $rawData['labels'][$analysisId],
                'isNew' => in_array($analysisId, $rawData['new']),
                'isValidPHPMDAnalysis' => in_array($analysisId, $rawData['validPHPMDAnalyses']),
                'isValidDependencyAnalysis' => in_array($analysisId, $rawData['validDependencyAnalyses']),
                'phpMDScore' => isset($rawData['phpMDScores'][$analysisId]) ?
                    $rawData['phpMDScores'][$analysisId] : null,
                'dependencyScore' => isset($rawData['dependencyScores'][$analysisId]) ?
                    $rawData['dependencyScores'][$analysisId] : null,
            );
        }
        
        return $data;
    }
    
    /**
     * Computes the project analyses summary by calling the summary callback functions and aggregating the
     * resulting data.
     * 
     * @param ProjectEntity $project
     * 
     * @return array
     * 
     * @throws AnalysesParsersException
     */
    private function computeSummaryValues(ProjectEntity $project)
    {
        // Firstly, get the data from the defined callbacks.
        $rawData = array();
        foreach ($this->summaryCallbacks as $item => $definition) {
            // Unpack and read the callback definitions.
            list (list ($object, $method), $default) = $definition;

            $called = $this;
            if ($object !== null) {
                $called = $this->{$object};
            }
            
            // Try to fetch the results for the current summary item.
            try {
                $rawData[$item] = call_user_func(array($called, $method), $project);
            } catch (AnalysesParsersException $exception) {
                // There are some cases when we want to display to the user the summary, even if the project doesn't
                // have valid analyses attached.
                if ($exception->getCode() !== AnalysesParsersException::EMPTY_RESULT_ERROR_CODE) {
                    throw $exception;
                }
                
                // Assure a default value.
                $rawData[$item] = $default;
            }
        }
        
        // After fetching the results, we aggregate them into a more readable form.
        $data = $this->aggregateSummaryValues($rawData);
        
        return $data;
    }
    
    /**
     * Prepares a project's analyses complete summary for rendering.
     * 
     * @param ProjectEntity $project
     * 
     * @return array
     * 
     * @throws AnalysesParsersException
     */
    public function forProjectSummary(ProjectEntity $project)
    {
        // Validate the arguments and throw the corresponding errors if there are problems.
        if ($this->isResultEmpty($project)) {
            throw new AnalysesParsersException(
                'The given project doesn\'t have analyses at all!',
                AnalysesParsersException::EMPTY_RESULT_ERROR_CODE
            );
        }

        // Compute the summary's values.
        $data = $this->computeSummaryValues($project);
        
        return $data;
    }
    
    /**
     * Builds the necessary information for representing the current status of the project.
     * 
     * @param ProjectEntity $project
     * 
     * @return array
     * 
     * @throws AnalysesParsersException
     */
    public function forProjectNotifier(ProjectEntity $project)
    {
        // Validate the arguments and throw the corresponding errors if there are problems.
        if ($this->isResultEmpty($project)) {
            throw new AnalysesParsersException(
                'The given project doesn\'t have analyses at all!',
                AnalysesParsersException::EMPTY_RESULT_ERROR_CODE
            );
        }
        
        $data = array('analyzing' => $this->computeIsAnalyzed($project));
        
        return $data;
    }
}
