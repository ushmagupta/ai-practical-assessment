<?php

declare(strict_types=1);

namespace Drupal\support_ticket;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Shared ticket queue scoping helpers.
 */
final class TicketScopeHelper {

  /**
   * Whether an Agent may act on a ticket (assigned to self or unassigned).
   */
  public static function isAgentScopedTicket(AccountInterface $agent, NodeInterface $ticket): bool {
    if (!$ticket->hasField('field_assigned_to') || $ticket->get('field_assigned_to')->isEmpty()) {
      return TRUE;
    }
    return (int) $ticket->get('field_assigned_to')->target_id === (int) $agent->id();
  }

}
