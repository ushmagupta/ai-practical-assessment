<?php

declare(strict_types=1);

namespace Drupal\support_ticket;

use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Drupal\views\Plugin\views\query\Sql as ViewsSqlQuery;

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
  public function canView(AccountInterface $account, NodeInterface $ticket): bool {
    if ($ticket->bundle() !== 'ticket') {
      return FALSE;
    }
    if ($account->hasRole('administrator')) {
      return TRUE;
    }
    if ($account->hasRole('agent')) {
      return $account->hasPermission('manage scoped tickets')
        && TicketScopeHelper::isAgentScopedTicket($account, $ticket);
    }
    if ($account->hasRole('reporter')) {
      return (int) $ticket->getOwnerId() === (int) $account->id();
    }
    return FALSE;
  }

  /**
   * Determines whether a user may update ticket fields.
   */
  public function canUpdate(AccountInterface $account, NodeInterface $ticket): bool {
    if (!$this->canView($account, $ticket)) {
      return FALSE;
    }
    if ($this->isTicketTerminal($ticket)) {
      return FALSE;
    }
    if ($account->hasRole('administrator')) {
      return TRUE;
    }
    if ($account->hasRole('agent')) {
      return $account->hasPermission('manage scoped tickets');
    }
    if ($account->hasRole('reporter')) {
      return (int) $ticket->getOwnerId() === (int) $account->id();
    }
    return FALSE;
  }

  /**
   * Determines whether a user may delete a ticket.
   */
  public function canDelete(AccountInterface $account, NodeInterface $ticket): bool {
    return $account->hasRole('administrator') && $ticket->bundle() === 'ticket';
  }

  /**
   * Determines whether a user may set or change the assignee.
   */
  public function canAssign(AccountInterface $account, NodeInterface $ticket): bool {
    if ($account->hasRole('reporter')) {
      return FALSE;
    }
    if (!$this->canView($account, $ticket)) {
      return FALSE;
    }
    if ($this->isTicketTerminal($ticket)) {
      return FALSE;
    }
    return $account->hasRole('administrator')
      || ($account->hasRole('agent') && $account->hasPermission('manage scoped tickets'));
  }

  /**
   * Determines whether a user may add a comment to a ticket.
   */
  public function canAddComment(AccountInterface $account, NodeInterface $ticket): bool {
    if (!$this->canView($account, $ticket)) {
      return FALSE;
    }
    return !$this->isTicketTerminal($ticket);
  }

  /**
   * Determines whether a user may edit a comment.
   */
  public function canEditComment(AccountInterface $account, CommentInterface $comment): bool {
    if ((int) $comment->getOwnerId() !== (int) $account->id()) {
      return FALSE;
    }
    $ticket = $comment->getCommentedEntity();
    if (!$ticket instanceof NodeInterface || $ticket->bundle() !== 'ticket') {
      return FALSE;
    }
    if (!$this->canView($account, $ticket)) {
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
   * Applies role-based list scoping to an entity query.
   */
  public function applyListScope(QueryInterface $query, AccountInterface $account): void {
    if ($account->hasRole('administrator')) {
      return;
    }
    if ($account->hasRole('agent')) {
      $this->applyAgentListScopeToEntityQuery($query, $account);
      return;
    }
    if ($account->hasRole('reporter')) {
      $query->condition('uid', $account->id());
      return;
    }
    $query->condition('nid', 0);
  }

  /**
   * Applies role-based list scoping to the tickets View SQL query.
   */
  public function applyListScopeToViewsQuery(ViewsSqlQuery $query, AccountInterface $account): void {
    $base_alias = $query->ensureTable('node_field_data');
    if ($account->hasRole('administrator')) {
      return;
    }
    if ($account->hasRole('agent')) {
      $this->applyAgentListScopeToViewsQuery($query, $account);
      return;
    }
    if ($account->hasRole('reporter')) {
      $query->addWhere(0, "$base_alias.uid", $account->id());
      return;
    }
    $query->addWhere(0, "$base_alias.nid", 0);
  }

  /**
   * Applies Agent queue scoping to an entity query.
   */
  protected function applyAgentListScopeToEntityQuery(QueryInterface $query, AccountInterface $account): void {
    $or = $query->orConditionGroup()
      ->condition('field_assigned_to', $account->id())
      ->condition('field_assigned_to', NULL, 'IS NULL');
    $query->condition($or);
  }

  /**
   * Applies Agent queue scoping to a Views SQL query.
   */
  protected function applyAgentListScopeToViewsQuery(ViewsSqlQuery $query, AccountInterface $account): void {
    $assigned_alias = $query->ensureTable('node__field_assigned_to');
    $or = $query->getConnection()->condition('OR');
    $or->isNull("$assigned_alias.field_assigned_to_target_id");
    $or->condition("$assigned_alias.field_assigned_to_target_id", $account->id());
    $query->addWhere(0, $or);
  }

  /**
   * Removes ticket fields from render arrays per role rules (FR-19).
   *
   * @param array<string, mixed> $build
   *   The entity view render array.
   */
  public function filterRenderedTicket(array &$build, AccountInterface $account): void {
    if ($account->hasRole('reporter')) {
      unset($build['field_assigned_to']);
    }
  }

  /**
   * Whether a ticket is in a terminal workflow status.
   */
  protected function isTicketTerminal(NodeInterface $ticket): bool {
    $status = $this->statusService->getTicketStatus($ticket);
    return $status === NULL || $this->statusService->isTerminal($status);
  }

}
