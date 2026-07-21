<?php

declare(strict_types=1);

namespace Drupal\support_ticket\Plugin\Validation\Constraint;

use Drupal\comment\CommentInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\support_ticket\TicketStatusService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the TicketNotTerminal constraint.
 */
class TicketNotTerminalConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

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
    if (!$constraint instanceof TicketNotTerminalConstraint) {
      return;
    }

    if ($entity instanceof NodeInterface && in_array($entity->bundle(), $constraint->bundles, TRUE)) {
      if ($entity->isNew()) {
        return;
      }
      $original = $entity->original;
      if (!$original instanceof NodeInterface) {
        $original = $this->entityTypeManager->getStorage('node')->loadUnchanged($entity->id());
      }
      if (!$original instanceof NodeInterface) {
        return;
      }
      $status = $this->statusService->getTicketStatus($original);
      if ($status !== NULL && $this->statusService->isTerminal($status)) {
        $this->context->addViolation($constraint->ticketMessage);
      }
      return;
    }

    if ($entity instanceof CommentInterface) {
      $ticket = $entity->getCommentedEntity();
      if (!$ticket instanceof NodeInterface || $ticket->bundle() !== 'ticket') {
        return;
      }
      $status = $this->statusService->getTicketStatus($ticket);
      if ($status !== NULL && $this->statusService->isTerminal($status)) {
        $this->context->addViolation($constraint->commentMessage);
      }
    }
  }

}
