<?php

declare(strict_types=1);

namespace Drupal\support_ticket\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\support_ticket\TicketStatusService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dedicated form for changing ticket workflow status.
 */
class TicketTransitionForm extends FormBase {

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
  public function getFormId(): string {
    return 'support_ticket_transition_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL): array {
    if (!$node instanceof NodeInterface || $node->bundle() !== 'ticket') {
      throw new \InvalidArgumentException('A ticket node is required.');
    }

    $current_status = $this->statusService->getTicketStatus($node);
    if ($current_status === NULL) {
      throw new \InvalidArgumentException('Ticket workflow status is missing.');
    }

    $form_state->set('ticket_nid', $node->id());
    $form_state->set('expected_status', $current_status);

    $status_labels = $this->getStatusLabels();
    $options = [];
    foreach ($this->statusService->getAllowedTargetStatuses($current_status) as $target) {
      if ($this->statusService->canTransition($this->currentUser(), $node, $target)) {
        $options[$target] = $status_labels[$target] ?? $target;
      }
    }

    $form['#title'] = $this->t('Change status: @title', ['@title' => $node->label()]);

    $form['current_status'] = [
      '#type' => 'item',
      '#title' => $this->t('Current status'),
      '#markup' => $status_labels[$current_status] ?? $current_status,
    ];

    $form['target_status'] = [
      '#type' => 'select',
      '#title' => $this->t('New status'),
      '#options' => $options,
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select -'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Change status'),
        '#button_type' => 'primary',
      ],
      'cancel' => [
        '#type' => 'link',
        '#title' => $this->t('Cancel'),
        '#url' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()]),
        '#attributes' => ['class' => ['button']],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $node = $this->loadTicket($form_state);
    if (!$node) {
      $form_state->setErrorByName('target_status', $this->t('Ticket not found.'));
      return;
    }

    $expected_status = (string) $form_state->get('expected_status');
    if ($this->statusService->isStatusStale($node, $expected_status)) {
      $form_state->setErrorByName('target_status', $this->t('The ticket status has changed. Please reload the page and try again.'));
      return;
    }

    $target_status = (string) $form_state->getValue('target_status');
    if (!$this->statusService->canTransition($this->currentUser(), $node, $target_status)) {
      $form_state->setErrorByName('target_status', $this->t('You are not allowed to perform this status transition.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $node = $this->loadTicket($form_state);
    if (!$node) {
      return;
    }

    $target_status = (string) $form_state->getValue('target_status');
    $node->set('field_ticket_status', $target_status);
    $node->save();

    $this->messenger()->addStatus($this->t('Ticket status updated.'));
    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
  }

  /**
   * Loads the ticket node for this form submission.
   */
  protected function loadTicket(FormStateInterface $form_state): ?NodeInterface {
    $nid = $form_state->get('ticket_nid');
    if (!$nid) {
      return NULL;
    }
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    return $node instanceof NodeInterface ? $node : NULL;
  }

  /**
   * Returns human-readable labels for workflow statuses.
   *
   * @return array<string, string>
   */
  protected function getStatusLabels(): array {
    return [
      TicketStatusService::STATUS_OPEN => (string) $this->t('Open'),
      TicketStatusService::STATUS_IN_PROGRESS => (string) $this->t('In Progress'),
      TicketStatusService::STATUS_RESOLVED => (string) $this->t('Resolved'),
      TicketStatusService::STATUS_CLOSED => (string) $this->t('Closed'),
      TicketStatusService::STATUS_CANCELLED => (string) $this->t('Cancelled'),
    ];
  }

}
