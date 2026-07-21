<?php

declare(strict_types=1);

namespace Drupal\support_ticket;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Workflow status transitions and terminal-state rules for ticket nodes.
 */
class TicketStatusService {

  public const STATUS_OPEN = 'open';

  public const STATUS_IN_PROGRESS = 'in_progress';

  public const STATUS_RESOLVED = 'resolved';

  public const STATUS_CLOSED = 'closed';

  public const STATUS_CANCELLED = 'cancelled';

  /**
   * Statuses with no outgoing transitions.
   */
  private const TERMINAL_STATUSES = [
    self::STATUS_CLOSED,
    self::STATUS_CANCELLED,
  ];

  /**
   * Allowed transitions keyed by current status.
   */
  private const TRANSITION_MAP = [
    self::STATUS_OPEN => [
      self::STATUS_IN_PROGRESS,
      self::STATUS_CANCELLED,
    ],
    self::STATUS_IN_PROGRESS => [
      self::STATUS_RESOLVED,
      self::STATUS_CANCELLED,
    ],
    self::STATUS_RESOLVED => [
      self::STATUS_CLOSED,
    ],
  ];

  /**
   * Returns all workflow status values.
   *
   * @return string[]
   *   Status machine names.
   */
  public function getStatuses(): array {
    return [
      self::STATUS_OPEN,
      self::STATUS_IN_PROGRESS,
      self::STATUS_RESOLVED,
      self::STATUS_CLOSED,
      self::STATUS_CANCELLED,
    ];
  }

  /**
   * Determines whether a status is terminal (closed or cancelled).
   */
  public function isTerminal(string $status): bool {
    return in_array($status, self::TERMINAL_STATUSES, TRUE);
  }

  /**
   * Returns valid target statuses for a given current status.
   *
   * @return string[]
   *   Allowed target status values; empty for terminal or unknown statuses.
   */
  public function getAllowedTargetStatuses(string $from_status): array {
    return self::TRANSITION_MAP[$from_status] ?? [];
  }

  /**
   * Checks whether a transition is allowed by the state machine.
   */
  public function isValidTransition(string $from_status, string $to_status): bool {
    if ($from_status === $to_status) {
      return FALSE;
    }
    if ($this->isTerminal($from_status)) {
      return FALSE;
    }
    return in_array($to_status, $this->getAllowedTargetStatuses($from_status), TRUE);
  }

  /**
   * Reads the workflow status from a ticket node.
   */
  public function getTicketStatus(NodeInterface $ticket): ?string {
    if ($ticket->bundle() !== 'ticket' || !$ticket->hasField('field_ticket_status')) {
      return NULL;
    }
    if ($ticket->get('field_ticket_status')->isEmpty()) {
      return NULL;
    }
    return $ticket->get('field_ticket_status')->value;
  }

  /**
   * Determines whether a user may transition a ticket to a target status.
   */
  public function canTransition(AccountInterface $account, NodeInterface $ticket, string $to_status): bool {
    $from_status = $this->getTicketStatus($ticket);
    if ($from_status === NULL) {
      return FALSE;
    }
    if (!$this->isValidTransition($from_status, $to_status)) {
      return FALSE;
    }
    return $this->canUserTransitionTicket($account, $ticket);
  }

  /**
   * Determines whether a user may perform any transition on a ticket.
   */
  public function canUserTransitionTicket(AccountInterface $account, NodeInterface $ticket): bool {
    $current_status = $this->getTicketStatus($ticket);
    if ($current_status === NULL || $this->isTerminal($current_status)) {
      return FALSE;
    }
    if ($account->hasRole('administrator')) {
      return TRUE;
    }
    if ($account->hasRole('reporter')) {
      return FALSE;
    }
    if ($account->hasRole('agent')) {
      return $account->hasPermission('transition scoped tickets')
        && TicketScopeHelper::isAgentScopedTicket($account, $ticket);
    }
    return FALSE;
  }

  /**
   * Detects concurrent modification since a form was built.
   *
   * Returns TRUE when storage status differs from the expected value or the
   * ticket is now in a terminal state.
   */
  public function isStatusStale(NodeInterface $ticket, string $expected_status): bool {
    $current_status = $this->getTicketStatus($ticket);
    if ($current_status === NULL) {
      return TRUE;
    }
    if ($current_status !== $expected_status) {
      return TRUE;
    }
    return $this->isTerminal($current_status);
  }

}
