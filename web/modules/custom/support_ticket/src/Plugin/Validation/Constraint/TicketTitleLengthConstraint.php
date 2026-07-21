<?php

declare(strict_types=1);

namespace Drupal\support_ticket\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates ticket title length.
 */
#[Constraint(
  id: 'TicketTitleLength',
  label: new TranslatableMarkup('Ticket title length', [], ['context' => 'Validation']),
  type: ['string']
)]
class TicketTitleLengthConstraint extends SymfonyConstraint {

  /**
   * Maximum allowed characters.
   */
  public int $max = 100;

  /**
   * Violation message.
   */
  public string $tooLongMessage = 'Title must not exceed @max characters.';

}
