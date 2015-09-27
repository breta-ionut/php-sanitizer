<?php

namespace PHPSanitizer\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Controller which handles the user login.
 */
class LoginController extends Controller
{
    /**
     * The action corresponding to the login operation.
     * 
     * @return Response
     */
    public function indexAction()
    {       
        $authenticationUtils = $this->get('security.authentication_utils');

        // Get the login error if there is one.
        $error = $authenticationUtils->getLastAuthenticationError();
        // Fetch the last username entered by the user.
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            'user/login.html.twig',
            array(
                'error' => $error,
                'last_username' => $lastUsername,
            )
        );
    }
    
    /**
     * The action corresponding to the login check operation.
     */
    public function loginCheckAction()
    {
    }
}
