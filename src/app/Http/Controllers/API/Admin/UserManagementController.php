<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = User::with(['profile', 'organization'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('role')) {
            $query->whereHas('role', fn($q) => $q->where('name', $request->role));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                  ->orWhere('email', 'ilike', '%' . $search . '%');
            });
        }

        $users = $query->paginate(20);

        return $this->success([
            'users'        => $users->items(),
            'current_page' => $users->currentPage(),
            'last_page'    => $users->lastPage(),
            'total'        => $users->total(),
        ], 'Users retrieved');
    }

    public function show($id)
    {
        $user = User::with(['profile', 'organization', 'organization.documents'])->find($id);

        if (!$user)
            return $this->error('User not found', 404);

        return $this->success($user, 'User details retrieved');
    }

    public function activate($id)
    {
        $user = User::find($id);
        if (!$user)
            return $this->error('User not found', 404);

        $user->update(['is_active' => true]);
        return $this->success($user, 'User activated');
    }

    public function deactivate($id)
    {
        $user = User::find($id);
        if (!$user)
            return $this->error('User not found', 404);

        if ($user->role->name === 'admin') {
            return $this->error('Cannot deactivate admin account', 403);
        }

        $user->update(['is_active' => false]);
        return $this->success($user, 'User deactivated');
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user)
            return $this->error('User not found', 404);

        if ($user->role->name === 'admin') {
            return $this->error('Cannot delete admin account', 403);
        }

        $user->delete();
        return $this->success(null, 'User deleted');
    }

    public function approveOrg($id)
    {
        $user = User::with('organization')->find($id);
        if (!$user)
            return $this->error('User not found', 404);

        if ($user->role->name !== 'organization') {
            return $this->error('User is not an organization', 400);
        }

        if (!$user->organization) {
            return $this->error('Organization profile not found', 404);
        }

        $user->organization->update(['verification_status' => 'approved']);
        $user->update(['is_active' => true]);

        return $this->success(null, 'Organization approved');
    }

    public function rejectOrg($id)
    {
        $user = User::with('organization')->find($id);
        if (!$user)
            return $this->error('User not found', 404);

        if ($user->role->name !== 'organization') {
            return $this->error('User is not an organization', 400);
        }

        $user->organization->update(['verification_status' => 'rejected']);
        $user->update(['is_active' => false]);

        return $this->success(null, 'Organization rejected');
    }
}
