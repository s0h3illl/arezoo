<?php

namespace App\Http\Controllers;

use App\Http\Resources\MessageResource;
use App\Models\Contribution;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    private const int MESSAGES_PER_PAGE = 15;

    public function __invoke(): Response
    {
        return Inertia::render('dashboard/Inbox', [
            'messages' => Inertia::scroll(fn () => MessageResource::collection(
                Contribution::query()
                    ->join('wishes', 'wishes.id', '=', 'contributions.wish_id')
                    ->where('wishes.user_id', auth()->user()->id)
                    ->select('contributions.*')
                    ->paid()
                    ->whereNotNull('contributions.message')
                    ->with(['contributor', 'wish'])
                    ->orderByDesc('contributions.settled_at')
                    ->paginate(self::MESSAGES_PER_PAGE)
            )),
        ]);
    }
}
