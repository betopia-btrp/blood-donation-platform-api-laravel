<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Event;
use App\Models\DonationRequest;
use App\Models\Report;
use App\Models\Payment;
use App\Models\EventRegistration;
use App\Models\DonationRequestRecipient;
use App\Traits\ApiResponse;

class AdminDashboardController extends Controller
{
    use ApiResponse;

    public function stats()
    {
        $totalUsers = User::count();
        $usersByRole = User::join('roles', 'users.role_id', '=', 'roles.id')
            ->selectRaw('roles.name as role, count(*) as total')
            ->groupBy('roles.name')
            ->pluck('total', 'role');

        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsers = User::where('is_active', false)->count();

        $totalEvents = Event::count();
        $eventsByStatus = Event::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalRequests = DonationRequest::count();
        $requestsByStatus = DonationRequest::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalDonations = DonationRequestRecipient::where('response_status', 'donated')->count();

        $totalReports = Report::count();
        $reportsByStatus = Report::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalPayments = Payment::count();
        $totalRevenue = Payment::where('status', 'confirmed')->sum('amount');

        return $this->success([
            'users' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'inactive' => $inactiveUsers,
                'by_role' => $usersByRole,
            ],
            'events' => [
                'total' => $totalEvents,
                'by_status' => $eventsByStatus,
            ],
            'donation_requests' => [
                'total' => $totalRequests,
                'by_status' => $requestsByStatus,
            ],
            'donations' => $totalDonations,
            'reports' => [
                'total' => $totalReports,
                'by_status' => $reportsByStatus,
            ],
            'payments' => [
                'total' => $totalPayments,
                'revenue' => $totalRevenue,
            ],
        ], 'Admin stats retrieved');
    }
}
