<?php


namespace SkyDiablo\AsyncEventDispatcherBundle\EventDispatcher;

use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Description for class AsyncEventDispatcher
 */
class AsyncEventDispatcher extends ContainerAwareEventDispatcher
{

    private $needRequest = [];

    /**
     * Adds a service as event listener.
     *
     * @param string $eventName Event for which the listener is added
     * @param array $callback The service ID of the listener service & the method
     *                          name that has to be called
     * @param int $priority The higher this value, the earlier an event listener
     *                          will be triggered in the chain.
     *                          Defaults to 0.
     *
     * @throws \InvalidArgumentException
     */
    public function addListenerService($eventName, $callback, $priority = 0)
    {
        if (!is_array($callback) || 2 > count($callback)) {
            throw new \InvalidArgumentException('Expected an array("service", "method") argument');
        }
        if (isset($callback[2])) { // 3th parameter: async-request
            $this->setNeedRequest($eventName, $callback[2]);
            unset($callback[2]);
        }
        parent::addListenerService($eventName, $callback, $priority);
    }

    /**
     * Adds a service as event subscriber.
     *
     * @param string $serviceId The service ID of the subscriber service
     * @param string|EventSubscriberInterface $class The service's class name (which must implement EventSubscriberInterface)
     */
    public function addSubscriberService($serviceId, $class)
    {
        foreach ($class::getSubscribedEvents() as $eventName => $params) {
            if (is_string($params[0]) && isset($params[2])) {
                $this->setNeedRequest($eventName, $params[2]);
            } else {
                foreach ($params as $listener) {
                    if (isset($listener[2]) && $this->setNeedRequest($eventName, $listener)) {
                        break;
                    }
                }
            }
        }
        parent::addSubscriberService($serviceId, $class);
    }

    /**
     * @param string $eventName
     * @param bool $value
     * @return bool
     */
    protected function setNeedRequest($eventName, $value)
    {
        if ($value) {
            $this->needRequest[$eventName] = (bool)$value;
        }
        return $value;
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function isAsyncRequestNeeded($eventName)
    {
        return $this->needRequest[$eventName] ?? false;
    }


}