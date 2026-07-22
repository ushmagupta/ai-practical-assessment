<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Functional;

/**
 * Authentication and authorization functional tests (P0).
 *
 * @group support_ticket
 */
class TicketAuthAndAccessFunctionalTest extends SupportTicketFunctionalTestBase {

  /**
   * Anonymous users are redirected to login from /tickets (EC-12).
   */
  public function testAnonymousTicketsRedirectToLogin(): void {
    $this->drupalGet('/tickets');
    $this->assertSession()->addressEquals('/user/login');
  }

  /**
   * Authenticated users can reach the ticket list.
   */
  public function testAuthenticatedUserReachesTicketList(): void {
    $reporter = $this->createRoleUser(['reporter']);
    $this->drupalLogin($reporter);
    $this->drupalGet('/tickets');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Tickets');
  }

  /**
   * Reporter cannot view another reporter's ticket (EC-6).
   */
  public function testReporterDeniedOtherReporterTicket(): void {
    $owner = $this->createRoleUser(['reporter'], 'owner_reporter');
    $other = $this->createRoleUser(['reporter'], 'other_reporter');
    $ticket = $this->createTicket([
      'title' => 'Owner only ticket',
      'uid' => $owner->id(),
    ]);

    $this->drupalLogin($other);
    $this->drupalGet('/node/' . $ticket->id());
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Reporter detail page omits assignee from output (EC-7).
   */
  public function testReporterDetailOmitsAssignee(): void {
    $agent = $this->createRoleUser(['agent'], 'detail_agent');
    $reporter = $this->createRoleUser(['reporter'], 'detail_reporter');
    $ticket = $this->createTicket([
      'title' => 'Assigned reporter ticket',
      'uid' => $reporter->id(),
      'field_assigned_to' => $agent->id(),
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains($agent->getAccountName());
    $this->assertSession()->pageTextNotContains('Assigned to');
  }

  /**
   * Agent is denied access to another agent's assigned ticket (EC-8).
   */
  public function testAgentDeniedOutsideQueue(): void {
    $agent_a = $this->createRoleUser(['agent'], 'agent_a');
    $agent_b = $this->createRoleUser(['agent'], 'agent_b');
    $ticket = $this->createTicket([
      'title' => 'Agent B ticket',
      'field_assigned_to' => $agent_b->id(),
    ]);

    $this->drupalLogin($agent_a);
    $this->drupalGet('/node/' . $ticket->id());
    $this->assertSession()->statusCodeEquals(403);
  }

}
