<?php

namespace PHPSanitizer\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use PHPSanitizer\ProjectBundle\Exception\AnalysesParsersException;

/**
 * The controller responsible for analyses related operations.
 */
class AnalysesController extends Controller
{
    /**
     * Action which corresponds to the analysis dependency results page.
     * 
     * @param int $id
     * 
     * @return Response
     * 
     * @throws NotFoundHttpException
     * @throws AnalysesParsersException
     */
    public function dependencyAction($id)
    {
        $analysis = $this->getDoctrine()
            ->getRepository('PHPSanitizer\ProjectBundle\Entity\Analysis')
            ->find($id);
        
        // Access and data integrity checks.
        if ($analysis === null) {
            throw $this->createNotFoundException(
                sprintf('The analysis with the id %d couldn\'t be found!', $id)    
            );
        }
        $this->denyAccessUnlessGranted('VIEW', $analysis->getProject());
        
        // Parse the dependency results for rendering.
        try {
            $graph = $this->get('php_sanitizer_project.analyses_parsers.dependency')
                ->forAnalysisGraph($analysis);
        } catch (AnalysesParsersException $exception) {
            if ($exception->getCode() === AnalysesParsersException::EMPTY_RESULT_ERROR_CODE) {
                throw $this->createNotFoundException('This analysis doesn\'t have any dependency results attached!');
            }
            
            throw $exception;
        }
        
        return $this->render(
            'analysis/dependency.html.twig',
            array('graph' => $graph)
        );
    }
    
    /**
     * Action which corresponds to the analysis PHPMD results displayed as a chart page.
     * 
     * @param int $id
     * 
     * @return Response
     * 
     * @throws NotFoundHttpException
     * @throws AnalysesParsersException
     */
    public function chartAction($id)
    {
        $analysis = $this->getDoctrine()
            ->getRepository('PHPSanitizer\ProjectBundle\Entity\Analysis')
            ->find($id);
        
        // Access and data integrity checks.
        if ($analysis === null) {
            throw $this->createNotFoundException(
                sprintf('The analysis with the id %d couldn\'t be found!', $id)    
            );
        }
        $this->denyAccessUnlessGranted('VIEW', $analysis->getProject());

        // Parse the PHPMD results for rendering.
        try {
            $chart = $this->get('php_sanitizer_project.analyses_parsers.phpmd')
                ->forAnalysisChart($analysis);
        } catch (AnalysesParsersException $exception) {
            // Handle special error cases.
            if ($exception->getCode() === AnalysesParsersException::EMPTY_RESULT_ERROR_CODE) {
                throw $this->createNotFoundException('This analysis doesn\'t have any PHPMD results attached!');
            }
            
            throw $exception;
        }
        
        return $this->render(
            'analysis/chart.html.twig',
            array('chart' => $chart)
        );
    }
    
    /**
     * Action corresponding to the PHPMD errors page.
     * 
     * @param int $id
     * 
     * @return Response
     * 
     * @throws NotFoundHttpException
     * @throws AnalysesParsersException
     */
    public function tableAction($id)
    {
        $analysis = $this->getDoctrine()
            ->getRepository('PHPSanitizer\ProjectBundle\Entity\Analysis')
            ->find($id);
        
        // Access and data integrity checks.
        if ($analysis === null) {
            throw $this->createNotFoundException(
                sprintf('The analysis with the id %d couldn\'t be found!', $id)    
            );
        }
        $this->denyAccessUnlessGranted('VIEW', $analysis->getProject());
        
        // Parse the PHPMD results for rendering.
        try {
            $table = $this->get('php_sanitizer_project.analyses_parsers.phpmd')
                ->forAnalysisTable($analysis);
        } catch (AnalysesParsersException $exception) {
            // Handle special error cases.
            if ($exception->getCode() === AnalysesParsersException::EMPTY_RESULT_ERROR_CODE) {
                throw $this->createNotFoundException('This analysis doesn\'t have any PHPMD results attached!');
            }
            
            throw $exception;
        }

        return $this->render(
            'analysis/table.html.twig',
            array('table' => $table)
        );
    }
    
    /**
     * Action corresponding to the project PHPMD errors history chart page.
     * 
     * @param int $id
     * 
     * @return Response
     * 
     * @throws NotFoundHttpException
     * @throws AnalysesParsersException
     */
    public function phpMDHistoryChartAction($id)
    {
        $project = $this->getDoctrine()
            ->getRepository('PHPSanitizer\ProjectBundle\Entity\Project')
            ->find($id);
        
        // Access and data integrity checks.
        if ($project === null) {
            throw $this->createNotFoundException(
                sprintf('The project with the id %d couldn\'t be found!', $id)    
            );
        }
        $this->denyAccessUnlessGranted('VIEW', $project);
        
        // Parse the PHPMD results for rendering.
        try {
            $historyChart = $this->get('php_sanitizer_project.analyses_parsers.project_phpmd')
                ->forProjectHistoryChart($project);
        } catch (AnalysesParsersException $exception) {
            // Handle special error cases.
            if ($exception->getCode() === AnalysesParsersException::EMPTY_RESULT_ERROR_CODE) {
                throw $this->createNotFoundException(
                    'This project either doesn\'t have analyses with valid PHPMD results or doesn\'t have analyses '
                    . 'at all!'
                );
            }
            
            throw $exception;
        }
        
        return $this->render(
            'analysis/phpmd_history_chart.html.twig',
            array('chart' => $historyChart)
        );
    }
    
    /**
     * Action corresponding to the project dependency errors history chart page.
     * 
     * @param int $id
     * 
     * @return Response
     * 
     * @throws NotFoundHttpException
     * @throws AnalysesParsersException
     */
    public function dependencyHistoryChartAction($id)
    {
        $project = $this->getDoctrine()
            ->getRepository('PHPSanitizer\ProjectBundle\Entity\Project')
            ->find($id);
        
        // Access and data integrity checks.
        if ($project === null) {
            throw $this->createNotFoundException(
                sprintf('The project with the id %d couldn\'t be found!', $id)    
            );
        }
        $this->denyAccessUnlessGranted('VIEW', $project);
        
        // Parse the dependency results for rendering.
        try {
            $historyChart = $this->get('php_sanitizer_project.analyses_parsers.project_dependency')
                ->forProjectHistoryChart($project);
        } catch (AnalysesParsersException $exception) {
            // Handle special error cases.
            if ($exception->getCode() === AnalysesParsersException::EMPTY_RESULT_ERROR_CODE) {
                throw $this->createNotFoundException(
                    'This project either doesn\'t have analyses with valid PHPMD results or doesn\'t have analyses '
                    . 'at all!'
                );
            }
            
            throw $exception;
        }
        
        return $this->render(
            'analysis/dependency_history_chart.html.twig',
            array('chart' => $historyChart)
        );
    }
}
