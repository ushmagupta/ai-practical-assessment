<?php

declare(strict_types=1);

namespace Drupal\support_ticket\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates ticket description length.
 */
#[Constraint(
  id: 'TicketDescriptionLength',
  label: new TranslatableMarkup('Ticket description length', [], ['context' => 'Validation']),
  type: ['string']
)]
class TicketDescriptionLengthConstraint extends SymfonyConstraint {

  /**
   * Maximum allowed characters.
   */
  public int $max = 1000;

  /**
   * Violation message.
   */
  public string $tooLongMessage = 'Description must not exceed @max characters.';

}
