<?php

namespace PHPSanitizer\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * Custom repository class defined for the bundle's User entity.
 */
class UserRepository extends EntityRepository implements UserProviderInterface
{
    /**
     * Permits loading the user either by username or by e-mail.
     * 
     * {@inheritdoc}
     */
    public function loadUserByUsername($usernameOrEmail)
    {
        $user = $this->createQueryBuilder('u')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $usernameOrEmail)
            ->setParameter('email', $usernameOrEmail)
            ->getQuery()
            ->getOneOrNullResult();
        
        if ($user === null) {
            $message = sprintf(
                'Unable to find user identified by username of email "%s"!',
                $usernameOrEmail
            );
            
            throw new UsernameNotFoundException($message);
        }
        
        return $user;
    }
    
    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }

        return $this->find($user->getId());
    }
    
    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $this->getEntityName() === $class || is_subclass_of($class, $this->getEntityName());
    }
}
