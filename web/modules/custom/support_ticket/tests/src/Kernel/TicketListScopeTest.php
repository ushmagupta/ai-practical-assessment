<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Kernel;

use Drupal\support_ticket\TicketAccessService;

/**
 * Kernel tests for ticket list scoping helpers.
 *
 * @group support_ticket
 */
class TicketListScopeTest extends SupportTicketKernelTestBase {

  protected TicketAccessService $accessService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->accessService = $this->container->get('support_ticket.access');
  }

  /**
   * Admin list scope returns all tickets.
   */
  public function testAdminListScope(): void {
    $admin = $this->createUser(['administrator']);
    $agent = $this->createUser(['agent']);
    $reporter = $this->createUser(['reporter']);

    $this->createTicket(['field_assigned_to' => $agent->id(), 'uid' => $reporter->id()]);
    $this->createTicket(['uid' => $reporter->id()]);

    $query = $this->container->get('entity_type.manager')->getStorage('node')->getQuery()
      ->condition('type', 'ticket')
      ->accessCheck(FALSE);
    $this->accessService->applyListScope($query, $admin);
    $this->assertCount(2, $query->execute());
  }

  /**
   * Agent list scope returns assigned-to-self and unassigned tickets only.
   */
  public function testAgentListScope(): void {
    $agent = $this->createUser(['agent']);
    $other_agent = $this->createUser(['agent']);

    $unassigned = $this->createTicket(['field_ticket_status' => 'open']);
    $assigned_self = $this->createTicket([
      'field_assigned_to' => $agent->id(),
      'field_ticket_status' => 'open',
    ]);
    $this->createTicket([
      'field_assigned_to' => $other_agent->id(),
      'field_ticket_status' => 'open',
    ]);

    $query = $this->container->get('entity_type.manager')->getStorage('node')->getQuery()
      ->condition('type', 'ticket')
      ->accessCheck(FALSE);
    $this->accessService->applyListScope($query, $agent);
    $nids = array_map('intval', array_values($query->execute()));

    $this->assertEqualsCanonicalizing(
      [(int) $unassigned->id(), (int) $assigned_self->id()],
      $nids
    );
  }

  /**
   * Reporter list scope returns own tickets only.
   */
  public function testReporterListScope(): void {
    $reporter = $this->createUser(['reporter']);
    $other_reporter = $this->createUser(['reporter']);

    $own = $this->createTicket(['uid' => $reporter->id()]);
    $this->createTicket(['uid' => $other_reporter->id()]);

    $query = $this->container->get('entity_type.manager')->getStorage('node')->getQuery()
      ->condition('type', 'ticket')
      ->accessCheck(FALSE);
    $this->accessService->applyListScope($query, $reporter);
    $nids = $query->execute();
    $this->assertCount(1, $nids);
    $this->assertSame((int) $own->id(), (int) reset($nids));
  }

}
