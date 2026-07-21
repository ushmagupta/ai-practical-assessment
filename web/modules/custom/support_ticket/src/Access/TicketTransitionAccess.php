<?php

declare(strict_types=1);

namespace Drupal\support_ticket\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\support_ticket\TicketStatusService;

/**
 * Access check for the ticket status transition form.
 */
class TicketTransitionAccess implements AccessInterface {

  public function __construct(
    protected TicketStatusService $statusService,
  ) {}

  /**
   * Checks access for the transition route.
   */
  public function access(NodeInterface $node, AccountInterface $account): AccessResult {
    if ($node->bundle() !== 'ticket') {
      return AccessResult::forbidden()->addCacheableDependency($node);
    }
    $allowed = $this->statusService->canUserTransitionTicket($account, $node);
    return AccessResult::allowedIf($allowed)
      ->addCacheableDependency($node)
      ->cachePerPermissions()
      ->cachePerUser();
  }

}
