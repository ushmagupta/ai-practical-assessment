<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Functional;

use Drupal\node\Entity\Node;

/**
 * Ticket form validation and transition functional tests (P0).
 *
 * @group support_ticket
 */
class TicketFormFunctionalTest extends SupportTicketFunctionalTestBase {

  /**
   * New tickets default to Open, Medium, and unassigned.
   */
  public function testCreateTicketDefaults(): void {
    $reporter = $this->createRoleUser(['reporter'], 'defaults_reporter');
    $this->drupalLogin($reporter);
    $this->drupalGet('/node/add/ticket');
    $this->submitForm([
      'title[0][value]' => 'Defaults ticket',
      'field_ticket_type' => 'technical',
    ], 'Save');

    $this->assertSession()->pageTextContains('Defaults ticket');
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
      'title' => 'Defaults ticket',
    ]);
    $this->assertCount(1, $nodes);
    /** @var \Drupal\node\Entity\Node $ticket */
    $ticket = reset($nodes);
    $this->assertSame('open', $ticket->get('field_ticket_status')->value);
    $this->assertSame('medium', $ticket->get('field_priority')->value);
    $this->assertTrue($ticket->get('field_assigned_to')->isEmpty());
    $this->assertSame(1, (int) $ticket->isPublished());
  }

  /**
   * Whitespace-only title is rejected on create (EC-14).
   */
  public function testWhitespaceTitleRejected(): void {
    $reporter = $this->createRoleUser(['reporter'], 'whitespace_reporter');
    $this->drupalLogin($reporter);
    $this->drupalGet('/node/add/ticket');
    $this->submitForm([
      'title[0][value]' => '   ',
      'field_ticket_type' => 'technical',
    ], 'Save');
    $this->assertSession()->pageTextContains('Title field is required.');
  }

  /**
   * Reporter edit form omits the assignee field (FR-19).
   */
  public function testReporterEditFormOmitsAssignee(): void {
    $reporter = $this->createRoleUser(['reporter'], 'edit_form_reporter');
    $ticket = $this->createTicket([
      'title' => 'Edit form ticket',
      'uid' => $reporter->id(),
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id() . '/edit');
    $this->assertSession()->fieldNotExists('field_assigned_to[0][target_id]');
    $this->assertSession()->pageTextNotContains('Assigned to');
  }

  /**
   * Reporter cannot tamper assignee on submit (EC-1).
   */
  public function testReporterAssigneeTamperDenied(): void {
    $agent = $this->createRoleUser(['agent'], 'tamper_agent');
    $reporter = $this->createRoleUser(['reporter'], 'tamper_reporter');
    $ticket = $this->createTicket([
      'title' => 'Tamper ticket',
      'uid' => $reporter->id(),
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id() . '/edit');
    $client = $this->getSession()->getDriver()->getClient();
    $form = $client->getCrawler()->filter('form.node-ticket-edit-form')->form();
    $values = $form->getPhpValues();
    $values['title'][0]['value'] = 'Tamper ticket';
    $values['field_ticket_type'] = 'technical';
    $values['field_assigned_to'][0]['target_id'] = $this->userAutocompleteValue($agent);
    $client->request('POST', $form->getUri(), $values);
    $this->assertSession()->pageTextContains('You are not allowed to assign tickets.');
  }

  /**
   * Assigning a non-Agent user shows a field error (EC-2).
   */
  public function testAssignNonAgentRejected(): void {
    $reporter = $this->createRoleUser(['reporter'], 'assign_reporter');
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/node/add/ticket');
    $this->submitForm([
      'title[0][value]' => 'Bad assignee ticket',
      'field_ticket_type' => 'technical',
      'field_assigned_to[0][target_id]' => $this->userAutocompleteValue($reporter),
    ], 'Save');
    $this->assertSession()->pageTextContains('Assignee must be a user with the Agent role.');
  }

  /**
   * Reporter cannot access the transition form (EC-4).
   */
  public function testReporterTransitionDenied(): void {
    $reporter = $this->createRoleUser(['reporter'], 'transition_reporter');
    $ticket = $this->createTicket([
      'title' => 'Reporter transition ticket',
      'uid' => $reporter->id(),
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/ticket/' . $ticket->id() . '/transition');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Status cannot be changed via the edit form (transition form is sole path).
   */
  public function testStatusChangeViaEditFormDenied(): void {
    $agent = $this->createRoleUser(['agent'], 'edit_status_agent');
    $ticket = $this->createTicket([
      'title' => 'Edit status ticket',
      'field_assigned_to' => $agent->id(),
    ]);

    $this->drupalLogin($agent);
    $this->drupalGet('/node/' . $ticket->id() . '/edit');
    $this->submitForm([
      'title[0][value]' => 'Edit status ticket',
      'field_ticket_type' => 'technical',
      'field_ticket_status' => 'in_progress',
    ], 'Save');
    $this->assertSession()->pageTextContains('Status can only be changed via the transition form.');
  }

  /**
   * Invalid transition shows a form-level error (EC-3).
   */
  public function testInvalidTransitionRejected(): void {
    $agent = $this->createRoleUser(['agent'], 'invalid_transition_agent');
    $ticket = $this->createTicket([
      'title' => 'Invalid transition ticket',
      'field_assigned_to' => $agent->id(),
    ]);

    $this->drupalLogin($agent);
    $this->drupalGet('/ticket/' . $ticket->id() . '/transition');
    $this->submitForm([
      'target_status' => 'closed',
    ], 'Change status');
    $this->assertSession()->pageTextContains('You are not allowed to perform this status transition.');
  }

  /**
   * Concurrent status change is rejected on submit (EC-13).
   */
  public function testConcurrentStatusChangeRejected(): void {
    $agent = $this->createRoleUser(['agent'], 'stale_agent');
    $ticket = $this->createTicket([
      'title' => 'Stale status ticket',
      'field_assigned_to' => $agent->id(),
    ]);

    $this->drupalLogin($agent);
    $this->drupalGet('/ticket/' . $ticket->id() . '/transition');

    $storage_ticket = Node::load($ticket->id());
    $storage_ticket->set('field_ticket_status', 'in_progress');
    $storage_ticket->save();

    $this->submitForm([
      'target_status' => 'cancelled',
    ], 'Change status');
    $this->assertSession()->pageTextContains('The ticket status has changed. Please reload the page and try again.');
  }

  /**
   * Title length validation shows an inline field error (EC-11).
   */
  public function testTitleLengthValidation(): void {
    $reporter = $this->createRoleUser(['reporter'], 'length_reporter');
    $this->drupalLogin($reporter);
    $this->drupalGet('/node/add/ticket');
    $this->submitForm([
      'title[0][value]' => str_repeat('a', 101),
      'field_ticket_type' => 'technical',
    ], 'Save');
    $this->assertSession()->pageTextContains('Title must not exceed 100 characters.');
  }

}
