<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Kernel;

use Drupal\support_ticket\TicketAccessService;

/**
 * Kernel tests for ticket render filtering.
 *
 * @group support_ticket
 */
class TicketRenderFilterTest extends SupportTicketKernelTestBase {

  protected TicketAccessService $accessService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->accessService = $this->container->get('support_ticket.access');
  }

  /**
   * Reporter render arrays never include assignee, even when unassigned.
   */
  public function testFilterRenderedTicketRemovesAssigneeForReporter(): void {
    $reporter = $this->createUser(['reporter']);
    $ticket = $this->createTicket([
      'uid' => $reporter->id(),
    ]);
    $build = [
      'field_assigned_to' => ['#theme' => 'field'],
      'field_ticket_status' => ['#theme' => 'field'],
    ];

    $this->accessService->filterRenderedTicket($build, $reporter);
    $this->assertArrayNotHasKey('field_assigned_to', $build);
    $this->assertArrayHasKey('field_ticket_status', $build);
  }

  /**
   * Admin render arrays retain assignee field.
   */
  public function testFilterRenderedTicketKeepsAssigneeForAdmin(): void {
    $admin = $this->createUser(['administrator']);
    $build = [
      'field_assigned_to' => ['#theme' => 'field'],
    ];

    $this->accessService->filterRenderedTicket($build, $admin);
    $this->assertArrayHasKey('field_assigned_to', $build);
  }

}
