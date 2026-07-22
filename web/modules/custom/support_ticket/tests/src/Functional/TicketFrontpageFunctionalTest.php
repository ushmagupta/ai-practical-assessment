<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Functional;

/**
 * Front page and post-login routing to the scoped ticket list.
 *
 * @group support_ticket
 */
class TicketFrontpageFunctionalTest extends SupportTicketFunctionalTestBase {

  /**
   * Authenticated users land on /tickets after login when no destination set.
   */
  public function testLoginRedirectsToTickets(): void {
    $reporter = $this->createRoleUser(['reporter'], 'frontpage_reporter');
    $this->drupalGet('/user/login');
    $this->submitForm([
      'name' => $reporter->getAccountName(),
      'pass' => $reporter->getAccountName(),
    ], 'Log in');
    $this->assertSession()->addressEquals('/tickets');
  }

  /**
   * Direct /node requests redirect to the scoped ticket list.
   */
  public function testNodeFrontpageRedirectsToTickets(): void {
    $reporter = $this->createRoleUser(['reporter'], 'node_redirect_reporter');
    $other = $this->createRoleUser(['reporter'], 'node_redirect_other');
    $this->createTicket(['title' => 'Hidden from node listing', 'uid' => $other->id()]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node');
    $this->assertSession()->addressEquals('/tickets');
    $this->assertSession()->pageTextNotContains('Hidden from node listing');
  }

}
