<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Functional;

use Drupal\comment\Entity\Comment;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Shared bootstrap for support_ticket Functional tests.
 */
abstract class SupportTicketFunctionalTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'field',
    'filter',
    'text',
    'options',
    'comment',
    'views',
    'path',
    'support_ticket',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->assertTrue(
      \Drupal::moduleHandler()->moduleExists('support_ticket'),
      'The support_ticket module must be enabled for functional tests.'
    );
    $view = \Drupal::entityTypeManager()->getStorage('view')->load('tickets');
    $this->assertNotNull($view, 'The tickets view must be installed.');
    $this->assertTrue($view->status(), 'The tickets view must be enabled.');
    $this->rebuildAll();
  }
  /**
   * Creates a user with the given roles.
   *
   * @param string[] $roles
   */
  protected function createRoleUser(array $roles, ?string $name = NULL): User {
    $name = $name ?? $this->randomMachineName();
    $user = User::create([
      'name' => $name,
      'mail' => $name . '@example.com',
      'pass' => $name,
      'status' => 1,
    ]);
    foreach ($roles as $role) {
      $user->addRole($role);
    }
    $user->save();
    return User::load($user->id());
  }

  /**
   * Creates a ticket node with optional field overrides.
   *
   * @param array<string, mixed> $values
   */
  protected function createTicket(array $values = []): Node {
    $owner = $values['uid'] ?? $this->rootUser->id();
    $node = Node::create([
      'type' => 'ticket',
      'title' => $values['title'] ?? 'Test ticket',
      'status' => $values['status'] ?? 1,
      'uid' => $owner,
    ]);
    $node->set('field_ticket_type', $values['field_ticket_type'] ?? 'technical');
    $node->set('field_ticket_status', $values['field_ticket_status'] ?? 'open');
    $node->set('field_priority', $values['field_priority'] ?? 'medium');
    if (array_key_exists('field_assigned_to', $values)) {
      $node->set('field_assigned_to', $values['field_assigned_to']);
    }
    if (isset($values['field_description'])) {
      $node->set('field_description', $values['field_description']);
    }
    $node->save();
    return Node::load($node->id());
  }

  /**
   * Creates a comment on a ticket.
   */
  protected function createComment(Node $ticket, User $author, string $message = 'Test comment'): Comment {
    $comment = Comment::create([
      'entity_type' => 'node',
      'entity_id' => $ticket->id(),
      'field_name' => 'comment',
      'comment_type' => 'comment',
      'subject' => 'Comment',
      'comment_body' => $message,
      'uid' => $author->id(),
      'status' => Comment::PUBLISHED,
    ]);
    $comment->save();
    return Comment::load($comment->id());
  }

  /**
   * Submits the ticket transition form for a target status.
   */
  protected function submitTransition(Node $ticket, string $target_status): void {
    $this->drupalGet('/ticket/' . $ticket->id() . '/transition');
    $this->submitForm([
      'target_status' => $target_status,
    ], 'Change status');
  }

  /**
   * Returns the autocomplete value for an entity reference user field.
   */
  protected function userAutocompleteValue(User $user): string {
    return $user->getAccountName() . ' (' . $user->id() . ')';
  }

}
