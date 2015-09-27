<?php

namespace PHPSanitizer\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use PHPSanitizer\UserBundle\Entity\User;
use PHPSanitizer\UserBundle\Form\RegistrationType;

/**
 * Controller which handles the user registration process.
 */
class RegisterController extends Controller
{
    /**
     * Tries to save a given user, and if the operation is succesful, authenticates it.
     * 
     * @param Request $request
     * @param User $user
     * 
     * @return Response|null
     */
    protected function tryToSaveAndAuthenticateUser(Request $request, User $user)
    {
        // Firstly, encode the password, and attach it to the new user.
        $encoder = $this->get('security.password_encoder');
        $password = $encoder->encodePassword($user, $user->getPlainPassword());
        $user->setPassword($password);
        
        // Validate the user object.
        $errors = $this->get('validator')->validate($user);
        if (count($errors) === 0) {
            // Save the user object to the database.
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            
            // Authenticate the new user.
            $token = new UsernamePasswordToken($user, $user->getPassword(), 'main', $user->getRoles());
            $this->get('security.token_storage')->setToken($token);
            
            $event = new InteractiveLoginEvent($request, $token);
            $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);
            
            // Perform a redirect to the main page of the application and return the resulted response.
            return $this->redirectToRoute('php_sanitizer_user_login');
        }

        return null;
    }
    
    /**
     * Performs registration related actions.
     * 
     * @param Request $request
     * 
     * @return Response
     */
    public function indexAction(Request $request)
    {
        // Create the register form.
        $form = $this->createForm(new RegistrationType());
        
        // Handle the submitted data.
        $form->handleRequest($request);
        
        // Handle form validation.
        $internalErrorsDetected = false;
        if ($form->isValid()) {
            // If the submitted data is valid, try to create the user and authenticate it automatically.
            $user = $form->getData()->getUser();
            // If the authentication method returns a valid response, forward it.
            // Otherwise, flag the detection of an internal error and send a message to the user.
            $response = $this->tryToSaveAndAuthenticateUser($request, $user);
            if ($response) {
                return $response;
            } else {
                $internalErrorsDetected = true;
            }
        }
        
        // Render the form.
        return $this->render(
            'user/register.html.twig',
            array('internalErrorsDetected' => $internalErrorsDetected, 'form' => $form->createView())
        );
    }
}
