<?php

namespace PHPSanitizer\ProjectBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command offers the possibility of analyzing a project. 
 */
class AnalyzeCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('php_sanitizer_project:analyze')
            ->setDescription('Command used for analyzing projects.')
            ->addArgument('id', InputArgument::REQUIRED, 'The id of the project you want to analyze.');
    }
    
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectId = $input->getArgument('id');
        $this->getContainer()
            ->get('php_sanitizer_project.analyzer')
            ->analyze($projectId);
    }
}
