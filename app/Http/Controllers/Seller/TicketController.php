<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    /** GET /seller/tickets - List seller's tickets */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $tickets = Ticket::where('user_id', $userId)
            ->withCount('messages')
            ->latest('updated_at')
            ->paginate($request->per_page ?? 15);

        return response()->json(['success' => true, 'data' => $tickets]);
    }

    /** POST /seller/tickets - Create a new ticket */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'subject'  => 'required|string|max:255',
            'message'  => 'required|string',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        $userId = auth()->id();

        $ticket = Ticket::create([
            'user_id'  => $userId,
            'subject'  => $request->subject,
            'priority' => $request->priority ?? 'medium',
            'status'   => 'open',
        ]);

        $ticket->messages()->create([
            'user_id' => $userId,
            'message' => $request->message,
        ]);

        return response()->json(['success' => true, 'data' => $ticket], 201);
    }

    /** GET /seller/tickets/{ticket} - View ticket and its messages */
    public function show(Ticket $ticket): JsonResponse
    {
        if ($ticket->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $ticket->load(['messages.user' => function($q) {
            $q->select('id', 'name', 'role');
        }]);

        return response()->json(['success' => true, 'data' => $ticket]);
    }

    /** POST /seller/tickets/{ticket}/messages - Reply to a ticket */
    public function reply(Request $request, Ticket $ticket): JsonResponse
    {
        if ($ticket->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate(['message' => 'required|string']);

        $message = $ticket->messages()->create([
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);

        // Re-open ticket if it was closed
        if ($ticket->status === 'closed') {
            $ticket->update(['status' => 'open']);
        } else {
            $ticket->touch(); // Update updated_at timestamp
        }

        return response()->json(['success' => true, 'data' => $message->load('user:id,name,role')]);
    }
}
