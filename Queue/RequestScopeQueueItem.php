<?php


namespace SkyDiablo\AsyncEventDispatcherBundle\Queue;

use Symfony\Component\HttpFoundation\Request;


/**
 * Description for class RequestScopeQueueItem
 */
class RequestScopeQueueItem extends QueueItem implements RequestScopeQueueItemInterface
{

    /**
     * @var string
     */
    private $request;

    /**
     * @param Request $request
     * @param string $eventName
     * @param string $data
     */
    public function __construct(Request $request, $eventName, $data)
    {
        parent::__construct($eventName, $data);
        $this->request = $request;
    }


    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

}