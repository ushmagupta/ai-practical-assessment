<?php

declare(strict_types=1);

namespace Drupal\support_ticket\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\support_ticket\TicketStatusService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the TicketStatusTransition constraint.
 */
class TicketStatusTransitionConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  public function __construct(
    protected TicketStatusService $statusService,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('support_ticket.status'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $entity, Constraint $constraint): void {
    if (!$constraint instanceof TicketStatusTransitionConstraint) {
      return;
    }
    if (!$entity instanceof NodeInterface || $entity->bundle() !== 'ticket') {
      return;
    }
    if ($entity->isNew()) {
      return;
    }
    $original = $entity->original;
    if (!$original instanceof NodeInterface && !$entity->isNew()) {
      $original = $this->entityTypeManager->getStorage('node')->loadUnchanged($entity->id());
    }
    if (!$original instanceof NodeInterface) {
      return;
    }
    $from_status = $this->statusService->getTicketStatus($original);
    $to_status = $this->statusService->getTicketStatus($entity);
    if ($from_status === NULL || $to_status === NULL || $from_status === $to_status) {
      return;
    }
    if (!$this->statusService->isValidTransition($from_status, $to_status)) {
      $this->context->addViolation($constraint->message, [
        '@from' => $from_status,
        '@to' => $to_status,
      ]);
    }
  }

}
