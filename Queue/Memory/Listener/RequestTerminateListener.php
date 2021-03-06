<?php

namespace SkyDiablo\AsyncEventDispatcherBundle\Queue\Memory\Listener;

use SkyDiablo\AsyncEventDispatcherBundle\Service\QueueWorkerService;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Volker von Hoesslin <volker@oksnap.me>
 * Class RequestTerminateListener
 */
class RequestTerminateListener implements EventSubscriberInterface
{

    /**
     * @var QueueWorkerService
     */
    private $queueWorkerService;

    /**
     * RequestTerminateListener constructor.
     * @param QueueWorkerService $queueWorkerService
     */
    public function __construct(QueueWorkerService $queueWorkerService)
    {
        $this->queueWorkerService = $queueWorkerService;
    }

    /**
     * @param PostResponseEvent $event
     */
    public function onRequestTerminate(PostResponseEvent $event)
    {
        $this->processQueue();
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onCommandTerminate(ConsoleTerminateEvent $event)
    {
        $this->processQueue();
    }

    protected function processQueue()
    {
        while ($this->queueWorkerService->run(100)) ;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::TERMINATE => 'onRequestTerminate',
            ConsoleEvents::TERMINATE => 'onCommandTerminate',
        ];
    }
}