<?php

namespace PHPSanitizer\ProjectBundle\Service\AnalysesParsers;

use PHPSanitizer\ProjectBundle\Entity\Analysis;
use PHPSanitizer\ProjectBundle\Entity\Project as ProjectEntity;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsers\PHPMD;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsersCacheManager;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsers\Helper;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsers\Settings;
use PHPSanitizer\ProjectBundle\Exception\AnalysesParsersException;

/**
 * The PHPMD project parser. It is used to parse project-wise PHPMD analyses results into different formats
 * for passing them to the renderers.
 */
class ProjectPHPMD extends PHPMD
{
    /**
     * The name of the parser.
     */
    const NAME = 'project-phpmd';
    
    /**
     * The color used in rendering the project history chart.
     * 
     * @var array
     */
    private static $historyChartColor = array(23, 55, 100);
    
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
        // A project is considered to have analyses results if it has at least one analysis with complete
        // PHPMD results.
        $analyses = $resultContainer->getAnalyses();
        if ($analyses->isEmpty()) {
            return true;
        }
        
        foreach ($analyses as $analysis) {
            if (!parent::isResultEmpty($analysis)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Determines the PHPMD error score of an analysis. The score is the sum of all the error priorities reported
     * in the analysis. 
     * 
     * @param Analysis $analysis
     * 
     * @return int
     */
    protected function getAnalysisScore(Analysis $analysis)
    {
        $score = 0;
        foreach ($analysis->getPHPMDResults()->getErrors() as $file) {
            foreach ($file as $violation) {
                $score += (int) $violation['priority'];
            }
        }
        
        return $score;
    }
    
    /**
     * Generates the labels of the history chart.
     * 
     * @param ProjectEntity $project
     * 
     * @return array
     */
    private function computeHistoryChartLabels(ProjectEntity $project)
    {
        $labels = array();
        foreach ($project->getAnalyses() as $analysis) {
            // We include in the chart just the analyses with valid results.
            if (!parent::isResultEmpty($analysis)) {
                $labels[] = $analysis->getCreated()->format(Settings::HISTORY_CHART_TIME_FORMAT);
            }
        }
        
        return $labels;
    }
    
    /**
     * Computes the data used to render the history chart.
     * 
     * @param ProjectEntity $project
     * 
     * @return array
     */
    private function computeHistoryChartDatasets(ProjectEntity $project)
    {
        $data = array();
        foreach ($project->getAnalyses() as $analysis) {
            // We include in the chart only the analyses with valid results.
            if (!parent::isResultEmpty($analysis)) {
                $data[] = $this->getAnalysisScore($analysis);
            }
        }
        
        $dataset = array(
            'label' => 'Number of errors',
            'fillColor' => $this->helper->generateColorForCss(self::$historyChartColor, Settings::CHART_FILL_OPACITY),
            'strokeColor' => $this->helper->generateColorForCss(
                self::$historyChartColor,
                Settings::CHART_STROKE_OPACITY
            ),
            'highlightFill' => $this->helper->generateColorForCss(
                self::$historyChartColor,
                Settings::CHART_HIGHLIGHT_FILL_OPACITY
            ),
            'highlightStroke' => $this->helper->generateColorForCss(
                self::$historyChartColor,
                Settings::CHART_HIGHLIGHT_STROKE_OPACITY
            ),
            'data' => $data,
        );
        
        return array($dataset);
    }
    
    /**
     * Parses a project's PHPMD analyses results in a format needed by the history chart renderer. 
     * 
     * @param ProjectEntity $project
     * 
     * @return array
     * 
     * @throws AnalysesParsersException
     */
    public function forProjectHistoryChart(ProjectEntity $project)
    {
        // Validate the arguments and throw the corresponding errors if there are problems.
        if ($this->isResultEmpty($project)) {
            throw new AnalysesParsersException(
                'Project which has no analyses or only analyses with empty PHPMD results provided!',
                AnalysesParsersException::EMPTY_RESULT_ERROR_CODE
            );
        }
        
        $labels = $this->computeHistoryChartLabels($project);
        $datasets = $this->computeHistoryChartDatasets($project);
        
        $data = array(
            'labels' => $labels,
            'datasets' => $datasets,
        );
        
        return $data;
    }
    
    /**
     * Computes and returns the PHPMD scores for all the analyses of a given project. 
     * 
     * @param ProjectEntity $project
     * 
     * @return array
     * 
     * @throws AnalysesParsersException
     */
    public function forProjectScores(ProjectEntity $project)
    {
        // Validate the arguments and throw the corresponding errors if there are problems.
        if ($this->isResultEmpty($project)) {
            throw new AnalysesParsersException(
                'Project which has no analyses or only analyses with empty PHPMD results provided!',
                AnalysesParsersException::EMPTY_RESULT_ERROR_CODE
            );
        }
        
        $scores = array();
        foreach ($project->getAnalyses() as $analysis) {
            if (!parent::isResultEmpty($analysis)) {
                $scores[$analysis->getId()] = $this->getAnalysisScore($analysis);
            }
        }
        
        return $scores;
    }
    
    /**
     * Fetches the analyses with valid PHPMD results of a given project.
     * 
     * @param ProjectEntity $project
     * 
     * @return array
     * 
     * @throws AnalysesParsersException
     */
    public function forProjectValidation(ProjectEntity $project)
    {
        // Validate the arguments and throw the corresponding errors if there are problems.
        if ($this->isResultEmpty($project)) {
            throw new AnalysesParsersException(
                'Project which has no analyses or only analyses with empty PHPMD results provided!',
                AnalysesParsersException::EMPTY_RESULT_ERROR_CODE
            );
        }
        
        $validAnalyses = array();
        foreach ($project->getAnalyses() as $analysis) {
            if (!parent::isResultEmpty($analysis)) {
                $validAnalyses[] = $analysis->getId();
            }
        }
        
        return $validAnalyses;
    }
}
