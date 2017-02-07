<?php

namespace SkyDiablo\AsyncEventDispatcherBundle\DependencyInjection;

use SkyDiablo\AsyncEventDispatcherBundle\Queue\AWSSQS\AWSSQSQueue;
use SkyDiablo\AsyncEventDispatcherBundle\Queue\Memory\Listener\RequestTerminateListener;
use SkyDiablo\AsyncEventDispatcherBundle\Queue\Memory\MemoryQueue;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AsyncEventDispatcherExtension extends Extension
{

    const QUEUE_SERVICE_NAME = 'async_event_dispatcher.queue';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $queueDefinition = null;
        foreach ($config['queue'] AS $key => $queueConfig) {
            switch (strtolower($key)) {
                case 'awssqs': //config aws sqs queue
                    $sqsClient = new Reference($queueConfig['sqs_client']);
                    $queueDefinition = new Definition();
                    $queueDefinition->setClass(AWSSQSQueue::class);
                    $queueDefinition->setArguments([
                        $sqsClient, //SQS-Client
                        $queueConfig['queue_url'], //AWS Queue URL
                        new Reference('logger')
                    ]);
                    if (isset($queueConfig['long_polling_timeout'])) {
                        $queueDefinition->addMethodCall('setLongPollingTimeout', [$queueConfig['long_polling_timeout']]);
                    }
                    break 2;
                case 'memory':
                    $queueDefinition = new Definition();
                    $queueDefinition->setClass(MemoryQueue::class);

                    // activate event listener/subscriber
                    $terminateListener = new Definition();
                    $terminateListener->setClass(RequestTerminateListener::class);
                    $terminateListener->addArgument(new Reference('async_event_dispatcher.service.queue_worker'));
                    $terminateListener->addTag('kernel.event_subscriber');
                    $container->setDefinition('async_event_dispatcher.terminate_listener', $terminateListener);
                    break 2;
            }
        }
        if (!$queueDefinition) {
            throw new \InvalidArgumentException('Missing AsyncEventDispatcher queue!');
        }

        $container->setDefinition(self::QUEUE_SERVICE_NAME, $queueDefinition);

        $asyncEventDispatcher = $container->getDefinition('async_collector_event_dispatcher');
        $asyncEventDispatcher->replaceArgument(0, new Reference(self::QUEUE_SERVICE_NAME)); //override first constructor argument

        $queueWorkerService = $container->getDefinition('async_event_dispatcher.service.queue_worker');
        $queueWorkerService->replaceArgument(0, new Reference(self::QUEUE_SERVICE_NAME)); //override first constructor argument
    }
}
