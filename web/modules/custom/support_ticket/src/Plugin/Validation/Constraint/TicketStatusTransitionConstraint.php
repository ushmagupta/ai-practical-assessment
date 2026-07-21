<?php

declare(strict_types=1);

namespace Drupal\support_ticket\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates ticket workflow status transitions.
 */
#[Constraint(
  id: 'TicketStatusTransition',
  label: new TranslatableMarkup('Ticket status transition', [], ['context' => 'Validation']),
  type: 'entity:node'
)]
class TicketStatusTransitionConstraint extends SymfonyConstraint {

  /**
   * Violation message.
   */
  public string $message = 'Invalid status transition from @from to @to.';

  /**
   * Ticket bundles this constraint applies to.
   *
   * @var string[]
   */
  public array $bundles = ['ticket'];

}
