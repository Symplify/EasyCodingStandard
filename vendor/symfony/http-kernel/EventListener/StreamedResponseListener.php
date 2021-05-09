<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix20210509\Symfony\Component\HttpKernel\EventListener;

use ECSPrefix20210509\Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ECSPrefix20210509\Symfony\Component\HttpFoundation\StreamedResponse;
use ECSPrefix20210509\Symfony\Component\HttpKernel\Event\ResponseEvent;
use ECSPrefix20210509\Symfony\Component\HttpKernel\KernelEvents;
/**
 * StreamedResponseListener is responsible for sending the Response
 * to the client.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
class StreamedResponseListener implements \ECSPrefix20210509\Symfony\Component\EventDispatcher\EventSubscriberInterface
{
    /**
     * Filters the Response.
     */
    public function onKernelResponse(\ECSPrefix20210509\Symfony\Component\HttpKernel\Event\ResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        $response = $event->getResponse();
        if ($response instanceof \ECSPrefix20210509\Symfony\Component\HttpFoundation\StreamedResponse) {
            $response->send();
        }
    }
    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents()
    {
        return [\ECSPrefix20210509\Symfony\Component\HttpKernel\KernelEvents::RESPONSE => ['onKernelResponse', -1024]];
    }
}