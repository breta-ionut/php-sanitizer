<?php

namespace PHPSanitizer\ProjectBundle\Service;

use Symfony\Component\Security\Core\Authorization\Voter\AbstractVoter;
use Symfony\Component\Security\Core\User\UserInterface;
use PHPSanitizer\UserBundle\Entity\User;

/**
 * Service which manages access to the project entity.
 */
class ProjectVoter extends AbstractVoter
{
    /**
     * @defgroup permissions
     * @{
     * 
     * Represents the permission to view a project.
     */
    const VIEW_PERMISSION = 'VIEW';
    
    /**
     * Represents the permission to edit a project.
     */
    const EDIT_PERMISSION = 'EDIT';
    
    /**
     * Represents the permission to delete a project.
     */
    const DELETE_PERMISSION = 'DELETE';
    /**
     * @} End of "defgroup permissions"
     */
    
    /**
     * {@inheritdoc}
     */
    protected function getSupportedClasses()
    {
        return array('PHPSanitizer\ProjectBundle\Entity\Project');
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getSupportedAttributes()
    {
        return array(self::VIEW_PERMISSION, self::EDIT_PERMISSION, self::DELETE_PERMISSION);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function isGranted($attribute, $object, $user = null)
    {
        // If the user isn't logged in, reject any possible action.
        if (!$user instanceof UserInterface) {
            return false;
        }
        
        // If the user object type is different from what we except, throw an exception, as this can be
        // the sign of a server configuration problem.
        if (!$user instanceof User) {
            throw new \LogicException();
        }
        
        // A project cannot be edited or deleted while it's being analyzed.
        if (($attribute === self::EDIT_PERMISSION || $attribute === self::DELETE_PERMISSION)
            && $object->getAnalyzing()
        ) {
            return false;
        }
        
        // A user can view, edit or delete a project if he is the owner.
        return $user->getId() === $object->getUser()->getId();
    }
}
