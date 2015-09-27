<?php

namespace PHPSanitizer\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the user type form.
 */
class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username', 'text')
            ->add('email', 'email')
            ->add('password', 'repeated', array(
                'first_name' => 'password',
                'second_name' => 'confirm',
                'invalid_message' => 'The password fields must match.',
                'type' => 'password',
                'property_path' => 'plainPassword',
            ));
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'PHPSanitizer\UserBundle\Entity\User',
        ));
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'php_sanitizer_user_bundle_user';
    }
}
