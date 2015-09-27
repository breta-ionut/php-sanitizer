<?php

namespace PHPSanitizer\ProjectBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Define a form type which handles form delete operations.
 */
class ProjectDeleteType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('yes', 'submit')
            ->add('no', 'submit');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'php_sanitizer_project_bundle_project_delete';
    }
}
