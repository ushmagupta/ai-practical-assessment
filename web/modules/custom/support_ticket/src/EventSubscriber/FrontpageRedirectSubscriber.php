<?php

declare(strict_types=1);

namespace Drupal\support_ticket\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects unscoped core frontpage requests to the ticket list.
 */
class FrontpageRedirectSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onRequest', 300],
    ];
  }

  /**
   * Redirects /node (core frontpage view) to the scoped tickets list.
   */
  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();
    $path = rtrim($request->getPathInfo(), '/') ?: '/';

    if ($path === '/tickets' && \Drupal::currentUser()->isAnonymous()) {
      $event->setResponse(new RedirectResponse('/user/login', RedirectResponse::HTTP_FOUND));
      return;
    }

    $front = \Drupal::config('system.site')->get('page.front');

    if ($path === '/node' || ($path === '/' && $front === '/node')) {
      $event->setResponse(new RedirectResponse('/tickets', RedirectResponse::HTTP_FOUND));
    }
  }

}
