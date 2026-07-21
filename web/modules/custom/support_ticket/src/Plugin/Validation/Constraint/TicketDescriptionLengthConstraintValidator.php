<?php

declare(strict_types=1);

namespace Drupal\support_ticket\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the TicketDescriptionLength constraint.
 */
class TicketDescriptionLengthConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $items, Constraint $constraint): void {
    if (!$constraint instanceof TicketDescriptionLengthConstraint) {
      return;
    }
    if ($items->isEmpty()) {
      return;
    }
    $value = (string) $items->value;
    if (mb_strlen($value) > $constraint->max) {
      $this->context->addViolation($constraint->tooLongMessage, ['@max' => $constraint->max]);
    }
  }

}
