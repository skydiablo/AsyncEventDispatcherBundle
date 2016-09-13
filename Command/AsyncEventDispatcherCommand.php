<?php


namespace SkyDiablo\AsyncEventDispatcherBundle\Command;

use SkyDiablo\AsyncEventDispatcherBundle\AsyncEventDispatcherBundle;
use SkyDiablo\AsyncEventDispatcherBundle\DependencyInjection\AsyncEventDispatcherExtension;
use SkyDiablo\AsyncEventDispatcherBundle\Queue\QueueInterface;
use SkyDiablo\AsyncEventDispatcherBundle\Queue\RequestScopeQueueItemInterface;
use SkyDiablo\AsyncEventDispatcherBundle\Serializer\Manager\EventSerializerManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Description for class AsyncEventDispatcherCommand
 */
class AsyncEventDispatcherCommand extends ContainerAwareCommand
{

    const COMMAND_NAME = 'async_event_dispatcher';
    const OPTION_ITERATE_AMOUNT = 'iterate-amount';
    const DEFAULT_ITERATE_AMOUNT = 10;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var LoggerInterface
     */
    private $logger;

    protected function configure()
    {
        parent::configure();
        $this->setName(self::COMMAND_NAME);
        $this->addOption(self::OPTION_ITERATE_AMOUNT, null, InputOption::VALUE_OPTIONAL, null, self::DEFAULT_ITERATE_AMOUNT);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->logger = $this->getContainer()->get('logger');
        $this->requestStack = $this->getContainer()->get('request_stack');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /** @var QueueInterface $queue */
        $queue = $this->getContainer()->get(AsyncEventDispatcherExtension::QUEUE_SERVICE_NAME);
        /** @var EventSerializerManagerInterface $eventSerializerManager */
        $eventSerializerManager = $this->getContainer()->get('async_event_dispatcher.serializer_manager.container_aware_event_serializer_manager');
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->getContainer()->get(AsyncEventDispatcherBundle::SERVICE_ASYNC_EVENT_DISPATCHER);

        foreach ($queue->pull((int)$input->getOption(self::OPTION_ITERATE_AMOUNT) ?: self::DEFAULT_ITERATE_AMOUNT) AS $queueItem) {
            try {
                if ($queueItem instanceof RequestScopeQueueItemInterface) {
                    $this->enterRequestScope($queueItem);
                }
                $eventName = $queueItem->getEventName();
                if ($eventSerializerManager->has($eventName)) {
                    $eventSerializer = $eventSerializerManager->get($eventName);
                    $event = $eventSerializer->deserialize($queueItem);
                    if ($event instanceof Event) {
                        $eventDispatcher->dispatch($eventName, $event);
                    } else {
                        $this->logger->warning(sprintf('Given EventSerializer "%s" does not hydrate a valid event object', $eventName), [$eventSerializer, $queueItem->getData()]);
                    }
                } else {
                    $this->logger->warning(sprintf('No EventSerializer for "%s" available', $eventName));
                }
            } catch (\Exception $e) {
                $this->logger->error(sprintf('[ERROR] While execute "%s" command: %s', $this->getName(), $e->getMessage()), [$e, $queueItem]);
            } finally {
                $queue->remove($queueItem); //always delete item from queue
                if ($queueItem instanceof RequestScopeQueueItemInterface) {
                    $this->leaveRequestScope();
                }
            }
        }
        return 0;
    }

    /**
     * @param RequestScopeQueueItemInterface $queueItem
     */
    protected function enterRequestScope(RequestScopeQueueItemInterface $queueItem)
    {
        $this->requestStack->push($queueItem->getRequest());
    }

    /**
     * @return null|Request
     */
    protected function leaveRequestScope()
    {
        return $this->requestStack->pop();
    }

}