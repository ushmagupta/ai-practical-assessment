<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Kernel;

use Drupal\node\Entity\Node;
use Drupal\support_ticket\TicketStatusService;
use Drupal\user\Entity\User;

/**
 * Kernel tests for TicketStatusService.
 *
 * @group support_ticket
 */
class TicketStatusServiceTest extends SupportTicketKernelTestBase {

  protected TicketStatusService $statusService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->statusService = $this->container->get('support_ticket.status');
  }

  /**
   * Data provider for allowed transitions.
   *
   * @return array<string, array{string, string}>
   */
  public static function allowedTransitionProvider(): array {
    return [
      'open to in_progress' => ['open', 'in_progress'],
    ];
  }

  /**
   * @dataProvider allowedTransitionProvider
   */
  public function testAllowedTransitions(string $from, string $to): void {
    $this->assertTrue($this->statusService->isValidTransition($from, $to));
    $this->assertContains($to, $this->statusService->getAllowedTargetStatuses($from));
  }

  /**
   * Data provider for rejected transitions.
   *
   * @return array<string, array{string, string}>
   */
  public static function rejectedTransitionProvider(): array {
    return [
      'open to closed' => ['open', 'closed'],
    ];
  }

  /**
   * @dataProvider rejectedTransitionProvider
   */
  public function testRejectedTransitions(string $from, string $to): void {
    $this->assertFalse($this->statusService->isValidTransition($from, $to));
  }

  /**
   * Terminal statuses must not expose outgoing transitions.
   */
  public function testTerminalStatusesHaveNoOutgoingTransitions(): void {
    $this->assertTrue($this->statusService->isTerminal('closed'));
    $this->assertTrue($this->statusService->isTerminal('cancelled'));
    $this->assertFalse($this->statusService->isTerminal('open'));

    foreach ($this->statusService->getStatuses() as $status) {
      if ($this->statusService->isTerminal($status)) {
        $this->assertSame([], $this->statusService->getAllowedTargetStatuses($status));
        foreach ($this->statusService->getStatuses() as $target) {
          $this->assertFalse(
            $this->statusService->isValidTransition($status, $target),
            "Terminal status $status must not transition to $target."
          );
        }
      }
    }
  }

  /**
   * Admin may transition any non-terminal ticket.
   */
  public function testAdminCanTransitionAnyNonTerminalTicket(): void {
    $admin = $this->createUser(['administrator']);
    $agent = $this->createUser(['agent']);
    $ticket = $this->createTicket([
      'field_ticket_status' => 'open',
      'field_assigned_to' => $agent->id(),
    ]);

    $this->assertTrue(
      $this->statusService->canTransition($admin, $ticket, 'in_progress')
    );
    $this->assertTrue($this->statusService->canUserTransitionTicket($admin, $ticket));
  }

  /**
   * Agent may transition unassigned and self-assigned tickets.
   */
  public function testAgentCanTransitionScopedTickets(): void {
    $agent = $this->createUser(['agent']);
    $other_agent = $this->createUser(['agent']);

    $unassigned = $this->createTicket(['field_ticket_status' => 'open']);
    $this->assertTrue(
      $this->statusService->canTransition($agent, $unassigned, 'in_progress')
    );

    $assigned_to_self = $this->createTicket([
      'field_ticket_status' => 'open',
      'field_assigned_to' => $agent->id(),
    ]);
    $this->assertTrue(
      $this->statusService->canTransition($agent, $assigned_to_self, 'in_progress')
    );

    $assigned_to_other = $this->createTicket([
      'field_ticket_status' => 'open',
      'field_assigned_to' => $other_agent->id(),
    ]);
    $this->assertFalse(
      $this->statusService->canTransition($agent, $assigned_to_other, 'in_progress')
    );
    $this->assertFalse(
      $this->statusService->canUserTransitionTicket($agent, $assigned_to_other)
    );
  }

  /**
   * Reporter cannot perform any transition.
   */
  public function testReporterDeniedAllTransitions(): void {
    $reporter = $this->createUser(['reporter']);
    $ticket = $this->createTicket([
      'uid' => $reporter->id(),
      'field_ticket_status' => 'open',
    ]);

    $this->assertFalse(
      $this->statusService->canTransition($reporter, $ticket, 'in_progress')
    );
    $this->assertFalse($this->statusService->canUserTransitionTicket($reporter, $ticket));
  }

  /**
   * Stale status is detected when storage differs from form build value.
   */
  public function testStaleStatusDetection(): void {
    $ticket = $this->createTicket(['field_ticket_status' => 'in_progress']);

    $this->assertFalse($this->statusService->isStatusStale($ticket, 'in_progress'));

    $ticket->set('field_ticket_status', 'resolved');
    $ticket->save();
    $this->assertTrue($this->statusService->isStatusStale($ticket, 'in_progress'));
  }

  /**
   * Stale status is detected when ticket became terminal since form build.
   */
  public function testStaleStatusWhenTerminal(): void {
    $ticket = $this->createTicket(['field_ticket_status' => 'closed']);
    $this->assertTrue($this->statusService->isStatusStale($ticket, 'closed'));
  }

  /**
   * Workflow status is read from field_ticket_status, not node publish status.
   */
  public function testGetTicketStatusUsesWorkflowField(): void {
    $ticket = $this->createTicket([
      'status' => 1,
      'field_ticket_status' => 'resolved',
    ]);
    $this->assertSame('resolved', $this->statusService->getTicketStatus($ticket));
    $this->assertSame(1, (int) $ticket->get('status')->value);
  }

}
