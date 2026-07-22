<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Functional;

use Drupal\user\Entity\User;

/**
 * User management functional tests (P1).
 *
 * @group support_ticket
 */
class TicketUserFunctionalTest extends SupportTicketFunctionalTestBase {

  /**
   * Admin user create form defaults to the Agent role (FR-7).
   */
  public function testAdminCreateUserDefaultsToAgent(): void {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/people/create');
    $this->assertSession()->checkboxChecked('edit-roles-agent');
  }

  /**
   * Admin can create a user with name, email, and role.
   */
  public function testAdminCreatesUser(): void {
    $name = $this->randomMachineName();
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/people/create');
    $this->submitForm([
      'name' => $name,
      'mail' => $name . '@example.com',
      'pass[pass1]' => $name,
      'pass[pass2]' => $name,
      'roles[agent]' => TRUE,
    ], 'Create new account');

    $user = user_load_by_name($name);
    $this->assertInstanceOf(User::class, $user);
    $this->assertTrue($user->hasRole('agent'));
  }

  /**
   * Delete is blocked when user is a ticket assignee (EC-9).
   */
  public function testDeleteBlockedForAssignee(): void {
    $agent = $this->createRoleUser(['agent'], 'blocked_assignee');
    $this->createTicket([
      'title' => 'Assignee block ticket',
      'field_assigned_to' => $agent->id(),
    ]);

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/user/' . $agent->id() . '/cancel');
    $this->submitForm([], 'Confirm');
    $this->assertSession()->pageTextContains('User cannot be deleted while assigned to one or more tickets.');
    $this->assertNotNull(User::load($agent->id()));
  }

  /**
   * Admin self-delete is blocked (EC-10).
   */
  public function testAdminSelfDeleteBlocked(): void {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/user/' . $this->rootUser->id() . '/cancel');
    $this->submitForm([], 'Confirm');
    $this->assertSession()->pageTextContains('Administrators cannot delete their own account.');
    $this->assertNotNull(User::load($this->rootUser->id()));
  }

}
