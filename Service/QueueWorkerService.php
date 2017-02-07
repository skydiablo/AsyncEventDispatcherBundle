<?php

namespace SkyDiablo\AsyncEventDispatcherBundle\Service;

use SkyDiablo\AsyncEventDispatcherBundle\Queue\QueueInterface;
use SkyDiablo\AsyncEventDispatcherBundle\Queue\RequestScopeQueueItemInterface;
use SkyDiablo\AsyncEventDispatcherBundle\Serializer\Manager\EventSerializerManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Volker von Hoesslin <volker@oksnap.me>
 * Class QueueWorkerService
 */
class QueueWorkerService
{

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AsyncEventDispatcherExtension::QUEUE_SERVICE_NAME
     * @var QueueInterface
     */
    private $queue;

    /**
     * 'async_event_dispatcher.serializer_manager.container_aware_event_serializer_manager'
     * @var EventSerializerManagerInterface
     */
    private $eventSerializerManager;

    /**
     * AsyncEventDispatcherBundle::SERVICE_ASYNC_EVENT_DISPATCHER
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * QueueWorkerService constructor.
     * @param RequestStack $requestStack
     * @param LoggerInterface $logger
     * @param QueueInterface $queue
     * @param EventSerializerManagerInterface $eventSerializerManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        QueueInterface $queue,
        RequestStack $requestStack,
        LoggerInterface $logger,
        EventSerializerManagerInterface $eventSerializerManager,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->requestStack = $requestStack;
        $this->logger = $logger;
        $this->queue = $queue;
        $this->eventSerializerManager = $eventSerializerManager;
        $this->eventDispatcher = $eventDispatcher;
    }


    /**
     * @param int $iterations
     * @return bool
     */
    public function run(int $iterations)
    {
        $queueItems = $this->queue->pull($iterations);
        foreach ($queueItems AS $queueItem) {
            try {
                if ($queueItem instanceof RequestScopeQueueItemInterface) {
                    $this->enterRequestScope($queueItem);
                }
                $eventName = $queueItem->getEventName();
                if ($this->eventSerializerManager->has($eventName)) {
                    $eventSerializer = $this->eventSerializerManager->get($eventName);
                    $event = $eventSerializer->deserialize($queueItem);
                    if ($event instanceof Event) {
                        $this->eventDispatcher->dispatch($eventName, $event);
                    } else {
                        $this->logger->warning(sprintf('Given EventSerializer "%s" does not hydrate a valid event object', $eventName), [$eventSerializer, $queueItem->getData()]);
                    }
                } else {
                    $this->logger->warning(sprintf('No EventSerializer for "%s" available', $eventName));
                }
            } finally {
                $this->queue->remove($queueItem); //always delete item from queue
                if ($queueItem instanceof RequestScopeQueueItemInterface) {
                    $this->leaveRequestScope();
                }
            }
        }
        return (bool)count($queueItems);
    }

    /**
     * @param RequestScopeQueueItemInterface $queueItem
     */
    protected function enterRequestScope(RequestScopeQueueItemInterface $queueItem)
    {
        $this->requestStack->push($queueItem->getRequest());
    }

    /**
     * @return null|Request
     */
    protected function leaveRequestScope()
    {
        return $this->requestStack->pop();
    }


}