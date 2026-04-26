<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Report::with([
            'reporter:id,name,email',
            'targetDonationRequest:id,blood_group,district,status',
            'targetEvent:id,title,district,status',
            'targetUser:id,name,email',
        ])->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->paginate(20);

        return $this->success([
            'reports'      => $reports->items(),
            'current_page' => $reports->currentPage(),
            'last_page'    => $reports->lastPage(),
            'total'        => $reports->total(),
        ], 'Reports retrieved');
    }

    public function show($id)
    {
        $report = Report::with([
            'reporter:id,name,email',
            'targetDonationRequest:id,blood_group,district,status,requester_user_id',
            'targetDonationRequest.requester:id,name,email',
            'targetEvent:id,title,district,status,organization_id',
            'targetEvent.organization:id,org_name',
            'targetUser:id,name,email',
            'targetUser.profile',
        ])->find($id);

        if (!$report) return $this->error('Report not found', 404);

        return $this->success($report, 'Report details retrieved');
    }

    public function review($id)
    {
        $report = Report::find($id);
        if (!$report) return $this->error('Report not found', 404);

        if ($report->status !== 'pending') {
            return $this->error('Report already actioned', 400);
        }

        $report->update(['status' => 'reviewed']);
        return $this->success(null, 'Report marked as reviewed');
    }

    public function resolve($id)
    {
        $report = Report::find($id);
        if (!$report) return $this->error('Report not found', 404);

        $report->update(['status' => 'resolved']);
        return $this->success(null, 'Report resolved');
    }
}
