<?php

namespace SkyDiablo\AsyncEventDispatcherBundle;

use SkyDiablo\AsyncEventDispatcherBundle\DependencyInjection\Compiler\EventSerializerCompiler;
use SkyDiablo\AsyncEventDispatcherBundle\DependencyInjection\Compiler\RegisterListenerCompiler;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AsyncEventDispatcherBundle extends Bundle
{
    const SERVICE_ASYNC_EVENT_DISPATCHER = 'async_event_dispatcher';
    const TAG_LISTENER = 'kernel.event_listener.async';
    const TAG_SUBSCRIBER = 'kernel.event_subscriber.async';

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new EventSerializerCompiler());

        $container->addCompilerPass(
            new RegisterListenerCompiler(
                self::SERVICE_ASYNC_EVENT_DISPATCHER,
                self::TAG_LISTENER,
                self::TAG_SUBSCRIBER
            ),
            PassConfig::TYPE_BEFORE_REMOVING
        );
    }


}
