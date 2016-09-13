<?php


namespace SkyDiablo\AsyncEventDispatcherBundle\Queue;


/**
 * Description for interface QueueInterface
 */
interface QueueInterface {

    /**
     * @param QueueItemInterface $queueItem
     * @return bool
     */
    public function add(QueueItemInterface $queueItem);

    /**
     * @param int $maxCount
     * @return QueueItemInterface[]
     */
    public function pull($maxCount = 10);

    /**
     * @param QueueItemInterface $queueItem
     * @return mixed
     */
    public function remove(QueueItemInterface $queueItem);

}