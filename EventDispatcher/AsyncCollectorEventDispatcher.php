<?php


namespace AsyncEventDispatcherBundle\EventDispatcher;

use AsyncEventDispatcherBundle\Event\AsyncEventInterface;
use AsyncEventDispatcherBundle\Event\AsyncRequestScopeEventInterface;
use AsyncEventDispatcherBundle\Queue\QueueInterface;
use AsyncEventDispatcherBundle\Queue\QueueItemInterface;
use AsyncEventDispatcherBundle\Queue\RequestScopeQueueItem;
use AsyncEventDispatcherBundle\Serializer\Manager\EventSerializerManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher AS BaseEventDispatcher;
use Symfony\Component\HttpFoundation\Request;


/**
 * Description for class AsyncCollectorEventDispatcher
 */
class AsyncCollectorEventDispatcher extends BaseEventDispatcher
{

    /**
     * @var AsyncEventDispatcher
     */
    private $asyncEventDispatcher;

    /**
     * @var QueueInterface
     */
    private $queue;

    /**
     * @var EventSerializerManagerInterface
     */
    private $eventSerializerManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param QueueInterface $queue
     * @param ContainerInterface $container
     * @param AsyncEventDispatcher $asyncEventDispatcher
     * @param EventSerializerManagerInterface $eventSerializerManager
     * @param LoggerInterface $logger
     */
    function __construct(
        QueueInterface $queue,
        ContainerInterface $container,
        AsyncEventDispatcher $asyncEventDispatcher,
        EventSerializerManagerInterface $eventSerializerManager,
        LoggerInterface $logger
    )
    {
        parent::__construct($container);
        $this->asyncEventDispatcher = $asyncEventDispatcher;
        $this->queue = $queue;
        $this->eventSerializerManager = $eventSerializerManager;
        $this->logger = $logger;
    }

    /**
     * @param string $eventName
     * @param Event $event
     * @return Event
     */
    public function dispatch($eventName, Event $event = null)
    {
        if (null === $event) {
            $event = new Event();
        }
        if (!($event instanceof AsyncEventInterface) || $event->isAllowAsync()) {
            if ($this->asyncEventDispatcher->hasListeners($eventName)) {
                if ($this->eventSerializerManager->has($eventName)) {
                    try {
                        $queueItem = $this->eventSerializerManager->get($eventName)->serialize($event, $eventName);
                        if ($queueItem instanceof QueueItemInterface) {
                            $queueItem = $this->assignCurrentRequest($eventName, $event, $queueItem);
                            $this->queue->add($queueItem);
                        } else {
                            $this->logger->warning(sprintf('EventSerializer "%s" does not return a %s object', $eventName, QueueItemInterface::class), [$eventName, $event]);
                        }
                    } catch (\Exception $e) {
                        $this->logger->error(sprintf('[ERROR] Cannot add event to async queue: %s', $e->getMessage()), [$e, $eventName, $event]);
                    }
                } else {
                    $this->logger->warning(sprintf('Missing EventSerializer for async event "%s" handling', $eventName));
                }
            }
        } else {
            $this->logger->warning(sprintf('Event "%s" does not allowed async call', $eventName));
        }
        return parent::dispatch($eventName, $event);
    }

    protected function assignCurrentRequest($eventName, Event $event, QueueItemInterface $queueItem)
    {
        if ( // publish request in async handler ?
            ($request = $this->getCurrentRequest()) && // request available?
            (
                ($event instanceof AsyncRequestScopeEventInterface) || // event force to publish the current request
                $this->asyncEventDispatcher->isAsyncRequestNeeded($eventName) // listener tells that need the current request
            )
        ) { // store request to current async event?
            return new RequestScopeQueueItem($request, $queueItem->getEventName(), $queueItem->getData());
        }
        return $queueItem;
    }

    /**
     * @return Request
     */
    protected function getCurrentRequest()
    {
        $requestStack = $this->getContainer()->get('request_stack');
        return $requestStack->getCurrentRequest();
    }

}