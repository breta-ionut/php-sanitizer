<?php

namespace PHPSanitizer\ProjectBundle\Service\Analyzer;

use Symfony\Component\Finder\Finder;
use PHPSanitizer\ProjectBundle\Service\Analyzer\Dependency\GraphConnectedComponents;
use PHPSanitizer\ProjectBundle\Entity\AnalyzerResponse\Dependency as DependencyResponse;

/**
 * Service which provides components dependency analysis for a PHP project.
 * The results of the analysis will include the components dependency graph,
 * the set of strongly connected components and a dependency score which
 * indicates how coupled are the components in the application.
 */
class Dependency
{
    /**
     * A helper service used to determine the strongly connected components of a graph.
     * 
     * @var GraphConnectedComponents
     */
    protected $connectedComponentsAnalyzer;
    
    /**
     * The class constructor.
     * 
     * @param GraphConnectedComponents $connectedComponentsAnalyzer
     */
    public function __construct(GraphConnectedComponents $connectedComponentsAnalyzer)
    {
        $this->connectedComponentsAnalyzer = $connectedComponentsAnalyzer;
    }
    
    /**
     * Determines the names of the modules of the scanned application and their absolute paths.
     * As a convention, we will consider that a module is named after its root directory, as
     * it happens in most well-known PHP frameworks.
     * 
     * @param string $sourceCodeDir
     * The root directory of the project.
     * @param string $modulesRelativePattern
     * The pattern to identify the modules root directories.
     * 
     * @return array
     */
    protected function getModules($sourceCodeDir, $modulesRelativePattern)
    {   
        $files = new Finder();
        $files->in($sourceCodeDir)
            ->path($modulesRelativePattern)
            ->directories();
        
        $modules = array();
        foreach ($files as $file) {
            $modules[$file->getBasename()] = $file->getRealpath();
        }
        
        return $modules;
    }
    
    /**
     * Parses the content of a file for used and exposed classes.
     * 
     * @param string $content
     * 
     * @return array
     * A two keys array:
     * - on the first key we have the classes exposed by the module;
     * - on the second key we have the classes used by the module;
     * It is conceived to be used with list().
     */
    protected function parse($content)
    {
        // Scan for declared classes.
        preg_match_all('/^\s*(?:abstract|final)?\s*(?:class|interface)\s+([a-zA-Z0-9_]+)/m', $content, $matches);
        $classes = !empty($matches[1]) ? $matches[1] : array();
        // Scan for the namespace.
        preg_match('/^\s*namespace\s+([a-zA-Z0-9_\\\\]+)/m', $content, $matches);
        $namespace = !empty($matches[1]) ? $matches[1] : '';
        
        // Prepend the namespace to the classes names to obtain their fully qualified names.
        $exposes = array();
        foreach ($classes as $class) {
            $exposes[] = $namespace ? ($namespace . '\\' . $class) : $class;
        }
        
        // Scan for the used classes.
        preg_match_all('/^\s*use\s+([a-zA-Z0-9_\\\\]+)/m', $content, $matches);
        $uses = !empty($matches[1]) ? $matches[1] : array();
        // Remove the trailing backslashes from the beginning of the used classes.
        foreach ($uses as &$usage) {
            if ($usage[0] == '\\') {
                $usage = substr($usage, 1);
            }
        }
        unset($usage);
        
        return array($exposes, $uses);
    }
    
    /**
     * Scans a module's files and determines the classes it exposes and the classes it uses.
     * 
     * @param string $path
     * 
     * @return array
     * An array with two keys:
     * - exposes: the classes exposed by the module;
     * - uses: the classes used by the module;
     */
    protected function scanModule($path)
    {
        $moduleExposes = array();
        $moduleUses = array();
        
        $files = new Finder();
        $files->in($path)
            ->name('/^.+(\.php|\.inc)$/i')
            ->files();
        foreach ($files as $file) {
            $content = $file->getContents();
            
            list ($exposes, $uses) = $this->parse($content);
            $moduleExposes = array_merge($moduleExposes, $exposes);
            $moduleUses = array_merge($moduleUses, $uses);
        }
        
        return array(
            'exposes' => array_unique($moduleExposes),
            'uses' => array_unique($moduleUses),
        );
    }
    
    /**
     * Builds the dependency graph of the project. A module depends on another module if it
     * uses one of its classes. In the graph structure, an edge will exist from the dependent
     * module to its dependency.
     * 
     * @param array $declarations
     * An array, which specifies for every modules what classes uses and what classes exposes.
     * 
     * @return array
     * The dependency graph, as adjacency list.
     */
    protected function buildDependencyGraph(array $declarations)
    {
        $graph = array_fill_keys(array_keys($declarations), array());
        foreach ($declarations as $module => $declaration) {
            foreach ($declarations as $usedModule => $usedDeclaration) {
                if ($module === $usedModule) {
                    continue;
                }
                
                if (array_intersect($declaration['uses'], $usedDeclaration['exposes'])) {
                    $graph[$module][] = $usedModule;
                }
            }
        }
        
        return $graph;
    }
    
    /**
     * Analyzes the sources placed at the given path and returns the results.
     * 
     * @param string $sourceCodeDir
     * @param string $modulesRelativePattern
     * 
     * @return DependencyResponse
     * 
     * @throws \InvalidArgumentException
     */
    public function analyze($sourceCodeDir, $modulesRelativePattern)
    {   
        // Get the modules and their paths.
        $modules = $this->getModules($sourceCodeDir, $modulesRelativePattern);
        if (empty($modules)) {
            return DependencyResponse::createEmptyResponse();
        }
        
        // Get the module declarations: what classes they expose and what classes they use.
        $declarations = array();
        foreach ($modules as $name => $path) {
            $declarations[$name] = $this->scanModule($path);
        }
        
        // Prepare the response.
        $moduleNames = array_keys($modules);
        $dependencyGraph = $this->buildDependencyGraph($declarations);
        $connectedComponents = $this->connectedComponentsAnalyzer->get($dependencyGraph);
        $score = count($connectedComponents) / count($modules);
        
        // Return the response.
        return new DependencyResponse($moduleNames, $dependencyGraph, $connectedComponents, $score);
    }
}
