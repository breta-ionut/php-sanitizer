<?php

namespace PHPSanitizer\ProjectBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class which defines the project add/edit form.
 */
class ProjectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text')
            ->add('file', 'file', array('required' => false))
            ->add('modules_path', 'text')
            ->add('submit', 'submit');
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'PHPSanitizer\ProjectBundle\Entity\Project',
        ));
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'php_sanitizer_project_bundle_project';
    }
}
