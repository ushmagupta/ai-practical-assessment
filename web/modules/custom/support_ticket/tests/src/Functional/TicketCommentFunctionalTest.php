<?php

declare(strict_types=1);

namespace Drupal\Tests\support_ticket\Functional;

/**
 * Ticket comment functional tests (P1).
 *
 * @group support_ticket
 */
class TicketCommentFunctionalTest extends SupportTicketFunctionalTestBase {

  /**
   * Comment subject is not exposed on add, thread, or edit (message only).
   */
  public function testCommentSubjectNotExposed(): void {
    $reporter = $this->createRoleUser(['reporter'], 'no_subject_reporter');
    $ticket = $this->createTicket([
      'title' => 'No subject ticket',
      'uid' => $reporter->id(),
    ]);
    $comment = $this->createComment($ticket, $reporter, 'Visible comment message only');

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id());
    $this->assertSession()->fieldNotExists('subject[0][value]');
    $this->assertSession()->pageTextContains('Visible comment message only');
    $this->assertSession()->elementNotExists('xpath', '//h3[contains(., "Visible comment")]');

    $this->drupalGet('/comment/' . $comment->id() . '/edit');
    $this->assertSession()->fieldNotExists('subject[0][value]');
  }

  /**
   * Users can add comments on non-terminal tickets within scope.
   */
  public function testAddCommentWithinScope(): void {
    $reporter = $this->createRoleUser(['reporter'], 'comment_reporter');
    $ticket = $this->createTicket([
      'title' => 'Comment ticket',
      'uid' => $reporter->id(),
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id());
    $this->submitForm([
      'comment_body[0][value]' => 'Reporter comment body',
    ], 'Save');
    $this->assertSession()->pageTextContains('Reporter comment body');
  }

  /**
   * Comment author can edit own comment on a non-terminal ticket.
   */
  public function testEditOwnComment(): void {
    $reporter = $this->createRoleUser(['reporter'], 'edit_comment_reporter');
    $ticket = $this->createTicket([
      'title' => 'Edit comment ticket',
      'uid' => $reporter->id(),
    ]);
    $comment = $this->createComment($ticket, $reporter, 'Original comment');

    $this->drupalLogin($reporter);
    $this->drupalGet('/comment/' . $comment->id() . '/edit');
    $this->submitForm([
      'comment_body[0][value]' => 'Edited comment body',
    ], 'Save');
    $this->assertSession()->pageTextContains('Edited comment body');
  }

  /**
   * No comment delete control is exposed.
   */
  public function testNoCommentDeleteControl(): void {
    $reporter = $this->createRoleUser(['reporter'], 'no_delete_reporter');
    $ticket = $this->createTicket([
      'title' => 'No delete comment ticket',
      'uid' => $reporter->id(),
    ]);
    $comment = $this->createComment($ticket, $reporter, 'Persistent comment');

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/node/' . $ticket->id());
    $this->assertSession()->pageTextNotContains('Delete comment');
    $this->drupalGet('/comment/' . $comment->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Comments cannot be added on terminal tickets (EC-5).
   */
  public function testTerminalTicketCommentDenied(): void {
    $reporter = $this->createRoleUser(['reporter'], 'terminal_comment_reporter');
    $ticket = $this->createTicket([
      'title' => 'Closed comment ticket',
      'uid' => $reporter->id(),
      'field_ticket_status' => 'closed',
    ]);

    $this->drupalLogin($reporter);
    $this->drupalGet('/node/' . $ticket->id());
    $this->assertSession()->fieldNotExists('comment_body[0][value]');
  }

}
