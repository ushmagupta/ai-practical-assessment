<?php

declare(strict_types=1);

namespace Drupal\support_ticket\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Rejects writes to terminal tickets and comments on terminal tickets.
 */
#[Constraint(
  id: 'TicketNotTerminal',
  label: new TranslatableMarkup('Ticket not terminal', [], ['context' => 'Validation'])
)]
class TicketNotTerminalConstraint extends SymfonyConstraint {

  /**
   * Violation message for ticket updates.
   */
  public string $ticketMessage = 'Closed and cancelled tickets cannot be edited.';

  /**
   * Violation message for comments on terminal tickets.
   */
  public string $commentMessage = 'Comments cannot be added or edited on closed or cancelled tickets.';

  /**
   * Ticket bundles this constraint applies to.
   *
   * @var string[]
   */
  public array $bundles = ['ticket'];

}
