<?php


namespace SkyDiablo\AsyncEventDispatcherBundle\Queue;


/**
 * Description for interface QueueItemInterface
 */
interface QueueItemInterface
{

    /**
     * @return string
     */
    public function getEventName();

    /**
     * @return string
     */
    public function getData();

    /**
     * @return string
     */
    public function getQueueIdentifier();

    /**
     * @param string $queueIdentifier
     * @return null
     */
    public function setQueueIdentifier($queueIdentifier);


}