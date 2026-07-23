<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Functional;

use Drupal\user\Entity\User;

/**
 * Minimum HTTP smoke tests for support_ticket (P0 + representative P1).
 *
 * @group support_ticket
 */
class TicketSmokeFunctionalTest extends SupportTicketFunctionalTestBase {

  /**
   * Anonymous gate, login redirect, and authenticated list access.
   */
  public function testAuthGateAndListAccess(): void {
    $this->drupalGet('/tickets');
    $path = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_PATH) ?: '';
    $status = $this->getSession()->getStatusCode();
    $this->assertTrue(
      $path === '/user/login' || $status === 403,
      'Anonymous users must be redirected to login or receive access denied.'
    );

    $reporter = $this->createRoleUser(['reporter'], 'smoke_reporter');
    $this->drupalGet('/user/login');
    $this->submitForm([
      'name' => $reporter->getAccountName(),
      'pass' => $reporter->getAccountName(),
    ], 'Log in');
    $this->assertSession()->addressEquals('/tickets');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Tickets');
  }

  /**
   * Admin, Agent, and Reporter list scoping in one install.
   */
  public function testListScopeByRole(): void {
    $reporter = $this->createRoleUser(['reporter'], 'smoke_list_reporter');
    $other_reporter = $this->createRoleUser(['reporter'], 'smoke_list_other');
    $agent = $this->createRoleUser(['agent'], 'smoke_list_agent');
    $other_agent = $this->createRoleUser(['agent'], 'smoke_list_other_agent');

    $this->createTicket(['title' => 'Admin visible ticket', 'uid' => $reporter->id()]);
    $this->createTicket(['title' => 'Unassigned queue ticket']);
    $this->createTicket([
      'title' => 'Agent assigned ticket',
      'field_assigned_to' => $agent->id(),
    ]);
    $this->createTicket([
      'title' => 'Other agent hidden ticket',
      'field_assigned_to' => $other_agent->id(),
    ]);
    $this->createTicket([
      'title' => 'Reporter hidden ticket',
      'uid' => $other_reporter->id(),
    ]);

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/tickets');
    $this->assertSession()->pageTextContains('Admin visible ticket');
    $this->assertSession()->pageTextContains('Other agent hidden ticket');

    $this->drupalLogin($agent);
    $this->drupalGet('/tickets');
    $this->assertSession()->pageTextContains('Unassigned queue ticket');
    $this->assertSession()->pageTextContains('Agent assigned ticket');
    $this->assertSession()->pageTextNotContains('Other agent hidden ticket');

    $this->drupalLogin($reporter);
    $this->drupalGet('/tickets');
    $this->assertSession()->pageTextContains('Admin visible ticket');
    $this->assertSession()->pageTextNotContains('Reporter hidden ticket');
  }

  /**
   * Create defaults and reporter edit of own ticket.
   */
  public function testCreateAndEditTicket(): void {
    $reporter = $this->createRoleUser(['reporter'], 'smoke_edit_reporter');
    $this->drupalLogin($reporter);

    $this->drupalGet('/node/add/ticket');
    $this->submitForm([
      'title[0][value]' => 'Smoke create ticket',
      'field_ticket_type' => 'technical',
    ], 'Save');
    $this->assertSession()->pageTextContains('Smoke create ticket');

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
      'title' => 'Smoke create ticket',
    ]);
    $this->assertCount(1, $nodes);
    /** @var \Drupal\node\Entity\Node $ticket */
    $ticket = reset($nodes);
    $this->assertSame('open', $ticket->get('field_ticket_status')->value);
    $this->assertSame('medium', $ticket->get('field_priority')->value);
    $this->assertTrue($ticket->get('field_assigned_to')->isEmpty());

    $this->drupalGet('/node/' . $ticket->id() . '/edit');
    $this->submitForm([
      'title[0][value]' => 'Smoke updated ticket',
      'field_ticket_type' => 'general',
    ], 'Save');
    $ticket = $this->reloadTicket($ticket);
    $this->assertSame('Smoke updated ticket', $ticket->label());
    $this->assertSame('general', $ticket->get('field_ticket_type')->value);
  }

  /**
   * Happy-path lifecycle via the transition form only.
   */
  public function testLifecycleTransitions(): void {
    $agent = $this->createRoleUser(['agent'], 'smoke_lifecycle_agent');
    $ticket = $this->createTicket([
      'title' => 'Smoke lifecycle ticket',
      'field_assigned_to' => $agent->id(),
    ]);

    $this->drupalLogin($agent);
    $this->submitTransition($ticket, 'in_progress');
    $this->assertSession()->pageTextContains('Ticket status updated.');
    $ticket = $this->reloadTicket($ticket);
    $this->assertSame('in_progress', $ticket->get('field_ticket_status')->value);

    $this->submitTransition($ticket, 'resolved');
    $ticket = $this->reloadTicket($ticket);
    $this->assertSame('resolved', $ticket->get('field_ticket_status')->value);

    $this->drupalLogin($this->rootUser);
    $this->submitTransition($ticket, 'closed');
    $ticket = $this->reloadTicket($ticket);
    $this->assertSame('closed', $ticket->get('field_ticket_status')->value);
  }

  /**
   * Terminal tickets reject edit via HTTP.
   */
  public function testTerminalTicketReadOnly(): void {
    $reporter = $this->createRoleUser(['reporter'], 'smoke_terminal_reporter');
    $ticket = $this->createTicket([
      'title' => 'Smoke closed ticket',
      'uid' => $reporter->id(),
      'field_ticket_status' => 'closed',
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/ticket/' . $ticket->id() . '/transition');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Reporter detail output omits assignee (FR-19).
   */
  public function testReporterAssigneeHidden(): void {
    $agent = $this->createRoleUser(['agent'], 'smoke_detail_agent');
    $reporter = $this->createRoleUser(['reporter'], 'smoke_detail_reporter');
    $ticket = $this->createTicket([
      'title' => 'Smoke assignee ticket',
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
   * Users can add a comment on an accessible non-terminal ticket.
   */
  public function testAddComment(): void {
    $reporter = $this->createRoleUser(['reporter'], 'smoke_comment_reporter');
    $ticket = $this->createTicket([
      'title' => 'Smoke comment ticket',
      'uid' => $reporter->id(),
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id());
    $this->submitForm([
      'comment_body[0][value]' => 'Smoke comment body',
    ], 'Save');
    $this->assertSession()->pageTextContains('Smoke comment body');
  }

  /**
   * User delete is blocked when assignee on a ticket (EC-9).
   */
  public function testUserDeleteBlockedForAssignee(): void {
    $agent = $this->createRoleUser(['agent'], 'smoke_delete_agent');
    $this->createTicket([
      'title' => 'Smoke assignee block ticket',
      'field_assigned_to' => $agent->id(),
    ]);

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/user/' . $agent->id() . '/cancel');
    $this->submitForm([], 'Confirm');
    $this->assertSession()->pageTextContains('User cannot be deleted while assigned to one or more tickets.');
    $this->assertNotNull(User::load($agent->id()));
  }

}
