<?php

namespace PHPSanitizer\ProjectBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use PHPSanitizer\ProjectBundle\Entity\Project;
use PHPSanitizer\ProjectBundle\Entity\Analysis;
use PHPSanitizer\ProjectBundle\Entity\AnalyzerResponse\Dependency as DependencyResponse;
use PHPSanitizer\ProjectBundle\Entity\AnalyzerResponse\PHPMD as PHPMDResponse;
use PHPSanitizer\ProjectBundle\Event\AnalysisEvents;
use PHPSanitizer\ProjectBundle\Event\AnalysisEvents\EndEvent;
use PHPSanitizer\ProjectBundle\Service\Analyzer\Dependency;
use PHPSanitizer\ProjectBundle\Service\Analyzer\PHPMD;

/**
 * The analyzer service. It is used to analyze the code of a given project and save the results.
 */
class Analyzer
{
    /**
     * The absolute path of the workspace directory.
     * 
     * @var string
     */
    protected $workspaceDir;
    
    /**
     * The Doctrine service.
     * 
     * @var Registry
     */
    protected $doctrine;
        
    /**
     * The event dispatcher service.
     * 
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    /**
     * The dependency analyzer.
     * 
     * @var Dependency
     */
    protected $dependencyAnalyzer;
    
    /**
     * The PHPMD analyzer.
     * 
     * @var PHPMD
     */
    protected $phpMDAnalyzer;
    
    /**
     * The logger service.
     * 
     * @var LoggerInterface
     */
    protected $logger;
    
    /**
     * The filesystem service.
     * 
     * @var Filesystem
     */
    protected $fileSystem;
    
    /**
     * The service constructor.
     * 
     * @param string $kernelRootDir
     * @param string $workspaceRelativeDir
     * @param Registry $doctrine
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(
        $kernelRootDir,
        $workspaceRelativeDir,
        Registry $doctrine,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ) {
        $this->workspaceDir = realpath($kernelRootDir . '\..\\' . $workspaceRelativeDir);
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->fileSystem = new Filesystem();
    }
    
    /**
     * Dependency analyzer setter.
     * 
     * @param Dependency $dependencyAnalyzer
     */
    public function setDependencyAnalyzer(Dependency $dependencyAnalyzer)
    {
        $this->dependencyAnalyzer = $dependencyAnalyzer;
    }
    
    /**
     * PHPMD analyzer setter.
     * 
     * @param PHPMD $phpMDAnalyzer
     */
    public function setPHPMDAnalyzer(PHPMD $phpMDAnalyzer)
    {
        $this->phpMDAnalyzer = $phpMDAnalyzer;
    }
    
    /**
     * Performs the code analysis process of the project with the given id and saves the results.
     * 
     * @param int $id
     */
    public function analyze($id)
    {
        // Firstly, match the requirements.
        $project = $this->requirements($id);
        if (!$project) {
            return;
        }
        // If the requirements were matched, proceed with the execution.
        $this->execute($project);
    }
    
    /**
     * Matches the requirements of the analysis process: a valid project.
     * 
     * @param int $id
     * 
     * @return Project|null
     * 
     * @throws \InvalidArgumentException
     */
    protected function requirements($id)
    {
        try {
            // Fetch the project.
            $project = $this->doctrine
                ->getManager()
                ->getRepository('PHPSanitizer\ProjectBundle\Entity\Project')
                ->find($id);
            
            // Check if it's valid.
            if (empty($project)) {
                throw new \InvalidArgumentException('The id of an existing project must be provided!');
            }
        } catch (\Exception $exception) {
            // Log errors.
            $this->logger->error($exception);
            
            return null;
        }
        
        return $project;
    }

    /**
     * Executes the analysis process.
     * 
     * @param Project $project
     */
    protected function execute(Project $project)
    {
        try {
            // Make preparations for the analysis process.
            $projectDir = $this->prepare($project);
            
            // Run the analyzers and fetch the results.
            $dependencyResults = $this->dependencyAnalyzer->analyze($projectDir, $project->getModulesPath());
            $phpMDResults = $this->phpMDAnalyzer->analyze($projectDir);
            
            // Save the results.
            $this->saveResults($project, $dependencyResults, $phpMDResults);
        } catch (\Exception $exception) {
            // Log errors.
            $this->logger->error($exception);
        } finally {
            // Perform the cleanup process even if the analysis failed.
            $projectDir = !empty($projectDir) ? $projectDir : null;
            $this->cleanUp($project, $projectDir);
        }
    }

    /**
     * Makes the preparations for the analysis process: unzips the source code of the project
     * into a temporary directory and returns its path.
     * 
     * @param Project $project
     * 
     * @return string
     * 
     * @throws FileNotFoundException
     */
    protected function prepare(Project $project)
    {
        $sourceFile = $project->getFilepath();
        if (!is_file($sourceFile)) {
            throw new FileNotFoundException('The project source code was expected to still exist!');
        }
        
        $projectDir = $this->createTempDirectory();
        $this->unzip($sourceFile, $projectDir);
        
        return $projectDir;
    }
    
    /**
     * Creates a temporary directory in the analysis workspace.
     * 
     * @return string
     */
    protected function createTempDirectory()
    {   
        $tempName = tempnam($this->workspaceDir, '');
        if ($this->fileSystem->exists($tempName)) {
            $this->fileSystem->remove($tempName);
        }
        
        $this->fileSystem->mkdir($tempName);
        
        return $tempName;
    }
    
    /**
     * Unzips the source code of the project.
     * 
     * @param string $sourceFile
     * @param string $projectDir
     */
    protected function unzip($sourceFile, $projectDir)
    {
        $archive = new \ZipArchive();
        $archive->open($sourceFile);
        $archive->extractTo($projectDir);
    }
    
    /**
     * Saves the results of the analysis.
     * 
     * @param Project $project
     * @param DependencyResponse $dependencyResults
     * @param PHPMDResponse $phpMDResults
     */
    protected function saveResults(Project $project, DependencyResponse $dependencyResults, PHPMDResponse $phpMDResults)
    {
        $analysis = new Analysis();
        $analysis->setProject($project)
            ->setCreated()
            ->setDependencyResults($dependencyResults)
            ->setPHPMDResults($phpMDResults);
        
        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($analysis);
        $entityManager->flush();
    }
    
    /**
     * Performs the cleanup after the analysis process.
     * 
     * @param Project $project
     * @param string $projectDir
     */
    protected function cleanUp(Project $project, $projectDir = null)
    {
        if ($projectDir) {
            $this->fileSystem->remove($projectDir);
        }
        
        // Let other components an analysis process has ended.
        $event = new EndEvent($project);
        $this->eventDispatcher->dispatch(AnalysisEvents::END, $event);
    }
}
