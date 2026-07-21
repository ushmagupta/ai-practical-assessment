<?php

declare(strict_types=1);

namespace Drupal\support_ticket\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters routes for ticket-specific access requirements.
 */
class TicketsRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('view.tickets.page_1')) {
      $route->setRequirement('_user_is_logged_in', 'TRUE');
    }
  }

}
