# AsyncEventDispatcher

An easy way to use asyncron event handling as symfony bundle. Just create and handle all your existing or new events in an async style. Define your event handler as usual and run in an async scope.

A) Download the Bundle
----------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

    $ composer require skydiablo/async-event-bundle

This command requires you to have Composer installed globally, as explained
in the [installation chapter of the Composer documentation](https://getcomposer.org/).

B) Enable the Bundle
--------------------

Then, enable the bundle by adding the following line in the ``app/AppKernel.php``
file of your project:

    // app/AppKernel.php
    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            $bundles = array(
                // ...
                new skydiablo\AsyncEventDispatcherBundle\AsyncEventDispatcherBundle(),
            );

            // ...
        }
    }

C) Configure
----------------------
For queueing the events, there is currently only one implemented: AWS SQS! if you need another solution (like DB or so on, make an PR!)

    async_event_dispatcher:
        queue:
            awssqs:
                queue_url: http://...AWS...QUEUE...URL
                long_polling_timeout: 10 # some seconds...
                sqs_client: aws_sqs_client_service_name

D) Usage
----------------------

* Just create your events and listener like documented [here](http://symfony.com/doc/current/event_dispatcher.html)
* tag your listener instead of `kernel.event_listener` just like this: `kernel.event_listener.async`
  * same way for subscriber: `kernel.event_subscriber.async`
* publish your event serializer
  * for simple and static events, just use the `GenericEventSerializer`. define your serializer for events like this way:
    
            services:
                your_custom_serializer:
                    class: AsyncEventDispatcherBundle\Serializer\GenericEventSerializer
                    tags:
                        - { name: async_event_serializer, event: your_simple_event_name }

  * a more tricky event with complex objects in it or doctrine entitys, needs a custom event serializer. it is in your hand how to serialize and deserialize the event data. create your serializer and implement `AsyncEventDispatcherBundle\Serializer\EventSerializerInterface` interface. define this new serializer in known style. 
              
  for now on, all you listener/subscriber as tagged for async handling will run in an async scope. beware, events without serializer will be ignored and never handled!   
 * the core of the async event handling is an CLI command. run this command in an cycle interval (like cronjob):

            $ php bin/console async_event_dispatcher --iterate-amount 5
       
   this command will handle 5 events in this run, default are 10 events.
   
thats is!  

E) Extra
----------------------
In some situations, you need the request scope of triggering the event? this mean, you can get the current request in async event handler.

    /**
     * injected in some propper ways
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    public function someEventHandler(YourEvent $event) {
        // so you can request the current request in async event handler!
        $currentRequest = $this->requestStack->getCurrentRequest();
    }
    
     
there are some simple solutions:

 * implement `AsyncEventDispatcherBundle\Event\AsyncRequestScopeEventInterface` interface in your custom event
 * define request scope in subscriber config:
  
        class ExceptionSubscriber implements EventSubscriberInterface
        {
          public static function getSubscribedEvents()
          {
              // return the subscribed events, their methods and priorities
              return array(
                 KernelEvents::EXCEPTION => array(
                     array('processException', 10, true), // the third parameter "true" activate the request scope
                 )
              );
          }
 * define request scope in event listener tag:
 
         # app/config/services.yml
         services:
             app.exception_listener:
                 class: AppBundle\EventListener\ExceptionListener
                 tags:
                     # set "async-request" to "true/false"
                     - { name: kernel.event_listener, event: kernel.exception, async-request: true }
                     
                     
at the other hand, maybe there is no way to handle an event async? just implement this interface:

        \AsyncEventDispatcherBundle\Event\AsyncEventInterface
        
and return at function `isAllowAsync` a "false" value. so this event will never be called async!
