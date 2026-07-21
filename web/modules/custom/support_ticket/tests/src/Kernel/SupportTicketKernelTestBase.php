<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Kernel;

use Drupal\comment\Entity\Comment;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Shared bootstrap for support_ticket Kernel tests.
 */
abstract class SupportTicketKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'options',
    'node',
    'comment',
    'views',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig([
      'field',
      'filter',
      'node',
      'comment',
      'views',
      'text',
      'user',
    ]);
    $this->container->get('module_installer')->install(['support_ticket']);
  }

  /**
   * Creates a user with the given roles.
   *
   * @param string[] $roles
   */
  protected function createUser(array $roles): User {
    $role_storage = $this->container->get('entity_type.manager')
      ->getStorage('user_role');
    if (in_array('administrator', $roles, TRUE) && !$role_storage->load('administrator')) {
      $role_storage->create([
        'id' => 'administrator',
        'label' => 'Administrator',
        'is_admin' => TRUE,
      ])->save();
    }

    $user = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
      'roles' => $roles,
      'status' => 1,
    ]);
    $user->save();
    return $user;
  }

  /**
   * Creates a ticket node with optional field overrides.
   *
   * @param array<string, mixed> $values
   */
  protected function createTicket(array $values = []): Node {
    $node = Node::create([
      'type' => 'ticket',
      'title' => $values['title'] ?? 'Test ticket',
      'status' => $values['status'] ?? 1,
      'uid' => $values['uid'] ?? 1,
    ]);
    $node->set('field_ticket_type', $values['field_ticket_type'] ?? 'technical');
    $node->set('field_ticket_status', $values['field_ticket_status'] ?? 'open');
    $node->set('field_priority', $values['field_priority'] ?? 'medium');
    if (isset($values['field_assigned_to'])) {
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

}
