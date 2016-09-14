# AsyncEventDispatcher

The AsyncEventDispatcher is a symfony bundle that provides an easy way for asynchronous event handling. Handle all your existing or new events in an async style. Just define your event handler as usual and run it in an async scope.

A) Downloading the Bundle
----------------------

Open your command line, navigate to your project directory and execute the
following command to download the latest stable version of this bundle:

    $ composer require skydiablo/async-event-bundle

This command requires you to have Composer installed globally, as explained
in the [installation chapter of the Composer documentation](https://getcomposer.org/).

B) Enabling the Bundle
--------------------

Enable the bundle by adding the following line in the ``app/AppKernel.php``
file of your project:

    // app/AppKernel.php
    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            $bundles = array(
                // ...
                new SkyDiablo\AsyncEventDispatcherBundle\AsyncEventDispatcherBundle(),
            );

            // ...
        }
    }

C) Configuring the Bundle
----------------------
There's currently only one event queue implemented: Amazon AWS SQS. If you need another one, feel free to implement it and make a pull request.

    async_event_dispatcher:
        queue:
            awssqs:
                queue_url: http://...AWS...QUEUE...URL
                long_polling_timeout: 10 # some time in seconds...
                sqs_client: aws_sqs_client_service_name

D) Usage
----------------------

* Just create your events and listener like documented [here](http://symfony.com/doc/current/event_dispatcher.html)
* Instead of tagging your listener like this: `kernel.event_listener`, just add the '.async' suffix: `kernel.event_listener.async`
  * The same applies to subscriber tagging: `kernel.event_subscriber.async`
* Publish your event serializer
  * For simple and static events, just use the `GenericEventSerializer`. Define your serializer for events like this:
    
          services:
              your_custom_serializer:
                  class: SkyDiablo\AsyncEventDispatcherBundle\Serializer\GenericEventSerializer
                  tags:
                      - { name: async_event_serializer, event: your_simple_event_name }

  * Trickier events with complex objects or doctrine entities need a custom event serializer. You can choose how you want to serialize and deserialize the event data. Create your serializer and implement the `SkyDiablo\AsyncEventDispatcherBundle\Serializer\EventSerializerInterface` interface. Define this new serializer as explained above.
  
          class FooEventSerializer implements EventSerializerInterface
          {
          
              /**
               * @var FooRepository
               */
              private $fooRepository;
          
              /**
               * FooEventSerializer constructor.
               * @param FooRepository $fooRepository
               */
              public function __construct(FooRepository $fooRepository)
              {
                  $this->fooRepository = $fooRepository;
              }
          
              /**
               * @param Event $event
               * @param string $eventName
               * @return QueueItemInterface
               */
              public function serialize(Event $event, $eventName)
              {
                  if ($event instanceof FooEvent) {
                      return $event->getFoo()->getId(); // just serialize the entity id
                  }
              }
          
              /**
               * @param QueueItemInterface $queueItem
               * @return Event
               */
              public function deserialize(QueueItemInterface $queueItem)
              {
                  $snapId = (int)$queueItem->getData(); // "getData()" contains the result from serialize function
                  if($fooId) { // a valid number greater zero? 
                      if($foo = $this->fooRepository->getById($fooId)) { // try to load foo entity by id
                          return new FooEvent($foo); // create the event object that will be triggered async
                      }
                  }
              }
          }
              
  From now on, all your listeners/subscribers which are tagged for async handling will run in an async scope. Attention: events without serializers will be ignored and are never handled!   
 * The core of the async event handling is a CLI command. Run this command in cyclic interval (i. e. cronjob-style):

            $ php bin/console async_event_dispatcher --iterate-amount 5
       
   This command will handle 5 events in this run, the default is set to handle 10 events in one run.
   
Thats it!  

E) Extra
----------------------
In some situations you might need the scope of the request where the event was thrown. To use the scope of that request in the async event handler, do the following:

    /**
     * Inject in a propper way
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    public function someEventHandler(YourEvent $event) {
        // You can request the current request in the async event handler like this
        $currentRequest = $this->requestStack->getCurrentRequest();
    }
    
     
There are some simple solutions:

 * Implement the `SkyDiablo\AsyncEventDispatcherBundle\Event\AsyncRequestScopeEventInterface` interface in your custom event
 * Define request scope in the subscriber config:
  
        class ExceptionSubscriber implements EventSubscriberInterface
        {
          public static function getSubscribedEvents()
          {
              // return the subscribed events, their methods and priorities
              return array(
                 KernelEvents::EXCEPTION => array(
                     array('processException', 10, true), // The third parameter "true" activates the request scope
                 )
              );
          }
 * Define the request scope within the event listener tag:
 
         # app/config/services.yml
         services:
             app.exception_listener:
                 class: AppBundle\EventListener\ExceptionListener
                 tags:
                     # set "async-request" to "true" or "false"
                     - { name: kernel.event_listener.async, event: kernel.exception, async-request: true }
                     
                     
What if you can't or don't want to handle an event asynchronously? Just implement this interface:

        \SkyDiablo\AsyncEventDispatcherBundle\Event\AsyncEventInterface
        
and return "false" at the `isAllowAsync` function. Then this event will never be called asynchronously!