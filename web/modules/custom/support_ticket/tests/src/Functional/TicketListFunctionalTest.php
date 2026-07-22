<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Functional;

/**
 * Ticket list functional tests (P1 + representative P2).
 *
 * @group support_ticket
 */
class TicketListFunctionalTest extends SupportTicketFunctionalTestBase {

  /**
   * Admin sees all tickets in the list.
   */
  public function testAdminSeesAllTickets(): void {
    $reporter = $this->createRoleUser(['reporter'], 'list_admin_reporter');
    $this->createTicket(['title' => 'Admin visible ticket', 'uid' => $reporter->id()]);
    $this->createTicket(['title' => 'Admin closed ticket', 'field_ticket_status' => 'closed']);

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/tickets');
    $this->assertSession()->pageTextContains('Admin visible ticket');
    $this->assertSession()->pageTextContains('Admin closed ticket');
  }

  /**
   * Agent sees assigned and unassigned tickets only.
   */
  public function testAgentListScope(): void {
    $agent = $this->createRoleUser(['agent'], 'list_agent');
    $other_agent = $this->createRoleUser(['agent'], 'list_other_agent');
    $this->createTicket(['title' => 'Unassigned queue ticket']);
    $this->createTicket([
      'title' => 'My assigned ticket',
      'field_assigned_to' => $agent->id(),
    ]);
    $this->createTicket([
      'title' => 'Other agent hidden ticket',
      'field_assigned_to' => $other_agent->id(),
    ]);

    $this->drupalLogin($agent);
    $this->drupalGet('/tickets');
    $this->assertSession()->pageTextContains('Unassigned queue ticket');
    $this->assertSession()->pageTextContains('My assigned ticket');
    $this->assertSession()->pageTextNotContains('Other agent hidden ticket');
  }

  /**
   * Reporter sees only own tickets.
   */
  public function testReporterListScope(): void {
    $reporter = $this->createRoleUser(['reporter'], 'list_reporter');
    $other = $this->createRoleUser(['reporter'], 'list_other_reporter');
    $this->createTicket(['title' => 'Reporter own ticket', 'uid' => $reporter->id()]);
    $this->createTicket(['title' => 'Reporter hidden ticket', 'uid' => $other->id()]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/tickets');
    $this->assertSession()->pageTextContains('Reporter own ticket');
    $this->assertSession()->pageTextNotContains('Reporter hidden ticket');
  }

  /**
   * Keyword search matches title and description only (A-14).
   */
  public function testKeywordSearchTitleAndDescription(): void {
    $this->createTicket([
      'title' => 'UniqueAlphaTitle',
      'field_description' => 'plain description',
    ]);
    $this->createTicket([
      'title' => 'Other ticket',
      'field_description' => 'UniqueBetaDescription',
      'field_ticket_status' => 'in_progress',
    ]);
    $this->createTicket([
      'title' => 'No match ticket',
      'field_ticket_status' => 'open',
    ]);

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/tickets', ['query' => ['search' => 'UniqueAlphaTitle']]);
    $this->assertSession()->pageTextContains('UniqueAlphaTitle');
    $this->assertSession()->pageTextNotContains('No match ticket');

    $this->drupalGet('/tickets', ['query' => ['search' => 'UniqueBetaDescription']]);
    $this->assertSession()->pageTextContains('Other ticket');
    $this->assertSession()->pageTextNotContains('No match ticket');
  }

  /**
   * Representative exposed filters work for status and priority.
   */
  public function testListFilters(): void {
    $this->createTicket([
      'title' => 'Open priority low',
      'field_ticket_status' => 'open',
      'field_priority' => 'low',
    ]);
    $this->createTicket([
      'title' => 'Resolved priority high',
      'field_ticket_status' => 'resolved',
      'field_priority' => 'high',
    ]);

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/tickets', [
      'query' => [
        'ticket_status' => 'resolved',
      ],
    ]);
    $this->assertSession()->pageTextContains('Resolved priority high');
    $this->assertSession()->pageTextNotContains('Open priority low');

    $this->drupalGet('/tickets', [
      'query' => [
        'priority' => 'low',
      ],
    ]);
    $this->assertSession()->pageTextContains('Open priority low');
    $this->assertSession()->pageTextNotContains('Resolved priority high');
  }

  /**
   * Default sort is created date descending.
   */
  public function testDefaultSortCreatedDesc(): void {
    $older = $this->createTicket(['title' => 'Older sort ticket']);
    sleep(1);
    $newer = $this->createTicket(['title' => 'Newer sort ticket']);

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/tickets');
    $this->assertGreaterThan(
      strpos($this->getSession()->getPage()->getText(), 'Older sort ticket'),
      strpos($this->getSession()->getPage()->getText(), 'Newer sort ticket')
    );
    $this->assertGreaterThan($older->getCreatedTime(), $newer->getCreatedTime());
  }

  /**
   * Page size is five tickets per page.
   */
  public function testPaginationPageSizeFive(): void {
    for ($i = 1; $i <= 6; $i++) {
      $this->createTicket(['title' => 'Pager ticket ' . $i]);
    }

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/tickets');
    $this->assertSession()->pageTextContains('Pager ticket 1');
    $this->assertSession()->pageTextContains('Pager ticket 5');
    $this->assertSession()->pageTextNotContains('Pager ticket 6');
    $this->assertSession()->linkExists('Next');
  }

  /**
   * Assignee column is visible for Admin and hidden for Reporter.
   */
  public function testAssigneeColumnVisibility(): void {
    $agent = $this->createRoleUser(['agent'], 'column_agent');
    $reporter = $this->createRoleUser(['reporter'], 'column_reporter');
    $this->createTicket([
      'title' => 'Column visibility ticket',
      'uid' => $reporter->id(),
      'field_assigned_to' => $agent->id(),
    ]);

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/tickets');
    $this->assertSession()->pageTextContains('Assignee');

    $this->drupalLogin($reporter);
    $this->drupalGet('/tickets');
    $this->assertSession()->pageTextNotContains('Assignee');
  }

}
