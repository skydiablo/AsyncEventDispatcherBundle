<?php


namespace SkyDiablo\AsyncEventDispatcherBundle\Serializer\Manager;

use SkyDiablo\AsyncEventDispatcherBundle\Serializer\EventSerializerInterface;

/**
 * Description for class EventSerializerManager
 */
class EventSerializerManager implements EventSerializerManagerInterface
{

    private $serializerList = [];

    /**
     * @param $eventSerializer
     * @param $eventName
     * @return void
     */
    public function add($eventSerializer, $eventName)
    {
        if (!($eventSerializer instanceof EventSerializerInterface)) {
            if ($this->has($eventName)) {
                throw new \InvalidArgumentException(sprintf('Event name "%s" already added', $eventName));
            }
            $this->serializerList[strtolower($eventName)] = $eventSerializer;
        } else {
            throw new \InvalidArgumentException(sprintf('Given serializer is not type of "%s"', EventSerializerInterface::class));
        }
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function has($eventName)
    {
        return isset($this->serializerList[strtolower($eventName)]);
    }

    /**
     * @param $eventName
     * @return EventSerializerInterface
     * @throws \OutOfBoundsException
     */
    public function get($eventName)
    {
        if (!$this->has($eventName)) {
            throw new \OutOfBoundsException('Unknown event serializer for "%s"', $eventName);
        }
        return $this->serializerList[strtolower($eventName)];
    }

}