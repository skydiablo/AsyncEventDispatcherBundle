<?php

namespace SkyDiablo\AsyncEventDispatcherBundle\Queue\Memory\Listener;

use SkyDiablo\AsyncEventDispatcherBundle\Service\QueueWorkerService;
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

    public function onTerminate(PostResponseEvent $event)
    {
        while ($this->queueWorkerService->run(100)) ;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::TERMINATE, 'onTerminate',
        ];
    }
}