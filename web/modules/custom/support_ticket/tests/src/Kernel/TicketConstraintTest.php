<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Kernel;

use Drupal\node\Entity\Node;

/**
 * Kernel tests for custom validation constraints.
 *
 * @group support_ticket
 */
class TicketConstraintTest extends SupportTicketKernelTestBase {

  /**
   * Title length constraint passes within limit and fails above.
   */
  public function testTicketTitleLength(): void {
    $ticket = $this->createTicket(['title' => str_repeat('a', 100)]);
    $this->assertCount(0, $ticket->validate());

    $ticket->setTitle(str_repeat('a', 101));
    $violations = $ticket->validate();
    $this->assertGreaterThan(0, $violations->count());
  }

  /**
   * Description length constraint passes within limit and fails above.
   */
  public function testTicketDescriptionLength(): void {
    $ticket = $this->createTicket(['field_description' => str_repeat('a', 1000)]);
    $this->assertCount(0, $ticket->validate());

    $ticket->set('field_description', str_repeat('a', 1001));
    $violations = $ticket->validate();
    $this->assertGreaterThan(0, $violations->count());
  }

  /**
   * Assignee must reference an Agent user.
   */
  public function testTicketAssigneeIsAgent(): void {
    $reporter = $this->createUser(['reporter']);
    $agent = $this->createUser(['agent']);

    $ticket = $this->createTicket(['field_assigned_to' => $agent->id()]);
    $this->assertCount(0, $ticket->validate());

    $ticket->set('field_assigned_to', $reporter->id());
    $violations = $ticket->validate();
    $this->assertGreaterThan(0, $violations->count());
  }

  /**
   * Invalid status transitions are rejected.
   */
  public function testTicketStatusTransition(): void {
    $ticket = $this->createTicket(['field_ticket_status' => 'open']);
    $ticket->set('field_ticket_status', 'in_progress');
    $this->assertCount(0, $ticket->validate());

    $ticket = $this->createTicket(['field_ticket_status' => 'open']);
    $ticket->set('field_ticket_status', 'closed');
    $violations = $ticket->validate();
    $this->assertGreaterThan(0, $violations->count());
  }

  /**
   * Terminal tickets cannot be edited.
   */
  public function testTicketNotTerminalOnTicket(): void {
    $ticket = $this->createTicket(['field_ticket_status' => 'closed']);
    $loaded = Node::load($ticket->id());
    $loaded->setTitle('Updated title');
    $violations = $loaded->validate();
    $this->assertGreaterThan(0, $violations->count());
  }

  /**
   * Comments on terminal tickets are rejected.
   */
  public function testTicketNotTerminalOnComment(): void {
    $reporter = $this->createUser(['reporter']);
    $ticket = $this->createTicket([
      'uid' => $reporter->id(),
      'field_ticket_status' => 'closed',
    ]);

    $comment = $this->container->get('entity_type.manager')
      ->getStorage('comment')
      ->create([
        'entity_type' => 'node',
        'entity_id' => $ticket->id(),
        'field_name' => 'comment',
        'comment_type' => 'comment',
        'subject' => 'Comment',
        'comment_body' => 'Should fail',
        'uid' => $reporter->id(),
        'status' => 1,
      ]);
    $violations = $comment->validate();
    $this->assertGreaterThan(0, $violations->count());
  }

  /**
   * Comment message length constraint passes and fails appropriately.
   */
  public function testCommentMessageLength(): void {
    $reporter = $this->createUser(['reporter']);
    $ticket = $this->createTicket([
      'uid' => $reporter->id(),
      'field_ticket_status' => 'open',
    ]);
    $comment = $this->createComment($ticket, $reporter, str_repeat('a', 1000));
    $this->assertCount(0, $comment->validate());

    $comment->set('comment_body', str_repeat('a', 1001));
    $violations = $comment->validate();
    $this->assertGreaterThan(0, $violations->count());
  }

}
