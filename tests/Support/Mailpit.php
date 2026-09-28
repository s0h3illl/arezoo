<?php

namespace Tests\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Tests\Feature\MailServiceTest;

/**
 * A read-back of what the development mail service actually received.
 *
 * The point of this class is the asymmetry it exploits. The application can prove a
 * mail attempt did not throw, and Laravel can prove a notification was dispatched —
 * neither of those is delivery. What proves delivery is asking a different party
 * than the one that sent the mail, which is why the assertions go out over the HTTP
 * API rather than inspecting Laravel's own sent-mail bag. The two addresses are
 * deliberately separate here: SMTP (services.mailpit.smtp_host) is where the
 * application hands mail over, the API (services.mailpit.api_url) is where this
 * reads it back.
 *
 * @see MailServiceTest
 */
class Mailpit
{
    public function __construct(private readonly string $baseUrl) {}

    public static function fromConfig(): self
    {
        return new self(rtrim((string) config('services.mailpit.api_url'), '/'));
    }

    /**
     * Whether the mail service is there to be asked.
     *
     * A test that needs this says so rather than failing, because the right
     * response to an absent development service is to skip — the application has
     * not regressed, the environment is incomplete.
     */
    public function isReachable(): bool
    {
        try {
            return $this->request()->timeout(3)->get('/api/v1/info')->successful();
        } catch (ConnectionException) {
            return false;
        }
    }

    /**
     * Empty the service, so an assertion cannot be satisfied by a message an
     * earlier test left behind.
     */
    public function deleteAll(): void
    {
        $this->request()->delete('/api/v1/messages')->throw();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function messages(): array
    {
        return $this->request()->get('/api/v1/messages')->throw()->json('messages') ?? [];
    }

    /**
     * The full message, body included. The list endpoint deliberately withholds
     * that, so a body assertion needs this call.
     *
     * @return array<string, mixed>
     */
    public function message(string $id): array
    {
        return $this->request()->get("/api/v1/message/{$id}")->throw()->json();
    }

    /**
     * The first message addressed to one person, in full, or null if none arrived.
     *
     * @return array<string, mixed>|null
     */
    public function firstTo(string $email): ?array
    {
        foreach ($this->messages() as $summary) {
            foreach ((array) ($summary['To'] ?? []) as $recipient) {
                if (strcasecmp((string) ($recipient['Address'] ?? ''), $email) === 0) {
                    return $this->message((string) $summary['ID']);
                }
            }
        }

        return null;
    }

    /**
     * The same, for a moment a delivery may not have landed yet.
     *
     * Fortify's notifications are sent inline rather than queued, so in practice
     * the message is there before the HTTP response even returns. The wait costs
     * nothing when that holds and keeps the test honest if it ever stops holding.
     *
     * @return array<string, mixed>|null
     */
    public function waitForMessageTo(string $email, int $milliseconds = 5000): ?array
    {
        $deadline = microtime(true) + ($milliseconds / 1000);

        do {
            $message = $this->firstTo($email);

            if ($message !== null) {
                return $message;
            }

            usleep(100_000);
        } while (microtime(true) < $deadline);

        return null;
    }

    /**
     * @return array<int, string>
     */
    public function recipientsOf(string $email): array
    {
        $recipients = [];

        foreach ($this->messages() as $summary) {
            foreach ((array) ($summary['To'] ?? []) as $recipient) {
                $recipients[] = (string) ($recipient['Address'] ?? '');
            }
        }

        return array_values(array_filter(
            $recipients,
            fn (string $address): bool => strcasecmp($address, $email) === 0,
        ));
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)->acceptJson();
    }
}
