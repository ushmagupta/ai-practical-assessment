<?php

declare(strict_types=1);

namespace Drupal\support_ticket;

use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Visibility and write permission rules for tickets and comments.
 */
class TicketAccessService {

  public function __construct(
    protected TicketStatusService $statusService,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Determines whether a user may view a ticket.
   */
  public function canView(UserInterface $user, NodeInterface $ticket): bool {
    if ($ticket->bundle() !== 'ticket') {
      return FALSE;
    }
    if ($user->hasRole('administrator')) {
      return TRUE;
    }
    if ($user->hasRole('agent')) {
      return $this->isAgentScopedTicket($user, $ticket);
    }
    if ($user->hasRole('reporter')) {
      return (int) $ticket->getOwnerId() === (int) $user->id();
    }
    return FALSE;
  }

  /**
   * Determines whether a user may update ticket fields.
   */
  public function canUpdate(UserInterface $user, NodeInterface $ticket): bool {
    if (!$this->canView($user, $ticket)) {
      return FALSE;
    }
    if ($this->isTicketTerminal($ticket)) {
      return FALSE;
    }
    if ($user->hasRole('administrator') || $user->hasRole('agent')) {
      return TRUE;
    }
    if ($user->hasRole('reporter')) {
      return (int) $ticket->getOwnerId() === (int) $user->id();
    }
    return FALSE;
  }

  /**
   * Determines whether a user may delete a ticket.
   */
  public function canDelete(UserInterface $user, NodeInterface $ticket): bool {
    return $user->hasRole('administrator') && $ticket->bundle() === 'ticket';
  }

  /**
   * Determines whether a user may set or change the assignee.
   */
  public function canAssign(UserInterface $user, NodeInterface $ticket): bool {
    if ($user->hasRole('reporter')) {
      return FALSE;
    }
    if (!$this->canView($user, $ticket)) {
      return FALSE;
    }
    if ($this->isTicketTerminal($ticket)) {
      return FALSE;
    }
    return $user->hasRole('administrator') || $user->hasRole('agent');
  }

  /**
   * Determines whether a user may add a comment to a ticket.
   */
  public function canAddComment(UserInterface $user, NodeInterface $ticket): bool {
    if (!$this->canView($user, $ticket)) {
      return FALSE;
    }
    return !$this->isTicketTerminal($ticket);
  }

  /**
   * Determines whether a user may edit a comment.
   */
  public function canEditComment(UserInterface $user, CommentInterface $comment): bool {
    if ((int) $comment->getOwnerId() !== (int) $user->id()) {
      return FALSE;
    }
    $ticket = $comment->getCommentedEntity();
    if (!$ticket instanceof NodeInterface || $ticket->bundle() !== 'ticket') {
      return FALSE;
    }
    if (!$this->canView($user, $ticket)) {
      return FALSE;
    }
    return !$this->isTicketTerminal($ticket);
  }

  /**
   * Whether user deletion is allowed (FR-8, FR-9 guards).
   */
  public function userDeletionAllowed(UserInterface $account, AccountInterface $actor): bool {
    if ($actor->hasRole('administrator') && (int) $actor->id() === (int) $account->id()) {
      return FALSE;
    }
    if ($this->isUserAssigneeOnAnyTicket($account)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Whether a user is assigned to any ticket.
   */
  public function isUserAssigneeOnAnyTicket(UserInterface $user): bool {
    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'ticket')
      ->condition('field_assigned_to', $user->id())
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    return !empty($nids);
  }

  /**
   * Whether a ticket is in a terminal workflow status.
   */
  protected function isTicketTerminal(NodeInterface $ticket): bool {
    $status = $this->statusService->getTicketStatus($ticket);
    return $status === NULL || $this->statusService->isTerminal($status);
  }

  /**
   * Whether an Agent may act on a ticket (assigned to self or unassigned).
   */
  protected function isAgentScopedTicket(UserInterface $agent, NodeInterface $ticket): bool {
    if (!$ticket->hasField('field_assigned_to') || $ticket->get('field_assigned_to')->isEmpty()) {
      return TRUE;
    }
    return (int) $ticket->get('field_assigned_to')->target_id === (int) $agent->id();
  }

}
