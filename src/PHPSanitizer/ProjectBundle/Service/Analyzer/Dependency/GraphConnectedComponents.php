<?php

namespace PHPSanitizer\ProjectBundle\Service\Analyzer\Dependency;

/**
 * Service used to determine the strongly connected components in a directed graph, using Kosaraju's algorythm.
 * 
 * @see https://en.wikipedia.org/wiki/Kosaraju%27s_algorithm
 */
class GraphConnectedComponents
{
    /**
     * The graph represented as a adjacency list.
     * 
     * @var array
     */
    protected $graph;
    
    /**
     * The transpose graph.
     * 
     * @var array
     */
    protected $reversedGraph;
    
    /**
     * The node stack.
     * 
     * @var array
     */
    protected $stack;
    
    /**
     * The visited nodes.
     * 
     * @var array
     */
    protected $visited;
        
    /**
     * Builds the transpose graph.
     */
    protected function buildReversedGraph()
    {
        $this->reversedGraph = array_fill_keys(array_keys($this->graph), array());
        foreach ($this->graph as $vertex => $neighbours) {
            foreach ($neighbours as $neighbour) {
                $this->reversedGraph[$neighbour][] = $vertex;
            }
        }
    }
    
    /**
     * Prepares the algorythm running environment.
     * 
     * @param array $graph
     */
    protected function prepare(array $graph)
    {
        $this->graph = $graph;
        $this->buildReversedGraph();
        $this->stack = array();
        $this->visited = array();
    }
    
    /**
     * Runs a DFS on the graph and after consuming all the neighbours of the given vertex, adds it to a stack.
     * This method is used by the algorythm.
     * 
     * @param mixed $vertex
     */
    protected function dfsStack($vertex)
    {
        $this->visited[$vertex] = true;
        foreach ($this->graph[$vertex] as $neighbour) {
            if (empty($this->visited[$neighbour])) {
                $this->dfsStack($neighbour);
            }
        }
    
        array_unshift($this->stack, $vertex);
    }
    
    /**
     * Runs a DFS on the transpose graph and stores the visited nodes.
     * 
     * @param mixed $vertex
     * @param array $component
     */
    protected function dfs($vertex, array &$component)
    {
        $component[] = $vertex;
        $this->visited[$vertex] = true;
        foreach ($this->reversedGraph[$vertex] as $neighbour) {
            if (empty($this->visited[$neighbour])) {
                $this->dfs($neighbour, $component);
            }
        }
    }
    
    /**
     * Executes the algorythm and returns the result.
     * 
     * @param array $graph
     * 
     * @return array
     */
    public function get(array $graph)
    {
        // Prepare the algorythm running environment.
        $this->prepare($graph);
        
        // Pick a node outside the stack and run the custom DFS starting from it.
        // This version of DFS adds to the stack every node upon finishing expanding it.
        while ($available = array_diff(array_keys($this->graph), $this->stack)) {
            $vertex = reset($available);
            $this->dfsStack($vertex);
        }

        $this->visited = array();
        $connectedComponents = array();
        // Take every node from the stack and run a DFS with it as a starting point on the transpose graph.
        // After a running the DFS, the visited nodes on that particular session will represent a connected component.
        while ($this->stack) {
            $vertex = array_shift($this->stack);
            if (!empty($this->visited[$vertex])) {
                continue;
            }

            $component = array();
            $this->dfs($vertex, $component);

            $connectedComponents[] = $component;
        }
        
        return $connectedComponents;
    }
}
