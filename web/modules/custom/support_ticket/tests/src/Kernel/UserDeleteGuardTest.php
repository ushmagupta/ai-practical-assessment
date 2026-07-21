<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Kernel;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\user\Entity\User;

/**
 * Kernel tests for user delete guards.
 *
 * @group support_ticket
 */
class UserDeleteGuardTest extends SupportTicketKernelTestBase {

  /**
   * User deletion is blocked when the user is a ticket assignee.
   */
  public function testDeleteBlockedForAssignee(): void {
    $admin = $this->createUser(['administrator']);
    $agent = $this->createUser(['agent']);
    $this->createTicket(['field_assigned_to' => $agent->id()]);

    \Drupal::currentUser()->setAccount($admin);
    $access = $this->container->get('support_ticket.access');
    $this->assertFalse($access->userDeletionAllowed($agent, $admin));

    $this->expectException(EntityStorageException::class);
    $agent->delete();
  }

  /**
   * Admin cannot delete their own account.
   */
  public function testAdminSelfDeleteBlocked(): void {
    $admin = $this->createUser(['administrator']);
    \Drupal::currentUser()->setAccount($admin);

    $access = $this->container->get('support_ticket.access');
    $this->assertFalse($access->userDeletionAllowed($admin, $admin));

    $this->expectException(EntityStorageException::class);
    $admin->delete();
  }

  /**
   * Unassigned users without tickets can be deleted.
   */
  public function testDeleteAllowedForUnassignedUser(): void {
    $admin = $this->createUser(['administrator']);
    $reporter = $this->createUser(['reporter']);
    \Drupal::currentUser()->setAccount($admin);

    $access = $this->container->get('support_ticket.access');
    $this->assertTrue($access->userDeletionAllowed($reporter, $admin));

    $uid = $reporter->id();
    $reporter->delete();
    $this->assertNull(User::load($uid));
  }

}
