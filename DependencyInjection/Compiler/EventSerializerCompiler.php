<?php

namespace AsyncEventDispatcherBundle\DependencyInjection\Compiler;

use AsyncEventDispatcherBundle\Serializer\Manager\ContainerAwareEventSerializerManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Description for class EventSerializerCompiler
 */
class EventSerializerCompiler implements CompilerPassInterface {

    const EVENT_SERIALIZER_MANAGER = 'async_event_dispatcher.serializer_manager.container_aware_event_serializer_manager';
    const EVENT_SERIALIZER_TAG = 'async_event_serializer';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container) {
        $tags = $container->findTaggedServiceIds(self::EVENT_SERIALIZER_TAG);
        if (count($tags) > 0 && $container->hasDefinition(self::EVENT_SERIALIZER_MANAGER)) {
            $manager = $container->getDefinition(self::EVENT_SERIALIZER_MANAGER);
            foreach ($tags as $id => $tag) {
                foreach ($tag AS $tagItem) {
                    if (!isset($tagItem['event'])) {
                        throw new \InvalidArgumentException('Missing Tag-Key "event"');
                    }
                    foreach ((array)$tagItem['event'] AS $event) {
                        if (is_a($manager->getClass(), ContainerAwareEventSerializerManager::class, true)) {
                            $manager->addMethodCall('add', [$id, $event]);
                        } else {
                            $manager->addMethodCall('add', [new Reference($id), $event]);
                        }
                    }
                }
            }
        }
    }

}