<?php

declare(strict_types=1);

namespace Drupal\support_ticket\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the TicketAssigneeIsAgent constraint.
 */
class TicketAssigneeIsAgentConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  public function __construct(
    protected UserStorageInterface $userStorage,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $items, Constraint $constraint): void {
    if (!$constraint instanceof TicketAssigneeIsAgentConstraint) {
      return;
    }
    if ($items->isEmpty()) {
      return;
    }
    $target_id = $items->first()->target_id ?? NULL;
    if (!$target_id) {
      return;
    }
    $user = $this->userStorage->load($target_id);
    if (!$user || !$user->hasRole('agent')) {
      $this->context->addViolation($constraint->message);
    }
  }

}
