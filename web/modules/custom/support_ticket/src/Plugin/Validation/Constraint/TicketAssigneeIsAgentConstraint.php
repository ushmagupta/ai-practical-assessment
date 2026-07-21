<?php

declare(strict_types=1);

namespace Drupal\support_ticket\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates that ticket assignee references an Agent user.
 */
#[Constraint(
  id: 'TicketAssigneeIsAgent',
  label: new TranslatableMarkup('Ticket assignee is agent', [], ['context' => 'Validation']),
  type: 'entity_reference'
)]
class TicketAssigneeIsAgentConstraint extends SymfonyConstraint {

  /**
   * Violation message.
   */
  public string $message = 'Assignee must be a user with the Agent role.';

}
