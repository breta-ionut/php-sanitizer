<?php

namespace PHPSanitizer\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use PHPSanitizer\ProjectBundle\Entity\Project;
use PHPSanitizer\ProjectBundle\Form\ProjectType;
use PHPSanitizer\ProjectBundle\Form\ProjectDeleteType;
use PHPSanitizer\ProjectBundle\Exception\AnalysesParsersException;

/**
 * Controller which manages project related actions.
 */
class ProjectsController extends Controller
{    
    /**
     * Handles project creation action. 
     * 
     * @param Request $request
     * 
     * @return Response
     */
    public function addAction(Request $request)
    {
        $project = new Project();
        $project->setUser($this->getUser());
        
        // Create the form which manages the project creation.
        $form = $this->createForm(new ProjectType(), $project);
        
        // Handle user submitted data.
        $form->handleRequest($request);
        // Validate the form.
        if ($form->isValid()) {
            // If the data is valid, save the newly created project.
            $data = $form->getData();
            // Set created and updated flags.
            $data->setCreated()
                ->setUpdated();
            
            $doctrine = $this->getDoctrine()->getManager();
            $doctrine->persist($data);
            $doctrine->flush();
            
            return $this->redirectToRoute('php_sanitizer_project_index');
        }
        
        // Render the form.
        return $this->render(
            'project/add.html.twig',
            array('form' => $form->createView())
        );
    }
    
    /**
     * Handles the update action of a project.
     * 
     * @param Request $request
     * @param int $id
     * 
     * @return Response
     * 
     * @throws NotFoundHttpException
     * - if the project with the given id doesn't exist;
     * @throws AccessDeniedException
     * - if either the current user shouldn't be allowed to edit the project with the given id
     *   or if the project is being analyzed;
     */
    public function editAction(Request $request, $id)
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
        $this->denyAccessUnlessGranted('EDIT', $project);
        
        // Create the form which manages the project update.
        $form = $this->createForm(new ProjectType(), $project, array('validation_groups' => array('Edit')));
        
        // Handle user submitted data.
        $form->handleRequest($request);
        // Validate the form.
        if ($form->isValid()) {
            // If the data is valid, persist the changes to the project.
            $project->setUpdated();
            $doctrine = $this->getDoctrine()->getManager();
            $doctrine->flush();
            
            return $this->redirectToRoute('php_sanitizer_project_index');
        }
        
        // Render the form.
        return $this->render(
            'project/edit.html.twig',
            array('form' => $form->createView())
        );
    }
    
    /**
     * Handles the delete action of a project.
     * 
     * @param Request $request
     * @param int $id
     * 
     * @return Response
     * 
     * @throws NotFoundHttpException
     * - if the project with the given id doesn't exist;
     * @throws AccessDeniedException
     * - if either the current user shouldn't be allowed to delete the project with the given id
     *   or if the project is being analyzed;
     */
    public function deleteAction(Request $request, $id)
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
        $this->denyAccessUnlessGranted('DELETE', $project);
        
        // Create the form which manages the project delete action.
        $form = $this->createForm(new ProjectDeleteType());
        
        // Handle form submissions.
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->get('yes')->isClicked()) {
                $entityManager = $this->getDoctrine()->getManager();
                
                $entityManager->remove($project);
                $entityManager->flush();
            }
            
            return $this->redirectToRoute('php_sanitizer_project_index');
        }
        
        // Render the form.
        return $this->render(
            'project/delete.html.twig',
            array('form' => $form->createView())
        );
    }
    
    /**
     * Handles a user's projects summary listing action.
     * 
     * @return Response
     * 
     * @throws NotFoundHttpException
     * @throws AnalysesParsersException
     */
    public function indexAction()
    {
        // Parse the users's projects for rendering.
        try {
            $data = $this->get('php_sanitizer_project.analyses_parsers.user')
                ->forUserSummary($this->getUser());
        } catch (AnalysesParsersException $exception) {
            if ($exception->getCode() === AnalysesParsersException::EMPTY_RESULT_ERROR_CODE) {
                throw $this->createNotFoundException('There seem to be problems with the integrity of your data!');
            }
            
            throw $exception;
        }

        // Render the projects summary.
        return $this->render(
            'project/index.html.twig',
            $data
        );
    }
    
    /**
     * The response callback corresponding to the internal project watching endpoint, which has the role
     * of offering information about the states of a user's projects.
     * 
     * @return JsonResponse
     * 
     * @throws NotFoundHttpException
     * @throws AnalysesParsersException
     */
    public function watchAction()
    {
        // Parse the users's projects for rendering.
        try {
            $data = $this->get('php_sanitizer_project.analyses_parsers.user')
                ->forUserSummary($this->getUser());
        } catch (AnalysesParsersException $exception) {
            if ($exception->getCode() === AnalysesParsersException::EMPTY_RESULT_ERROR_CODE) {
                throw $this->createNotFoundException('There seem to be problems with the integrity of your data!');
            }
            
            throw $exception;
        }

        return new JsonResponse($data);
    }
    
    /**
     * Action corresponding to the project's analyses summary page.
     * 
     * @param int $id
     * 
     * @return Response
     * 
     * @throws NotFoundHttpException
     * @throws AnalysesParsersException
     */
    public function viewAction($id)
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
        
        // Parse the project's analyses for rendering.
        try {
            $data = $this->get('php_sanitizer_project.analyses_parsers.project')
                ->forProjectSummary($project);
        } catch (AnalysesParsersException $exception) {
            if ($exception->getCode() === AnalysesParsersException::EMPTY_RESULT_ERROR_CODE) {
                throw $this->createNotFoundException('The project doesn\'t have any analyses results attached!');
            }
            
            throw $exception;
        }

        return $this->render(
            'project/view.html.twig',
            $data
        );
    }
    
    /**
     * Callback corresponding to an internal action which makes information about the current status
     * (analyzed/not being analyzed) of a given project available.
     * 
     * @param int $id
     * 
     * @return JsonResponse
     * 
     * @throws NotFoundHttpException
     * @throws AnalysesParsersException
     */
    public function noticeAction($id)
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
        
        // Parse the project's information for rendering.
        try {
            $data = $this->get('php_sanitizer_project.analyses_parsers.project')
                ->forProjectNotifier($project);
        } catch (AnalysesParsersException $exception) {
            if ($exception->getCode() === AnalysesParsersException::EMPTY_RESULT_ERROR_CODE) {
                throw $this->createNotFoundException('The project doesn\'t have any analyses results attached!');
            }
            
            throw $exception;
        }
        
        return new JsonResponse($data);
    }
}
