<?php


namespace AsyncEventDispatcherBundle\Serializer;

use AsyncEventDispatcherBundle\Queue\QueueItem;
use AsyncEventDispatcherBundle\Queue\QueueItemInterface;
use Symfony\Component\EventDispatcher\Event;

class GenericEventSerializer implements EventSerializerInterface
{

    /**
     * @param Event $event
     * @param string $eventName
     * @return QueueItemInterface
     */
    public function serialize(Event $event, $eventName)
    {
        return new QueueItem($eventName, serialize($event));
    }

    /**
     * @param QueueItemInterface $data
     * @return Event
     */
    public function deserialize(QueueItemInterface $data)
    {
        return unserialize($data->getData());
    }
}