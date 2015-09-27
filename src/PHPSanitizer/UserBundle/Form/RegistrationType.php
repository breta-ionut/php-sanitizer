<?php

namespace PHPSanitizer\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class which defines the registration form type.
 */
class RegistrationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('user', new UserType())
            ->add('terms', 'checkbox', array('property_path' => 'termsAccepted'))
            ->add('submit', 'submit');
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'PHPSanitizer\UserBundle\Entity\Registration',
            'validation_groups' => array('Registration'),
        ));
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'php_sanitizer_user_bundle_registration';
    }
}
