<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Verifies support_ticket config installs cleanly on enable.
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
    'field',
    'filter',
    'text',
    'options',
    'node',
    'comment',
    'views',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig([
      'field',
      'filter',
      'node',
      'comment',
      'views',
      'text',
      'user',
    ]);
    $this->container->get('module_installer')->install(['support_ticket']);
  }

  /**
   * Verifies ticket bundle, fields, and roles exist after module install.
   */
  public function testModuleInstallsDataModel(): void {
    $this->assertTrue(
      $this->container->get('module_handler')->moduleExists('support_ticket')
    );

    $ticket_type = NodeType::load('ticket');
    $this->assertNotNull($ticket_type);
    $this->assertSame('Ticket', $ticket_type->label());

    $field_definitions = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions('node', 'ticket');
    $this->assertArrayHasKey('field_ticket_status', $field_definitions);
    $this->assertArrayHasKey('field_ticket_type', $field_definitions);
    $this->assertArrayHasKey('field_priority', $field_definitions);
    $this->assertArrayHasKey('field_assigned_to', $field_definitions);
    $this->assertArrayHasKey('field_description', $field_definitions);

    $role_storage = $this->container->get('entity_type.manager')
      ->getStorage('user_role');
    $this->assertNotNull($role_storage->load('agent'));
    $this->assertNotNull($role_storage->load('reporter'));

    $view = $this->container->get('entity_type.manager')
      ->getStorage('view')
      ->load('tickets');
    $this->assertNotNull($view);
    $this->assertTrue($view->status());

    $form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('node.ticket.default');
    $this->assertNotNull($form_display);
    $this->assertArrayHasKey('field_ticket_type', $form_display->getComponents());
    $this->assertArrayHasKey('field_description', $form_display->getComponents());
    $this->assertArrayHasKey('field_ticket_status', $form_display->get('hidden'));

    $view_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load('node.ticket.default');
    $this->assertNotNull($view_display);
    $this->assertArrayHasKey('field_ticket_status', $view_display->getComponents());
  }

}
