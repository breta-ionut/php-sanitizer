<?php

namespace PHPSanitizer\UserBundle\Service;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * A service used to redirect authenticated users from the login and register pages to a configured
 * default route.
 */
class Redirect
{
    /**
     * The authorization checker, a service used to determine if the user is logged in.
     * 
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;
    
    /**
     * The application router service.
     * 
     * @var Router
     */
    protected $router;
    
    /**
     * The name of the route where the user will be redirected.
     * 
     * @var string
     */
    protected $redirectRoute;
    
    /**
     * The class constructor.
     * 
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param Router $router
     * @param string $redirectRoute
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, Router $router, $redirectRoute)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->router = $router;
        $this->redirectRoute = $redirectRoute;
    }
    
    /**
     * The event listener, which handles all the logic of the service.
     * 
     * @param GetResponseEvent $getResponseEvent
     */
    public function onRequest(GetResponseEvent $getResponseEvent)
    {
        $route = $getResponseEvent->getRequest()->get('_route');
        if (in_array($route, array('php_sanitizer_user_login', 'php_sanitizer_user_register'))
            && $this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')
        ) {
            // If the current request takes place on the login or on the register pages, redirect the user
            // to the configured route.
            $getResponseEvent->setResponse(new RedirectResponse($this->router->generate($this->redirectRoute)));
        }
    }
}
