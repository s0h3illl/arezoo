<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\Support\Mailpit;

/*
|--------------------------------------------------------------------------
| The mail service itself
|--------------------------------------------------------------------------
|
| Every other test in this suite proves the application built a message; this file
| is the one that proves the message arrived. The distinction matters because a
| mail stack has three separate failure modes that look identical from the inside
| — a wrong mailer name, an unreachable host, a provider that quietly accepts and
| drops — and only the last one is visible to the application. So nothing here
| inspects Laravel's own sent-mail bag: every assertion is made by reading back
| what Mailpit actually received, over a different protocol to a different port
| than the one the mail was sent through.
|
*/

/**
 * The service under test, addressed by its own two addresses.
 */
beforeEach(function () {
    $this->mailpit = Mailpit::fromConfig();

    if (! $this->mailpit->isReachable()) {
        $this->markTestSkipped(sprintf(
            'The mail service is not reachable at %s. Start it with `docker compose up -d mailpit`.',
            config('services.mailpit.api_url'),
        ));
    }

    // Cleared first so that no assertion can be satisfied by a message an earlier
    // test left behind — the service outlives the test process, so it would.
    $this->mailpit->deleteAll();

    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.transport' => 'smtp',
        'mail.mailers.smtp.scheme' => null,
        'mail.mailers.smtp.host' => config('services.mailpit.smtp_host'),
        'mail.mailers.smtp.port' => config('services.mailpit.smtp_port'),
        'mail.mailers.smtp.username' => null,
        'mail.mailers.smtp.password' => null,
        'mail.mailers.smtp.timeout' => 10,
    ]);
});

test('a message the application hands to the smtp mailer is received by the mail service', function () {
    Mail::raw('سلام از آریزو', function ($message) {
        $message->to('sara@example.com')->subject('پیام آزمایشی');
    });

    $message = $this->mailpit->waitForMessageTo('sara@example.com');

    expect($message)
        ->not->toBeNull()
        ->and($message['Subject'])->toBe('پیام آزمایشی')
        ->and($message['Text'])->toContain('سلام از آریزو');
});

test('a delivered message carries the identity the application configured', function () {
    Mail::raw('محتوا', fn ($message) => $message->to('sara@example.com')->subject('هویت'));

    $message = $this->mailpit->waitForMessageTo('sara@example.com');

    // Compared against configuration rather than a literal: what is being claimed
    // is that the application's own sender reaches the service intact, not that a
    // particular address is in use today.
    expect($message['From']['Address'])->toBe(config('mail.from.address'))
        ->and($message['From']['Name'])->toBe(config('mail.from.name'));
});

test('registering sends the verification link, translated and laid out right to left', function () {
    $this->post(route('register.store'), mailTestAccount())->assertSessionHasNoErrors();

    $message = $this->mailpit->waitForMessageTo('sara@example.com');

    expect($message)->not->toBeNull()
        // The subject is what a person reads in their inbox before deciding to open
        // it, so an untranslated subject is the failure this catches.
        ->and($message['Subject'])->toBe(__('Verify Email Address'))
        ->and($message['Subject'])->not->toBe('Verify Email Address')
        ->and($message['From']['Address'])->toBe(config('mail.from.address'))
        // The link is the entire point of the message, and it is signed, so it can
        // only be recognised by its path.
        ->and($message['HTML'])->toContain('/email/verify/')
        // Proves the application's own layout override is in use rather than the
        // framework default, which is left-to-right and would read backwards in a
        // Persian inbox.
        ->and($message['HTML'])->toContain('dir="rtl"')
        // A message with an empty text part is invisible to anyone whose client
        // cannot render HTML.
        ->and($message['Text'])->not->toBeEmpty();
});

test('a verification link only reaches the address that registered', function () {
    $this->post(route('register.store'), mailTestAccount())->assertSessionHasNoErrors();

    // Read as "nobody received this" rather than "the address nobody owns was sent
    // this". A wait would only make the negative case slower, not stronger: the
    // message either arrived before this request returned or it did not.
    expect($this->mailpit->recipientsOf('nobody@example.com'))->toBeEmpty();
});

test('asking to reset a password sends a link to a known address', function () {
    // Created rather than registered, because registering signs the person in, and
    // the reset route is behind guest middleware: a second request from an
    // authenticated client is redirected away and never reaches the broker at all.
    // That is a fact about the flow worth knowing, not something to work around
    // silently — the mailbox here is genuinely empty for that reason.
    User::factory()->create(['email' => 'sara@example.com']);

    // Fortify reports the broker's answer in the session, translated. Asserting it
    // separately is what distinguishes "the link was sent and the message went
    // missing" from "the broker never sent anything" — the two otherwise look
    // identical, both being an empty mailbox.
    $this->post(route('password.email'), ['email' => 'sara@example.com'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', trans('passwords.sent'));

    $message = $this->mailpit->waitForMessageTo('sara@example.com');

    expect($message)->not->toBeNull()
        ->and($message['Subject'])->toBe(__('Reset Password'))
        ->and($message['Subject'])->not->toBe('Reset Password')
        ->and($message['HTML'])->toContain('dir="rtl"')
        ->and($message['Text'])->toContain(__('This password reset link will expire in :count minutes.', [
            'count' => config('auth.passwords.users.expire'),
        ]));
});

/*
| A characterisation test, not an endorsement.
|
| Fortify answers an unknown address with an error in the bag ("no user with this
| email") and a known one with a success status, and the application does not
| override that. The two are therefore trivially distinguishable, which means the
| forgot-password form confirms which addresses have accounts here — to anyone, with
| no credentials.
|
| Pinned rather than fixed, because changing it is a product decision rather than a
| mail-service one: the fix is a one-line Fortify binding of
| FailedPasswordResetLinkRequestResponse that always reports the same success. When
| that happens this test is what should be rewritten and renamed.
*/
test('the forgot-password form reveals whether an address is registered', function () {
    User::factory()->create(['email' => 'sara@example.com']);

    $known = $this->post(route('password.email'), ['email' => 'sara@example.com'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', trans('passwords.sent'));

    $this->mailpit->deleteAll();

    $unknown = $this->post(route('password.email'), ['email' => 'nobody@example.com'])
        ->assertSessionHasErrors('email', trans('passwords.user'));

    // Both are redirects, so the status code alone tells an attacker nothing. The
    // session contents are what give it away.
    expect($unknown->getStatusCode())->toBe($known->getStatusCode())
        ->and($this->mailpit->messages())->toBeEmpty();
});

/**
 * A registration, for the test that is about the mail it triggers.
 *
 * @return array<string, string>
 */
function mailTestAccount(): array
{
    return [
        'name' => 'سارا احمدی',
        'username' => 'sara-ahmadi',
        'email' => 'sara@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];
}
