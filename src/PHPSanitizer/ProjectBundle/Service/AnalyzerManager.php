<?php

namespace PHPSanitizer\ProjectBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use PHPSanitizer\ProjectBundle\Event\ProjectEvents\SourceChangeEvent;
use PHPSanitizer\ProjectBundle\Event\AnalysisEvents\EndEvent;
use PHPSanitizer\ProjectBundle\Entity\Project;
use PHPSanitizer\ProjectBundle\Entity\Analysis;

/**
 * Service which has the role of watching and controlling analyses related activities.
 */
class AnalyzerManager
{
    /**
     * The path to the application console.
     * 
     * @var string
     */
    protected $consoleRoot;
    
    /**
     * The CLI command needed to perform an analysis.
     * 
     * @var string
     */
    protected $consoleCommand = 'php_sanitizer_project:analyze';
        
    /**
     * The collection of routes which correspond to analyses reading actions.
     * 
     * @var array
     */
    protected $analysisViewingRoutes;
    
    /**
     * A flag which determines if the app runs on windows.
     * 
     * @var boolean
     */
    protected $runningOnWindows;
    
    /**
     * The Doctrine service.
     * 
     * @var Registry
     */
    protected $doctrine;
    
    /**
     * The authorization checker, a service used to authorize the user for certain actions.
     * 
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;
    
    /**
     * The logger service.
     * 
     * @var LoggerInterface
     */
    protected $logger;
    
    /**
     * The class constructor.
     * 
     * @param string $kernelRootDir
     * @param array $analysisViewingRoutes
     */
    public function __construct($kernelRootDir, array $analysisViewingRoutes)
    {
        $this->consoleRoot = $kernelRootDir . '/../app/console';
        $this->analysisViewingRoutes = $analysisViewingRoutes;
        $this->runningOnWindows = (strpos(strtolower(PHP_OS), 'win') !== false);
    }
    
    /**
     * The doctrine service setter.
     * 
     * @param Registry $doctrine
     */
    public function setDoctrine(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    /**
     * The authorization checker setter.
     * 
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }
    
    /**
     * The logger service setter.
     * 
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Runs the CLI command which performs analyses.
     * 
     * @param int $projectId
     */
    protected function runAnalysisCommand($projectId)
    {
        // Build the base command.
        $baseCommand = 'php ' . $this->consoleRoot . ' ' . $this->consoleCommand . ' ' . $projectId;
        // We must adapt the command to the operating system.
        if ($this->runningOnWindows) {
            $command = "start /b $baseCommand > NUL 2> NUL";
            pclose(popen($command, 'r'));
        } else {
            $command = "$baseCommand /dev/null 2>/dev/null &";
            exec($command);
        }
    }
    
    /**
     * Starts an analysis on the given project.
     * 
     * @param Project $project
     * 
     * @throws \RuntimeException
     */
    protected function startAnalysis(Project $project)
    {
        // A single analysis may run on a project at a time.
        if ($project->getAnalyzing()) {
            throw new \RuntimeException(sprintf(
                'Attempted to start an analysis on project with id:%d while another was running. There might be an '
                . 'error in the logic of the application.',
                $project->getId()
            ));
        }
        
        // Flag the project as being analyzed to prevent race conditions or other problems.
        $project->setAnalyzing(true);
        $this->doctrine
            ->getManager()
            ->flush();
        
        // Run the analysis command.
        $this->runAnalysisCommand($project->getId());
    }
    
    /**
     * Ends the analysis process.
     * 
     * @param Project $project
     */
    protected function endAnalysis(Project $project)
    {
        // Remove the flag from the project.
        $project->setAnalyzing(false);
        $this->doctrine
            ->getManager()
            ->flush();
    }
    
    /**
     * Marks an analysis as viewed by setting its "new" flag to false.
     * 
     * @param Analysis $analysis
     */
    protected function markViewedAnalysis(Analysis $analysis)
    {
        $analysis->setNew(false);
        $this->doctrine
            ->getManager()
            ->flush();
    }
    
    /**
     * @defgroup event-handlers
     * @{
     * 
     * When a new version of a project's source code is uploaded, we automatically analyze it.
     * 
     * @param SourceChangeEvent $event
     */
    public function onProjectSourceChange(SourceChangeEvent $event)
    {
        try {
            $this->startAnalysis($event->getProject());
        } catch (\Exception $exception) {
            // Silently fail when an exception is caught as we are in an event listener and we don't
            // want to break the chain. Log the error though for future analysis.
            $this->logger->error($exception);
        }
    }
    
    /**
     * When an analysis is ended, we perform several cleanup tasks.
     * 
     * @param EndEvent $event
     */
    public function onAnalysisEnd(EndEvent $event)
    {
        try {
            $this->endAnalysis($event->getProject());
        } catch (\Exception $exception) {
            // Silently fail when an exception is caught as we are in an event listener and we don't
            // want to break the chain. Log the error though for future analysis.
            $this->logger->error($exception);
        }
    }
    
    /**
     * Listen to requests on the pages corresponding to analysis reading actions and mark the associated
     * analyses as viewed by setting their "new" property to false.
     * 
     * @param PostResponseEvent $event
     * 
     * @return void
     */
    public function onTerminate(PostResponseEvent $event)
    {
        $requestAttributes = $event->getRequest()->attributes;

        // Firstly, we check if we are on an analysis reading page and afterwards, we try to fetch
        // the analysis id from the request attributes.
        if (!in_array($requestAttributes->get('_route'), $this->analysisViewingRoutes)
            || ($analysisId = $requestAttributes->get('id')) === null
        ) {
            return;
        }
        
        // Try to fetch the analysis from the database.
        $analysis = $this->doctrine
            ->getRepository('PHPSanitizer\ProjectBundle\Entity\Analysis')
            ->find($analysisId);
        // Check if the analysis actually exists and if the user was allowed to read its results.
        if ($analysis !== null && $this->authorizationChecker->isGranted('VIEW', $analysis->getProject())) {
            // Finally, mark the analysis as viewed.
            $this->markViewedAnalysis($analysis);
        }
    }
    /**
     * @} end of "defgroup event-handlers"
     */
}
