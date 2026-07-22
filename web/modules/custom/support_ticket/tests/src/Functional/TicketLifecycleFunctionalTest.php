<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Functional;

use Drupal\node\Entity\Node;

/**
 * Ticket lifecycle and assignment functional tests (P1).
 *
 * @group support_ticket
 */
class TicketLifecycleFunctionalTest extends SupportTicketFunctionalTestBase {

  /**
   * Full happy-path lifecycle through the transition form.
   */
  public function testFullLifecycleTransitions(): void {
    $agent = $this->createRoleUser(['agent'], 'lifecycle_agent');
    $ticket = $this->createTicket([
      'title' => 'Lifecycle ticket',
      'field_assigned_to' => $agent->id(),
    ]);

    $this->drupalLogin($agent);
    $this->submitTransition($ticket, 'in_progress');
    $this->assertSession()->pageTextContains('Ticket status updated.');

    $ticket = Node::load($ticket->id());
    $this->assertSame('in_progress', $ticket->get('field_ticket_status')->value);

    $this->submitTransition($ticket, 'resolved');
    $ticket = Node::load($ticket->id());
    $this->assertSame('resolved', $ticket->get('field_ticket_status')->value);

    $this->drupalLogin($this->rootUser);
    $this->submitTransition($ticket, 'closed');
    $ticket = Node::load($ticket->id());
    $this->assertSame('closed', $ticket->get('field_ticket_status')->value);
    $this->assertSame(1, (int) $ticket->isPublished());
  }

  /**
   * Cancellation path ends in terminal read-only state.
   */
  public function testCancellationPathTerminalReadOnly(): void {
    $agent = $this->createRoleUser(['agent'], 'cancel_agent');
    $ticket = $this->createTicket([
      'title' => 'Cancelled ticket',
      'field_assigned_to' => $agent->id(),
    ]);

    $this->drupalLogin($agent);
    $this->submitTransition($ticket, 'cancelled');
    $ticket = Node::load($ticket->id());
    $this->assertSame('cancelled', $ticket->get('field_ticket_status')->value);

    $this->drupalGet('/node/' . $ticket->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/ticket/' . $ticket->id() . '/transition');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Admin can delete tickets; Reporter cannot.
   */
  public function testAdminDeleteTicket(): void {
    $reporter = $this->createRoleUser(['reporter'], 'delete_reporter');
    $ticket = $this->createTicket([
      'title' => 'Delete me ticket',
      'uid' => $reporter->id(),
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/node/' . $ticket->id() . '/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([], 'Delete');
    $this->assertNull(Node::load($ticket->id()));
  }

  /**
   * Agent can self-assign an unassigned ticket (EC-15).
   */
  public function testAgentSelfAssign(): void {
    $agent = $this->createRoleUser(['agent'], 'self_assign_agent');
    $ticket = $this->createTicket([
      'title' => 'Self assign ticket',
    ]);

    $this->drupalLogin($agent);
    $this->drupalGet('/node/' . $ticket->id() . '/edit');
    $this->submitForm([
      'title[0][value]' => 'Self assign ticket',
      'field_ticket_type' => 'technical',
      'field_assigned_to[0][target_id]' => $this->userAutocompleteValue($agent),
    ], 'Save');

    $ticket = Node::load($ticket->id());
    $this->assertSame((int) $agent->id(), (int) $ticket->get('field_assigned_to')->target_id);
  }

  /**
   * Reporter may change type without assignment side effects (EC-16).
   */
  public function testReporterTypeChangeNoAssignment(): void {
    $reporter = $this->createRoleUser(['reporter'], 'type_reporter');
    $ticket = $this->createTicket([
      'title' => 'Type change ticket',
      'uid' => $reporter->id(),
      'field_ticket_type' => 'technical',
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id() . '/edit');
    $this->submitForm([
      'title[0][value]' => 'Type change ticket',
      'field_ticket_type' => 'billing',
    ], 'Save');

    $ticket = Node::load($ticket->id());
    $this->assertSame('billing', $ticket->get('field_ticket_type')->value);
    $this->assertTrue($ticket->get('field_assigned_to')->isEmpty());
  }

  /**
   * Reporter can update own ticket fields.
   */
  public function testReporterUpdatesOwnTicket(): void {
    $reporter = $this->createRoleUser(['reporter'], 'own_update_reporter');
    $ticket = $this->createTicket([
      'title' => 'Original title',
      'uid' => $reporter->id(),
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id() . '/edit');
    $this->submitForm([
      'title[0][value]' => 'Updated reporter title',
      'field_ticket_type' => 'general',
    ], 'Save');

    $ticket = Node::load($ticket->id());
    $this->assertSame('Updated reporter title', $ticket->label());
  }

  /**
   * Agent denied transition on another agent's ticket (EC-8 UI path).
   */
  public function testAgentTransitionDeniedOutsideQueue(): void {
    $agent_a = $this->createRoleUser(['agent'], 'transition_agent_a');
    $agent_b = $this->createRoleUser(['agent'], 'transition_agent_b');
    $ticket = $this->createTicket([
      'title' => 'Other agent transition ticket',
      'field_assigned_to' => $agent_b->id(),
    ]);

    $this->drupalLogin($agent_a);
    $this->drupalGet('/ticket/' . $ticket->id() . '/transition');
    $this->assertSession()->statusCodeEquals(403);
  }

}
