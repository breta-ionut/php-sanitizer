<?php

namespace PHPSanitizer\ProjectBundle\Service;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use PHPSanitizer\ProjectBundle\Entity\Project;
use PHPSanitizer\ProjectBundle\Event\ProjectEvents;
use PHPSanitizer\ProjectBundle\Event\ProjectEvents\SourceChangeEvent;

/**
 * Service which manages project related operations.
 */
class ProjectManager implements EventSubscriber
{
    /**
     * The complete path to the directory of the projects.
     * 
     * @var string
     */
    protected $projectsBaseDir;
    
    /**
     * The event dispatcher service.
     *
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    /**
     * The filesystem service.
     * 
     * @var Filesystem
     */
    protected $fileSystem;
    
    /**
     * The class constructor.
     * 
     * @param string $kernelRootDir
     * @param string $projectsRelativeBaseDir
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct($kernelRootDir, $projectsRelativeBaseDir, EventDispatcherInterface $eventDispatcher)
    {
        $this->projectsBaseDir = realpath($kernelRootDir . '\..\\' . $projectsRelativeBaseDir);
        $this->eventDispatcher = $eventDispatcher;
        $this->fileSystem = new Filesystem();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            'prePersist',
            'preUpdate',
            'postPersist',
            'postUpdate',
            'postRemove',
        );
    }

    /**
     * Computes the filename of a project's source code and attaches it.
     * 
     * @param Project $project
     */
    protected function setProjectPath(Project $project)
    {
        $dirname = $this->projectsBaseDir . '\\' . $project->getUser()->getId();
        $basename = md5(uniqid(mt_rand()));
        $path = $dirname . '\\' . $basename;
        
        $project->setFilepath($path);
    }
    
    /**
     * Tries to calculate the hash of a project's source code and attaches it if the operation is succesful.
     * 
     * @param Project $project
     * 
     * @return boolean
     * True if the operation succeeds and false otherwise.
     */
    protected function setProjectHash(Project $project)
    {
        if (($file = $project->getFile()) === null) {
            return false;
        }
        
        $temporaryPath = $file->getPath() . '\\' . $file->getFilename();
        $project->setHash(hash_file('sha256', $temporaryPath));

        return true;
    }
    
    /**
     * Saves the source code of the given project.
     * 
     * @param Project $project
     * @param array $entityChangeSet
     * 
     * @return void
     */
    protected function saveProjectData(Project $project, array $entityChangeSet)
    {
        if (($file = $project->getFile()) === null) {
            return;
        }
        
        $filepath = $project->getFilepath();
        $file->move(dirname($filepath), basename($filepath));
        $project->setFile();

        // If the source code is different from the old one, dispatch an event which lets other
        // components know that.
        if (!empty($entityChangeSet['hash'])) {
            $event = new SourceChangeEvent($project, $this);
            $this->eventDispatcher->dispatch(ProjectEvents::SOURCE_CHANGE, $event);
        }
    }
    
    /**
     * Removes a project's source code from the server.
     * 
     * @param Project $project
     */
    protected function removeProjectData(Project $project)
    {
        $this->fileSystem->remove($project->getFilepath());
    }
    
    /**
     * @defgroup event-handlers
     * @{
     * 
     * When a project is persisted, we want to generate the filepath of its source code and to calculate
     * its hash property, which is basically a hash of the uploaded file. This way, we will track
     * the changes to the project's codebase over time in order to trigger analyses.
     * 
     * @param LifecycleEventArgs $arguments
     * 
     * @return void
     */
    public function prePersist(LifecycleEventArgs $arguments)
    {
        $entity = $arguments->getEntity();
        if (!$entity instanceof Project) {
            return;
        }
        
        $this->setProjectPath($entity);
        $this->setProjectHash($entity);
    }
    
    /**
     * When a project is updated, we also update the hash of its source code.
     * 
     * @param LifecycleEventArgs $arguments
     * 
     * @return void
     */
    public function preUpdate(LifecycleEventArgs $arguments)
    {
        $entity = $arguments->getEntity();
        if (!$entity instanceof Project) {
            return;
        }
        
        $hashUpdated = $this->setProjectHash($entity);
        if (!$hashUpdated) {
            return;
        }
        
        // If the hash property was updated, do some extra work in order to let Doctrine know that.
        $entityManager = $arguments->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $metaData = $entityManager->getClassMetadata(get_class($entity));
        $unitOfWork->recomputeSingleEntityChangeSet($metaData, $entity);
    }
    
    /**
     * After the project is persisted, we also save its source code on the server to be later analyzed.
     * 
     * @param LifecycleEventArgs $arguments
     * 
     * @return void
     */
    public function postPersist(LifecycleEventArgs $arguments)
    {
        $entity = $arguments->getEntity();
        if (!$entity instanceof Project) {
            return;
        }
        
        $entityChangeSet = $arguments->getEntityManager()
            ->getUnitOfWork()
            ->getEntityChangeSet($entity);
        $this->saveProjectData($entity, $entityChangeSet);
    }
    
    /**
     * The same as above.
     * 
     * @param LifecycleEventArgs $arguments
     * 
     * @return void
     */
    public function postUpdate(LifecycleEventArgs $arguments)
    {
        $entity = $arguments->getEntity();
        if (!$entity instanceof Project) {
            return;
        }
        
        $entityChangeSet = $arguments->getEntityManager()
            ->getUnitOfWork()
            ->getEntityChangeSet($entity);
        $this->saveProjectData($entity, $entityChangeSet);
    }
    
    /**
     * After a project is removed, delete its source code as we won't need it anymore.
     * 
     * @param LifecycleEventArgs $arguments
     */
    public function postRemove(LifecycleEventArgs $arguments)
    {
        $entity = $arguments->getEntity();
        if ($entity instanceof Project) {
            $this->removeProjectData($entity);
        }
    }
    /**
     * @} End of "defgroup event-handlers".
     */
}
