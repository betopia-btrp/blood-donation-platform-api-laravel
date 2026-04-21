<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;

class UserManagementController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $users = User::with(['profile', 'organization'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->success($users, 'Users retrieved');
    }

    public function activate($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error('User not found', 404);
        }

        $user->update(['is_active' => true]);

        return $this->success($user, 'User activated');
    }

    public function deactivate($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error('User not found', 404);
        }

        if ($user->role === 'admin') {
            return $this->error('Cannot deactivate admin account', 403);
        }

        $user->update(['is_active' => false]);

        return $this->success($user, 'User deactivated');
    }
}
