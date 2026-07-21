<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Smoke test: support_ticket module enables without error.
 *
 * @group support_ticket
 */
class ModuleEnableTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'support_ticket',
  ];

  /**
   * Verifies the custom module is present after kernel bootstrap.
   */
  public function testModuleEnables(): void {
    $this->assertTrue(
      $this->container->get('module_handler')->moduleExists('support_ticket')
    );
  }

}
