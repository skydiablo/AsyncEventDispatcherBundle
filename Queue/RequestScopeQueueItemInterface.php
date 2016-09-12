<?php

namespace AsyncEventDispatcherBundle\Queue;

use Symfony\Component\HttpFoundation\Request;

/**
 * Description for class RequestScopeQueueItemInterface
 */
interface RequestScopeQueueItemInterface extends QueueItemInterface {

    /**
     * @return Request
     */
    public function getRequest();

    /**
     * @param Request $request
     */
    public function setRequest(Request $request);

}