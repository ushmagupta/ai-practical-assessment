<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Functional;

/**
 * Create-ticket local action on the ticket list page.
 *
 * @group support_ticket
 */
class TicketCreateLinkFunctionalTest extends SupportTicketFunctionalTestBase {

  /**
   * Reporter sees a create-ticket local action on the list page.
   */
  public function testReporterCreateTicketLocalAction(): void {
    $reporter = $this->createRoleUser(['reporter'], 'create_link_reporter');
    $this->drupalLogin($reporter);
    $this->drupalGet('/tickets');
    $this->assertSession()->linkExists('Create ticket');
    $this->assertSession()->linkByHrefExists('/node/add/ticket');
  }

}
