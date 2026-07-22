<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Functional;

/**
 * Reporter assignee field hidden on ticket detail (FR-19).
 *
 * @group support_ticket
 */
class TicketAssigneeRenderFunctionalTest extends SupportTicketFunctionalTestBase {

  /**
   * Reporter detail omits assignee when ticket is unassigned.
   */
  public function testReporterDetailOmitsUnassignedAssignee(): void {
    $reporter = $this->createRoleUser(['reporter'], 'unassigned_reporter');
    $ticket = $this->createTicket([
      'title' => 'Unassigned reporter ticket',
      'uid' => $reporter->id(),
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Assigned to');
  }

  /**
   * Reporter detail omits assignee when ticket is assigned to an agent.
   */
  public function testReporterDetailOmitsAssignedAssignee(): void {
    $agent = $this->createRoleUser(['agent'], 'assigned_agent');
    $reporter = $this->createRoleUser(['reporter'], 'assigned_reporter');
    $ticket = $this->createTicket([
      'title' => 'Assigned reporter ticket',
      'uid' => $reporter->id(),
      'field_assigned_to' => $agent->id(),
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Assigned to');
    $this->assertSession()->pageTextNotContains($agent->getAccountName());
  }

}
