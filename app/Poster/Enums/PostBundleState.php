<?php

namespace App\Poster\Enums;

/**
 * The bundle state machine.
 *
 * draft -> pending_approval -> approved -> posting -> posted | partially_failed | failed
 *
 * Hard rule: a bundle may only enter `posting` from `approved`, and approval
 * must be recorded (approved_by / approved_at) by a human decision. Nothing
 * in the intake path ever approves a bundle.
 */
enum PostBundleState: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Posting = 'posting';
    case Posted = 'posted';
    case PartiallyFailed = 'partially_failed';
    case Failed = 'failed';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::PendingApproval],
            self::PendingApproval => [self::Approved, self::Draft],
            self::Approved => [self::Posting],
            self::Posting => [self::Posted, self::PartiallyFailed, self::Failed],
            self::Posted, self::PartiallyFailed, self::Failed => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
