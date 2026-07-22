<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Kernel;

use Drupal\support_ticket\TicketAccessService;

/**
 * Kernel tests for TicketAccessService.
 *
 * @group support_ticket
 */
class TicketAccessServiceTest extends SupportTicketKernelTestBase {

  protected TicketAccessService $accessService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->accessService = $this->container->get('support_ticket.access');
  }

  /**
   * Admin has full ticket access including delete.
   */
  public function testAdminAccess(): void {
    $admin = $this->createUser(['administrator']);
    $agent = $this->createUser(['agent']);
    $ticket = $this->createTicket([
      'field_assigned_to' => $agent->id(),
      'field_ticket_status' => 'open',
    ]);

    $this->assertTrue($this->accessService->canView($admin, $ticket));
    $this->assertTrue($this->accessService->canUpdate($admin, $ticket));
    $this->assertTrue($this->accessService->canDelete($admin, $ticket));
    $this->assertTrue($this->accessService->canAssign($admin, $ticket));
    $this->assertTrue($this->accessService->canAddComment($admin, $ticket));
  }

  /**
   * Agent can access assigned-to-self and unassigned tickets only.
   */
  public function testAgentQueueScope(): void {
    $agent = $this->createUser(['agent']);
    $other_agent = $this->createUser(['agent']);

    $unassigned = $this->createTicket(['field_ticket_status' => 'open']);
    $this->assertTrue($this->accessService->canView($agent, $unassigned));
    $this->assertTrue($this->accessService->canUpdate($agent, $unassigned));

    $assigned_self = $this->createTicket([
      'field_assigned_to' => $agent->id(),
      'field_ticket_status' => 'open',
    ]);
    $this->assertTrue($this->accessService->canView($agent, $assigned_self));

    $assigned_other = $this->createTicket([
      'field_assigned_to' => $other_agent->id(),
      'field_ticket_status' => 'open',
    ]);
    $this->assertFalse($this->accessService->canView($agent, $assigned_other));
    $this->assertFalse($this->accessService->canUpdate($agent, $assigned_other));
    $this->assertFalse($this->accessService->canAssign($agent, $assigned_other));
    $this->assertFalse($this->accessService->canAddComment($agent, $assigned_other));
    $this->assertFalse($this->accessService->canDelete($agent, $assigned_other));
  }

  /**
   * Reporter can access own tickets only.
   */
  public function testReporterOwnTicketsOnly(): void {
    $reporter = $this->createUser(['reporter']);
    $other_reporter = $this->createUser(['reporter']);

    $own = $this->createTicket([
      'uid' => $reporter->id(),
      'field_ticket_status' => 'open',
    ]);
    $other = $this->createTicket([
      'uid' => $other_reporter->id(),
      'field_ticket_status' => 'open',
    ]);

    $this->assertTrue($this->accessService->canView($reporter, $own));
    $this->assertTrue($this->accessService->canUpdate($reporter, $own));
    $this->assertFalse($this->accessService->canView($reporter, $other));
    $this->assertFalse($this->accessService->canUpdate($reporter, $other));
    $this->assertFalse($this->accessService->canAssign($reporter, $own));
    $this->assertFalse($this->accessService->canDelete($reporter, $own));
  }

  /**
   * Terminal tickets deny updates, assignment, and comments.
   */
  public function testTerminalTicketWriteDenial(): void {
    $admin = $this->createUser(['administrator']);
    $ticket = $this->createTicket(['field_ticket_status' => 'closed']);

    $this->assertTrue($this->accessService->canView($admin, $ticket));
    $this->assertFalse($this->accessService->canUpdate($admin, $ticket));
    $this->assertFalse($this->accessService->canAssign($admin, $ticket));
    $this->assertFalse($this->accessService->canAddComment($admin, $ticket));
  }

  /**
   * Cancelled tickets deny reporter updates (terminal state).
   */
  public function testCancelledTicketReporterUpdateDenied(): void {
    $reporter = $this->createUser(['reporter']);
    $ticket = $this->createTicket([
      'uid' => $reporter->id(),
      'field_ticket_status' => 'cancelled',
    ]);

    $this->assertTrue($this->accessService->canView($reporter, $ticket));
    $this->assertFalse($this->accessService->canUpdate($reporter, $ticket));
  }

  /**
   * Comment access follows role and terminal rules.
   */
  public function testCommentAccess(): void {
    $reporter = $this->createUser(['reporter']);
    $other = $this->createUser(['reporter']);
    $ticket = $this->createTicket([
      'uid' => $reporter->id(),
      'field_ticket_status' => 'open',
    ]);
    $comment = $this->createComment($ticket, $reporter);

    $this->assertTrue($this->accessService->canEditComment($reporter, $comment));
    $this->assertFalse($this->accessService->canEditComment($other, $comment));

    $closed_ticket = $this->createTicket([
      'uid' => $reporter->id(),
      'field_ticket_status' => 'open',
    ]);
    $closed_comment = $this->createComment($closed_ticket, $reporter);
    $closed_ticket->set('field_ticket_status', 'closed');
    $closed_ticket->save();
    $closed_comment = $this->container->get('entity_type.manager')
      ->getStorage('comment')
      ->load($closed_comment->id());
    $this->assertFalse($this->accessService->canEditComment($reporter, $closed_comment));
  }

}
