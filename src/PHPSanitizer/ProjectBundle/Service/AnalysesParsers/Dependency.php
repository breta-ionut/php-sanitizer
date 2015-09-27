<?php

namespace PHPSanitizer\ProjectBundle\Service\AnalysesParsers;

use PHPSanitizer\ProjectBundle\Entity\Analysis;
use PHPSanitizer\ProjectBundle\Entity\AnalyzerResponse\Dependency as DependencyResponse;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsersCacheManager;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsers\AnalysesParsersInterface;
use PHPSanitizer\ProjectBundle\Service\AnalysesParsers\Helper;
use PHPSanitizer\ProjectBundle\Exception\AnalysesParsersException;

/**
 * The dependency response parser. It is used to parse the dependency analyses results
 * into different formats for passing them to the renderers.
 */
class Dependency implements AnalysesParsersInterface
{    
    /**
     * The name of the parser.
     */
    const NAME = 'dependency';
        
    /**
     * The parser dedicated cache manager service.
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
        return $resultContainer->getDependencyResults()->isEmpty();
    }
        
    /**
     * Parses the analysis dependency results and generates the dependency graph nodes and legend in a format
     * that it will be used for rendering. 
     * 
     * @param DependencyResponse $dependencyResponse
     * 
     * @return array
     * On the first key will be placed the nodes and on the second the legend. The method was designed to be used
     * with list.
     */
    private function computeGraphNodesAndLegend(DependencyResponse $dependencyResponse)
    {
        $components = $dependencyResponse->getConnectedComponents();
        $modulesNr = count($dependencyResponse->getModules());
        
        $nodes = array();
        $legend = array();
        $index = 0;
        foreach ($components as $component) {
            // Every component will have a unique color.
            $componentColor = $this->helper->generateRandomColor();

            foreach ($component as $vertex) {
                $nodes[$vertex] = array(
                    'id' => Settings::GRAPH_NODE_ID_PREFIX . $index,
                    'label' => $vertex,
                    'size' => Settings::GRAPH_NODE_SIZE,
                    'x' => $index,
                    'y' => $this->helper->generateRandomNumber($modulesNr),
                    'color' => $componentColor,
                );
                
                $index++;
            }
            
            $legend[] = array(
                'label' => implode(', ', $component),
                'color' => $componentColor,
            );
        }
        
        return array($nodes, $legend);
    }
    
    /**
     * Parses the analysis dependency results and generates the dependency graph edges in a format
     * that it will be used for rendering. 
     * 
     * @param DependencyResponse $dependencyResponse
     * @param array $nodes
     * 
     * @return array
     */
    private function computeGraphEdges(DependencyResponse $dependencyResponse, array $nodes)
    {
        $graph = $dependencyResponse->getGraph();
        
        $edges = array();
        $index = 0;
        foreach ($graph as $vertex => $neighbours) {
            foreach ($neighbours as $neighbour) {
                $edges[] = array(
                    'id' => Settings::GRAPH_EDGE_ID_PREFIX . $index,
                    'source' => $nodes[$vertex]['id'],
                    'target' => $nodes[$neighbour]['id'],
                    'color' => $nodes[$vertex]['color'],
                    'type' => Settings::GRAPH_EDGE_TYPE,
                );
                
                $index++;
            }
        }
        
        return $edges;
    }
    
    /**
     * Parses the dependency results in a format needed by the dependency graph renderer.
     * 
     * @param Analysis $analysis
     * 
     * @return array
     * 
     * @throws AnalysesParsersException
     */
    public function forAnalysisGraph(Analysis $analysis)
    {
        // Validate the arguments and throw the corresponding errors if there are problems.
        if ($this->isResultEmpty($analysis)) {
            throw new AnalysesParsersException(
                'Analysis with empty dependency results provided!',
                AnalysesParsersException::EMPTY_RESULT_ERROR_CODE
            );
        }
        
        // Try to fetch the data from the cache. If it isn't found there, proceed with generating it.
        if ($data = $this->cacheManager->get(__FUNCTION__, self::NAME, $analysis)) {
            return $data;
        }
        
        $dependencyResponse = $analysis->getDependencyResults();
        list ($nodes, $legend) = $this->computeGraphNodesAndLegend($dependencyResponse);
        $edges = $this->computeGraphEdges($dependencyResponse, $nodes);
        
        $data = array(
            'data' => array(
                'nodes' => array_values($nodes),
                'edges' => $edges,
            ),
            'legend' => $legend,
        );
        
        // Save the data to the cache.
        $this->cacheManager->set($data, __FUNCTION__, self::NAME, $analysis);
        
        return $data;
    }
}
