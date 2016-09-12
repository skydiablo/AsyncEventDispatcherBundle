<?php


namespace AsyncEventDispatcherBundle\Serializer\Manager;

use AsyncEventDispatcherBundle\Serializer\EventSerializerInterface;


/**
 * Description for interface EventSerializerManagerInterface
 */
interface EventSerializerManagerInterface {

    /**
     * @param $eventSerializer
     * @param $eventName
     * @return void
     */
    public function add($eventSerializer, $eventName);

    /**
     * @param string $eventName
     * @return bool
     */
    public function has($eventName);

    /**
     * @param $eventName
     * @return EventSerializerInterface
     * @throws \OutOfBoundsException
     */
    public function get($eventName);

}