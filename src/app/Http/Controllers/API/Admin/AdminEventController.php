<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AdminEventController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Event::with(['organization:id,org_name'])
            ->withCount('registrations')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('title', 'ilike', '%' . $request->search . '%');
        }

        $events = $query->paginate(20);

        return $this->success([
            'events'       => $events->items(),
            'current_page' => $events->currentPage(),
            'last_page'    => $events->lastPage(),
            'total'        => $events->total(),
        ], 'Events retrieved');
    }

    public function show($id)
    {
        $event = Event::with(['organization:id,org_name,user_id'])
            ->withCount([
                'registrations',
                'registrations as attended_count' => fn($q) => $q->where('attendance_status', 'attended'),
                'registrations as absent_count'   => fn($q) => $q->where('attendance_status', 'absent'),
            ])
            ->find($id);

        if (!$event) return $this->error('Event not found', 404);

        return $this->success($event, 'Event details retrieved');
    }

    public function approve($id)
    {
        $event = Event::find($id);
        if (!$event) return $this->error('Event not found', 404);

        if ($event->status !== 'pending') {
            return $this->error('Event is not pending approval', 400);
        }

        $event->update(['status' => 'upcoming']);
        return $this->success(null, 'Event approved and published');
    }

    public function cancel($id)
    {
        $event = Event::find($id);
        if (!$event) return $this->error('Event not found', 404);

        if ($event->status === 'cancelled') {
            return $this->error('Event already cancelled', 400);
        }

        $event->update(['status' => 'cancelled']);
        return $this->success(null, 'Event cancelled');
    }
}
