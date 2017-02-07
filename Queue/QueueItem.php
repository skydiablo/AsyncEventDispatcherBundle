<?php


namespace SkyDiablo\AsyncEventDispatcherBundle\Queue;


/**
 * Description for class QueueItem
 */
class QueueItem implements QueueItemInterface
{

    /**
     * @var string
     */
    private $eventName;

    /**
     * @var string
     */
    private $data;

    /**
     * @var string
     */
    private $queueIdentifier;

    /**
     * @param string $eventName
     * @param string $data
     */
    function __construct($eventName, $data = null)
    {
        $this->eventName = (string)$eventName;
        $this->data = (string)$data ?: null;
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getQueueIdentifier()
    {
        return $this->queueIdentifier;
    }

    /**
     * @param string $queueIdentifier
     * @return QueueItemInterface
     */
    public function setQueueIdentifier($queueIdentifier)
    {
        $this->queueIdentifier = $queueIdentifier;
        return $this;
    }

}