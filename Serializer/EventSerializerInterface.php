<?php


namespace SkyDiablo\AsyncEventDispatcherBundle\Serializer;

use SkyDiablo\AsyncEventDispatcherBundle\Queue\QueueItemInterface;
use Symfony\Component\EventDispatcher\Event;


/**
 * Description for interface EventSerializerInterface
 */
interface EventSerializerInterface
{

    /**
     * @param Event $event
     * @param string $eventName
     * @return QueueItemInterface
     */
    public function serialize(Event $event, $eventName);

    /**
     * @param QueueItemInterface $queueItem
     * @return Event
     */
    public function deserialize(QueueItemInterface $queueItem);

}