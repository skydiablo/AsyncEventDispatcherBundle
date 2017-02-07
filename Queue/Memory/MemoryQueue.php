<?php

namespace SkyDiablo\AsyncEventDispatcherBundle\Queue\Memory;

use SkyDiablo\AsyncEventDispatcherBundle\Queue\QueueInterface;
use SkyDiablo\AsyncEventDispatcherBundle\Queue\QueueItemInterface;

/**
 * @author Volker von Hoesslin <volker@oksnap.me>
 * Class MemoryQueue
 */
class MemoryQueue implements QueueInterface
{

    private $queue = [];

    /**
     * @param QueueItemInterface $queueItem
     * @return bool
     */
    public function add(QueueItemInterface $queueItem)
    {
        $key = uniqid();
        $this->queue[$key] = $queueItem->setQueueIdentifier($key);
        return true;
    }

    /**
     * @param int $maxCount
     * @return QueueItemInterface[]
     */
    public function pull($maxCount = 10)
    {
        return array_splice($this->queue, 0, max((int)$maxCount), 1);
    }

    /**
     * @param QueueItemInterface $queueItem
     * @return bool
     */
    public function remove(QueueItemInterface $queueItem)
    {
        unset($this->queue[$queueItem->getQueueIdentifier()]);
        return true;
    }
}