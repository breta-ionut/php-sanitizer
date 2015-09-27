<?php

namespace PHPSanitizer\ProjectBundle\Service\Analyzer;

use Symfony\Component\Filesystem\Filesystem;
use PHPSanitizer\ProjectBundle\Entity\AnalyzerResponse\PHPMD as PHPMDResponse;

/**
 * Service which handles PHPMD analysis.
 */
class PHPMD
{
    /**
     * @defgroup phpmd-exit-codes
     * @{
     * 
     * The status code returned by PHPMD when it didn't found any errors in the given project's source code.
     */
    const PHPMD_EXIT_CODE_EMPTY = 0;
    
    /**
     * The status code returned by PHPMD when an error was raised during the analysis runtime.
     */
    const PHPMD_EXIT_CODE_ERROR = 1;
    
    /**
     * The status code returned by PHPMD when it found errors for the given project and it stored them
     * in the given report file.
     */
    const PHPMD_EXIT_CODE_RESULTS = 2;
    /**
     * @} endof "defgroup phpmd-exit-codes"
     */
    
    /**
     * The absolute path of the workspace directory.
     * 
     * @var string
     */
    protected $workspaceDir;
    
    /**
     * The path to the PHPMD service.
     * 
     * @var string
     */
    protected $phpMDPath;
    
    /**
     * The format of the PHPMD response.
     * 
     * @var string
     */
    protected $format = 'xml';
    
    /**
     * The rules which PHPMD will use to scan the projects.
     * 
     * @var array
     */
    protected $rules;
    
    /**
     * The filesystem service.
     * 
     * @var Filesystem
     */
    protected $filesystem;
    
    /**
     * The class constructor.
     * 
     * @param string $kernelRootDir
     * @param string $workspaceRelativeDir
     * @param string $phpMDPath
     * @param array $rules
     */
    public function __construct($kernelRootDir, $workspaceRelativeDir, $phpMDPath, array $rules)
    {
        $this->workspaceDir = realpath($kernelRootDir . '\..\\' . $workspaceRelativeDir);
        $this->phpMDPath = $phpMDPath;
        $this->rules = $rules;
        $this->filesystem = new Filesystem();
    }
    
    /**
     * Creates the temporary file where the PHPMD reports will be stored.
     * 
     * @return string
     * 
     * @throws \RuntimeException
     * If the file couldn't be created.
     */
    protected function createResultsFile()
    {
        $resultsFile = tempnam($this->workspaceDir, '');
        if ($resultsFile === false) {
            throw new \RuntimeException('Couldn\'t create the PHPMD results file!');
        }
        
        return $resultsFile;
    }

    /**
     * Invokes PHPMD and forwards its operation status.
     * 
     * @param string $sourceCodeDir
     * @param string $resultsFile
     * The path to the file where the results will be stored.
     * 
     * @return boolean
     * True if PHPMD found errors in the given source code and false otherwise.
     * 
     * @throws \RuntimeException
     * If a PHPMD internal error was raised.
     */
    protected function invokePHPMD($sourceCodeDir, $resultsFile)
    {
        $rules = implode(',', $this->rules);
        $command = "php $this->phpMDPath\phpmd \"$sourceCodeDir\" $this->format $rules --reportfile $resultsFile";
        
        // Call PHPMD.
        exec($command, $output, $status);
        
        if ($status === self::PHPMD_EXIT_CODE_EMPTY) {
            return false;
        } elseif ($status === self::PHPMD_EXIT_CODE_ERROR) {
            throw new \RuntimeException('A PHPMD internal error was detected!');
        }
        
        return true;
    }
    
    /**
     * Parses the errors reported by PHPMD and returns the results.
     * 
     * @param string $sourceCodeDir
     * @param string $resultsFile
     * 
     * @return array
     * 
     * @throws \RuntimeException
     */
    protected function parseResult($sourceCodeDir, $resultsFile)
    {
        // Parse the raw result.
        $xmlDom = simplexml_load_file($resultsFile);
        if ($xmlDom === false) {
            throw new \RuntimeException('Couldn\'t parse the errors reported by PHPMD!');
        }
        
        $errors = array();
        foreach ($xmlDom->file as $file) {
            $filename = str_replace($sourceCodeDir, '', $file['name']);
            foreach ($file->violation as $violation) {
                $error = array(
                    'beginline' => $violation['beginline'],
                    'endline' => $violation['endline'],
                    'rule' => $violation['rule'],
                    'url' => !empty($violation['externalInfoUrl']) ? $violation['externalInfoUrl'] : null,
                    'ruleset' => $violation['ruleset'],
                    'priority' => $violation['priority'],
                    'message' => $violation,
                );
                
                // Convert the error properties to simple string elements.
                foreach ($error as &$property) {
                    $property = (string) $property;
                }
                unset($property);
                
                $errors[$filename][] = $error;
            }
        }
        
        return $errors;
    }

    /**
     * Performs clean-up operations after the analysis.
     * 
     * @param string $resultsFile
     */
    protected function cleanUp($resultsFile)
    {
        if ($resultsFile) {
            $this->filesystem->remove($resultsFile);
        }
    }

    /**
     * Analyzes the project placed at the given path and returns the results.
     * 
     * @param string $sourceCodeDir
     * 
     * @return PHPMDResponse
     */
    public function analyze($sourceCodeDir)
    {
        try {
            // Create the temporary results file.
            $resultsFile = $this->createResultsFile();
            
            // Invoke PHPMD and analyze the return status.
            $status = $this->invokePHPMD($sourceCodeDir, $resultsFile);
            if ($status === false) {
                return new PHPMDResponse(array());
            }
            
            // Parse the PHPMD report file.
            $parsedResult = $this->parseResult($sourceCodeDir, $resultsFile);
        } finally {
            $resultsFile = !empty($resultsFile) ? $resultsFile : null;
            $this->cleanUp($resultsFile);
        }
        
        return new PHPMDResponse($parsedResult);
    }
}
