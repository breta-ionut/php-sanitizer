<?php

namespace PHPSanitizer\ProjectBundle\Service\AnalysesParsers;

use PHPSanitizer\ProjectBundle\Entity\Analysis;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsersCacheManager;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsers\AnalysesParsersInterface;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsers\Helper;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsers\Settings;
use PHPSanitizer\ProjectBundle\Exception\AnalysesParsersException;

/**
 * The PHPMD response parser. It is used to parse the PHPMD analyses results
 * into different formats for passing them to the renderers.
 */
class PHPMD implements AnalysesParsersInterface
{   
    /**
     * The name of the parser.
     */
    const NAME = 'phpmd';
    
    /**
     * @defgroup phpmd-rules
     * @{
     * 
     * The code analysis rules used by PHPMD.
     */
    const RULE_CODESIZE = 'Code Size Rules';
    const RULE_CLEANCODE = 'Clean Code Rules';
    const RULE_CONTROVERSIAL = 'Controversial Rules';
    const RULE_DESIGN = 'Design Rules';
    const RULE_NAMING = 'Naming Rules';
    const RULE_UNUSEDCODE = 'Unused Code Rules';
    /**
     * @} endof "defgroup phpmd-rules"
     */
    
    /**
     * @defgroup phpmd-priorities
     * @{
     * 
     * The code analysis error priorities used by PHPMD.
     */
    const PRIORITY_1 = 1;
    const PRIORITY_2 = 2;
    const PRIORITY_3 = 3;
    const PRIORITY_4 = 4;
    const PRIORITY_5 = 5;
    /**
     * @} endof "defgroup phpmd-priorities"
     */
        
    /**
     * The complete collection of rules used by PHPMD.
     * 
     * @var array
     */
    protected static $rules = array(
        self::RULE_CODESIZE,
        self::RULE_CLEANCODE,
        self::RULE_CONTROVERSIAL,
        self::RULE_DESIGN,
        self::RULE_NAMING,
        self::RULE_UNUSEDCODE,
    );
    
    /**
     * The labels used to describe the PHPMD rules to the user.
     * 
     * @var array
     */
    protected static $ruleLabels = array(
        self::RULE_CODESIZE => 'Code size errors',
        self::RULE_CLEANCODE => 'Clean code errors',
        self::RULE_CONTROVERSIAL => 'Controversial',
        self::RULE_DESIGN => 'Design errors',
        self::RULE_NAMING => 'Naming errors',
        self::RULE_UNUSEDCODE => 'Unused code errors',
    );
    
    /**
     * The complete collection of priorities used by PHPMD.
     * 
     * @var array
     */
    protected static $priorities = array(
        self::PRIORITY_1,
        self::PRIORITY_2,
        self::PRIORITY_3,
        self::PRIORITY_4,
        self::PRIORITY_5,
    );
    
    /**
     * The labels used to describe the PHPMD priority levels to the user.
     * 
     * @var array
     */
    protected static $priorityLabels = array(
        self::PRIORITY_1 => 'Priority level 1',
        self::PRIORITY_2 => 'Priority level 2',
        self::PRIORITY_3 => 'Priority level 3',
        self::PRIORITY_4 => 'Priority level 4',
        self::PRIORITY_5 => 'Priority level 5',
    );
    
    /**
     * The colors associated to the PHPMD priority levels represented as a RGB array.
     * 
     * @var array
     */
    protected static $priorityColors = array(
        self::PRIORITY_1 => array(246, 140, 89),
        self::PRIORITY_2 => array(229, 120, 68),
        self::PRIORITY_3 => array(208, 103, 50),
        self::PRIORITY_4 => array(216, 85, 22),
        self::PRIORITY_5 => array(211, 68, 0),
    );
    
    /**
     * The cache manager used by the analyses parsers.
     *
     * @var AnalysesParsersCacheManager
     */
    protected $cacheManager;
    
    /**
     * The analysis parsers helper.
     * 
     * @var Helper
     */
    protected $helper;
    
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
     * {@inheritdoc}
     */
    public function isResultEmpty($resultContainer)
    {
        return $resultContainer->getPHPMDResults()->isEmpty();
    }
        
    /**
     * Counts the PHPMD errors by priority and by ruleset.
     * 
     * @param array $errors
     * 
     * @return array
     */
    protected function countErrorsByPriority(array $errors)
    {
        $counter = array();
        foreach ($errors as $file) {
            foreach ($file as $violation) {
                if (!isset($counter[$violation['priority']][$violation['ruleset']])) {
                    $counter[$violation['priority']][$violation['ruleset']] = 1;
                } else {
                    $counter[$violation['priority']][$violation['ruleset']]++;
                }
            }
        }
        
        return $counter;
    }
    
    /**
     * Generates the main categories of the analysis chart, which are actually the code analysis rules.
     * 
     * @return array
     */
    private function computeAnalysisChartLabels()
    {
        $labels = array();
        foreach (self::$rules as $rule) {
            $labels[] = self::$ruleLabels[$rule];
        }
        
        return $labels;
    }
    
    /**
     * Generates the datasets used to build the analysis chart. The chart is split in categories, represented by
     * analysis rules, and every category is divided itself in other subcategories, represented by
     * the priority level.
     * 
     * @param array $counter
     * 
     * @return array
     */
    private function computeAnalysisChartDatasets(array $counter)
    {
        $datasets = array();
        foreach (self::$priorities as $priority) {
            $data = array();
            foreach (self::$rules as $rule) {
                $data[] = !empty($counter[$priority][$rule]) ? $counter[$priority][$rule] : 0;
            }
            
            $datasets[] = array(
                'label' => self::$priorityLabels[$priority],
                'fillColor' => $this->helper->generateColorForCss(
                    self::$priorityColors[$priority],
                    Settings::CHART_FILL_OPACITY
                ),
                'strokeColor' => $this->helper->generateColorForCss(
                    self::$priorityColors[$priority],
                    Settings::CHART_FILL_OPACITY
                ),
                'highlightFill' => $this->helper->generateColorForCss(
                    self::$priorityColors[$priority],
                    Settings::CHART_HIGHLIGHT_FILL_OPACITY
                ),
                'highlightStroke' => $this->helper->generateColorForCss(
                    self::$priorityColors[$priority],
                    Settings::CHART_HIGHLIGHT_STROKE_OPACITY
                ),
                'data' => $data,
            );
        }
        
        return $datasets;
    }
    
    /**
     * Computes the legend of the analysis chart.
     * 
     * @return array
     */
    private function computeAnalysisChartLegend()
    {
        $legend = array();
        foreach (self::$priorities as $priority) {
            $legend[$priority] = array(
                'label' => self::$priorityLabels[$priority],
                'color' => $this->helper->generateColorForCss(self::$priorityColors[$priority]),
            );
        }
        
        return $legend;
    }
    
    /**
     * Computes the PHPMD analysis data for being rendered as a chart.
     * 
     * @param Analysis $analysis
     * 
     * @return array
     * 
     * @throws AnalysesParsersException
     */
    public function forAnalysisChart(Analysis $analysis)
    {
        // Validate the arguments and throw the corresponding errors if there are problems.
        if ($this->isResultEmpty($analysis)) {
            throw new AnalysesParsersException(
                'Analysis with empty PHPMD results provided!',
                AnalysesParsersException::EMPTY_RESULT_ERROR_CODE
            );
        }
        
        // Try to fetch the data from the cache firstly.
        if ($data = $this->cacheManager->get(__FUNCTION__, self::NAME, $analysis)) {
            return $data;
        }
        
        $counter = $this->countErrorsByPriority($analysis->getPHPMDResults()->getErrors());
        $labels = $this->computeAnalysisChartLabels();
        $datasets = $this->computeAnalysisChartDatasets($counter);
        $legend = $this->computeAnalysisChartLegend();
        
        $data = array(
            'data' => array(
                'labels' => $labels,
                'datasets' => $datasets,
            ),
            'legend' => $legend,
         );
        
        // Save the data in the cache after its has been computed.
        $this->cacheManager->set($data, __FUNCTION__, self::NAME, $analysis);
        
        return $data;
    }
    
    /**
     * Parses the PHPMD errors into user-readable information.
     * 
     * @param array $errors
     * 
     * @return array
     */
    private function computeAnalysisTable(array $errors)
    {
        $table = array();
        foreach ($errors as $filename => $file) {
            foreach ($file as $violation) {
                $priority = array(
                    'code' => $violation['priority'],
                    'color' => $this->helper->generateColorForCss(self::$priorityColors[$violation['priority']]),
                );
                $rule = array(
                    'name' => $violation['rule'],
                    'url' => $this->helper->filterUrl($violation['url']),
                );
                $lines = $violation['beginline'] . ':' . $violation['endline'];

                $table[$filename][] = array(
                    'priority' => $priority,
                    'ruleset' => self::$ruleLabels[$violation['ruleset']],
                    'rule' => $rule,
                    'lines' => $lines,
                    'message' => $violation['message'],
                );
            }
        }
        
        return $table;
    }
    
    /**
     * Computes the PHPMD analysis data in order to be rendered in a table.
     * 
     * @param Analysis $analysis
     * 
     * @return array
     * 
     * @throws AnalysesParsersException
     */
    public function forAnalysisTable(Analysis $analysis)
    {
        // Validate the arguments and throw the corresponding errors if there are problems.
        if ($this->isResultEmpty($analysis)) {
            throw new AnalysesParsersException(
                'Analysis with empty PHPMD results provided!',
                AnalysesParsersException::EMPTY_RESULT_ERROR_CODE
            );
        }
                
        // Try to fetch the data from the cache firstly.
        if ($data = $this->cacheManager->get(__FUNCTION__, self::NAME, $analysis)) {
            return $data;
        }
        
        $errors = $analysis->getPHPMDResults()->getErrors();
        $data = $this->computeAnalysisTable($errors);
        
        // Save the computed data in the cache.
        $this->cacheManager->set($data, __FUNCTION__, self::NAME, $analysis);
        
        return $data;
    }
}
