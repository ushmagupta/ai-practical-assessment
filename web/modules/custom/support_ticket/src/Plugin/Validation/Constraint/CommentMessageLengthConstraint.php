<?php

declare(strict_types=1);

namespace Drupal\support_ticket\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates comment message length.
 */
#[Constraint(
  id: 'CommentMessageLength',
  label: new TranslatableMarkup('Comment message length', [], ['context' => 'Validation']),
  type: ['string']
)]
class CommentMessageLengthConstraint extends SymfonyConstraint {

  /**
   * Maximum allowed characters.
   */
  public int $max = 1000;

  /**
   * Violation message.
   */
  public string $tooLongMessage = 'Comment must not exceed @max characters.';

}
