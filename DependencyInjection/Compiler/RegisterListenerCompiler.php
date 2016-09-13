<?php


namespace SkyDiablo\AsyncEventDispatcherBundle\DependencyInjection\Compiler;

use SkyDiablo\AsyncEventDispatcherBundle\EventDispatcher\AsyncEventDispatcher;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;


/**
 * Description for class RegisterListenerCompiler
 */
class RegisterListenerCompiler extends RegisterListenersPass {

    const ASYNC_REQUEST = 'async-request';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container) {

        if (!$container->hasDefinition($this->dispatcherService) && !$container->hasAlias($this->dispatcherService)) {
            return;
        }

        $definition = $container->findDefinition($this->dispatcherService);
        if (is_a($definition->getClass(), AsyncEventDispatcher::class, true)) {
            $this->registerListener($container, $definition);
        } else {
            return parent::process($container);
        }
    }

    protected function registerListener(ContainerBuilder $container, Definition $definition) {
        foreach ($container->findTaggedServiceIds($this->listenerTag) as $id => $events) {
            $def = $container->getDefinition($id);
            if (!$def->isPublic()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must be public as event listeners are lazy-loaded.', $id));
            }

            if ($def->isAbstract()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must not be abstract as event listeners are lazy-loaded.', $id));
            }

            foreach ($events as $event) {
                $priority = isset($event['priority']) ? $event['priority'] : 0;

                if (!isset($event['event'])) {
                    throw new \InvalidArgumentException(sprintf('Service "%s" must define the "event" attribute on "%s" tags.', $id, $this->listenerTag));
                }

                if (!isset($event['method'])) {
                    $event['method'] = 'on' . preg_replace_callback([
                            '/(?<=\b)[a-z]/i',
                            '/[^a-z0-9]/i',
                        ], function ($matches) {
                            return strtoupper($matches[0]);
                        }, $event['event']);
                    $event['method'] = preg_replace('/[^a-z0-9]/i', '', $event['method']);
                }

                $definition->addMethodCall(
                    'addListenerService',
                    [
                        $event['event'],
                        [
                            $id,
                            $event['method'],
                            isset($event[self::ASYNC_REQUEST]) ? filter_var($event[self::ASYNC_REQUEST], FILTER_VALIDATE_BOOLEAN) : false
                        ],
                        $priority
                    ]
                );
            }
        }

        foreach ($container->findTaggedServiceIds($this->subscriberTag) as $id => $attributes) {
            $def = $container->getDefinition($id);
            if (!$def->isPublic()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must be public as event subscribers are lazy-loaded.', $id));
            }

            if ($def->isAbstract()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must not be abstract as event subscribers are lazy-loaded.', $id));
            }

            // We must assume that the class value has been correctly filled, even if the service is created by a factory
            $class = $container->getParameterBag()->resolveValue($def->getClass());

            $refClass = new \ReflectionClass($class);
            $interface = 'Symfony\Component\EventDispatcher\EventSubscriberInterface';
            if (!$refClass->implementsInterface($interface)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
            }

            $definition->addMethodCall('addSubscriberService', [$id, $class]);
        }
    }


}