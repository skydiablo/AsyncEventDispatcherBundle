<?php


namespace SkyDiablo\AsyncEventDispatcherBundle\Serializer\Manager;

use SkyDiablo\AsyncEventDispatcherBundle\Serializer\EventSerializerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Description for class ContainerAwareEventSerializerManager
 */
class ContainerAwareEventSerializerManager implements EventSerializerManagerInterface, ContainerAwareInterface
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $serializerList = [];

    /**
     * @param $eventSerializer
     * @param $eventName
     * @return void
     */
    public function add($eventSerializer, $eventName)
    {
        if ($this->has($eventName)) {
            throw new \InvalidArgumentException(sprintf('Event name "%s" already added', $eventName));
        }
        $this->serializerList[strtolower($eventName)] = $eventSerializer;
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function has($eventName)
    {
        $eventName = strtolower($eventName);
        return isset($this->serializerList[$eventName]) && $this->container->has($this->serializerList[$eventName]);
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
        return $this->container->get($this->serializerList[strtolower($eventName)]);
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}