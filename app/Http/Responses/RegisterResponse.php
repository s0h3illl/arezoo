<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Send a freshly registered user to the email verification notice.
 *
 * Fortify's own response drops the user at home, which for this app is a page
 * they cannot act on yet: every account starts unverified, and publishing a
 * Wish or making a Contribution needs a verified address.
 */
class RegisterResponse implements RegisterResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        return redirect()->route('verification.notice');
    }
}
