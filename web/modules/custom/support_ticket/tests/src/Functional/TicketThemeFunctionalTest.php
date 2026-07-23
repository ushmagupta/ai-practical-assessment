<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Functional;

/**
 * Theme presentation tests (M5): empty states, local tasks, terminal UX.
 *
 * @group support_ticket
 */
class TicketThemeFunctionalTest extends SupportTicketFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'support_ticket_theme';

  /**
   * Reporter empty list encourages creating the first ticket.
   */
  public function testReporterEmptyListMessage(): void {
    $reporter = $this->createRoleUser(['reporter'], 'theme_empty_reporter');

    $this->drupalLogin($reporter);
    $this->drupalGet('/tickets');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('No tickets found.');
    $this->assertSession()->pageTextContains('Create your first ticket');
    $this->assertSession()->linkExists('Create your first ticket');
  }

  /**
   * Filtered empty list shows search-specific copy.
   */
  public function testFilteredEmptyListMessage(): void {
    $this->createTicket(['title' => 'Visible list ticket']);

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/tickets', ['query' => ['search' => 'NoMatchQuery']]);
    $this->assertSession()->pageTextContains('No tickets match your search.');
    $this->assertSession()->pageTextNotContains('Create your first ticket');
  }

  /**
   * Agent empty queue shows queue-specific copy.
   */
  public function testAgentEmptyQueueMessage(): void {
    $agent = $this->createRoleUser(['agent'], 'theme_empty_agent');

    $this->drupalLogin($agent);
    $this->drupalGet('/tickets');
    $this->assertSession()->pageTextContains('No tickets found.');
    $this->assertSession()->pageTextContains('no assigned or unassigned tickets in your queue');
  }

  /**
   * Terminal ticket detail shows read-only notice and hides write tabs.
   */
  public function testTerminalTicketReadOnlyAffordance(): void {
    $reporter = $this->createRoleUser(['reporter'], 'theme_terminal_reporter');
    $ticket = $this->createTicket([
      'title' => 'Theme terminal ticket',
      'uid' => $reporter->id(),
      'field_ticket_status' => 'closed',
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('This ticket is closed and read-only.');
    $this->assertSession()->elementExists('css', '.ticket--terminal');
    $this->assertSession()->linkNotExists('Edit');
    $this->assertSession()->linkNotExists('Change status');
  }

  /**
   * Ticket detail exposes expected local tasks for admin and reporter.
   */
  public function testLocalTasksOnTicketDetail(): void {
    $reporter = $this->createRoleUser(['reporter'], 'theme_tabs_reporter');
    $ticket = $this->createTicket([
      'title' => 'Theme tabs ticket',
      'uid' => $reporter->id(),
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id());
    $this->assertSession()->linkExists('View');
    $this->assertSession()->linkExists('Edit');
    $this->assertSession()->linkNotExists('Change status');
    $this->assertSession()->linkNotExists('Delete');

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/node/' . $ticket->id());
    $this->assertSession()->linkExists('View');
    $this->assertSession()->linkExists('Edit');
    $this->assertSession()->linkExists('Change status');
    $this->assertSession()->linkExists('Delete');
  }

  /**
   * Empty comment thread shows themed copy while add form remains available.
   */
  public function testEmptyCommentsMessage(): void {
    $reporter = $this->createRoleUser(['reporter'], 'theme_comments_reporter');
    $ticket = $this->createTicket([
      'title' => 'Theme comments ticket',
      'uid' => $reporter->id(),
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id());
    $this->assertSession()->pageTextContains('No comments yet.');
    $this->assertSession()->fieldExists('comment_body[0][value]');
  }

}
