<?php

namespace SkyDiablo\AsyncEventDispatcherBundle\Queue\Memory\Listener;

use SkyDiablo\AsyncEventDispatcherBundle\Queue\Memory\MemoryQueue;

/**
 * @author Volker von Hoesslin <volker@oksnap.me>
 * Class RequestTerminateListener
 */
class RequestTerminateListener
{

    /**
     * @var MemoryQueue
     */
    private $memoryQueue;

    /**
     * @var EventDi
     */
    private $eventDispatcher;

    public function onTerminate() {



    }

}