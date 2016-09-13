<?php


namespace SkyDiablo\AsyncEventDispatcherBundle\Event;


interface AsyncEventInterface {

    /**
     * Allow this event as async call?
     * @return bool
     */
    public function isAllowAsync();

}