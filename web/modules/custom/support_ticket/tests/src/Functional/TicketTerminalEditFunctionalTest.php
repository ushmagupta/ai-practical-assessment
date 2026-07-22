<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Functional;

/**
 * Terminal ticket edit access for reporters (closed and cancelled).
 *
 * @group support_ticket
 */
class TicketTerminalEditFunctionalTest extends SupportTicketFunctionalTestBase {

  /**
   * Reporter cannot access edit form on a cancelled ticket.
   */
  public function testReporterEditDeniedOnCancelledTicket(): void {
    $reporter = $this->createRoleUser(['reporter'], 'cancelled_edit_reporter');
    $ticket = $this->createTicket([
      'title' => 'Cancelled reporter ticket',
      'uid' => $reporter->id(),
      'field_ticket_status' => 'cancelled',
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/node/' . $ticket->id());
    $this->assertSession()->linkNotExists('Edit');
  }

  /**
   * Reporter cannot access edit form on a closed ticket.
   */
  public function testReporterEditDeniedOnClosedTicket(): void {
    $reporter = $this->createRoleUser(['reporter'], 'closed_edit_reporter');
    $ticket = $this->createTicket([
      'title' => 'Closed reporter ticket',
      'uid' => $reporter->id(),
      'field_ticket_status' => 'closed',
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/node/' . $ticket->id());
    $this->assertSession()->linkNotExists('Edit');
  }

}
