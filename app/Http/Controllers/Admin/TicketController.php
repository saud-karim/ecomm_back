<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    /** GET /admin/tickets - List all tickets */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        
        $tickets = Ticket::with('user:id,name,role')
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->withCount('messages')
            ->latest('updated_at')
            ->paginate($request->per_page ?? 20);

        return response()->json(['success' => true, 'data' => $tickets]);
    }

    /** GET /admin/tickets/{ticket} - View ticket and its messages */
    public function show(Ticket $ticket): JsonResponse
    {
        $ticket->load(['user:id,name,email,role', 'messages.user' => function($q) {
            $q->select('id', 'name', 'role');
        }]);

        return response()->json(['success' => true, 'data' => $ticket]);
    }

    /** POST /admin/tickets/{ticket}/messages - Reply to a ticket */
    public function reply(Request $request, Ticket $ticket): JsonResponse
    {
        $request->validate(['message' => 'required|string']);

        $message = $ticket->messages()->create([
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);

        if ($ticket->status === 'open') {
             $ticket->update(['status' => 'pending']); // Pending user response
        } else {
             $ticket->touch(); // Update updated_at
        }

        return response()->json(['success' => true, 'data' => $message->load('user:id,name,role')]);
    }

    /** PUT /admin/tickets/{ticket}/status - Update ticket status */
    public function updateStatus(Request $request, Ticket $ticket): JsonResponse
    {
        $request->validate(['status' => 'required|in:open,pending,closed']);
        
        $ticket->update(['status' => $request->status]);

        return response()->json(['success' => true, 'message' => 'Ticket status updated']);
    }
}
