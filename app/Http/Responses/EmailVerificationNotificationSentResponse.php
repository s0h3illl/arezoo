<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse as EmailVerificationNotificationSentResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Confirm a resent verification link in Persian.
 *
 * Fortify flashes the bare `verification-link-sent` key, leaving the wording to
 * the client. Flashing the sentence itself keeps the notice page rendering the
 * `status` flash exactly like login and forgot-password do.
 */
class EmailVerificationNotificationSentResponse implements EmailVerificationNotificationSentResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        return back()->with('status', trans('verification.sent'));
    }
}
