<?php

namespace Drupal\flocblock\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * FLoC Blocker ResponseSubscriber.
 */
class ResponseSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::RESPONSE => ['onKernelResponse']];
  }

  /**
   * Adds "Permissions-Policy: interest-cohort=()" header.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event.
   */
  public function onKernelResponse(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $event->getResponse()->headers->set('Permissions-Policy', 'interest-cohort=()');
  }

}
