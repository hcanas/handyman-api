<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DesignateUserRequest;
use App\Models\User;

class DesignateUserController extends Controller
{
    public function __invoke(DesignateUserRequest $request)
    {
        User::query()
            ->where('id', $request->validated('user_id'))
            ->update([
                'role' => $request->validated('role'),
                'department_id' => $request->validated('department_id'),
            ]);

        return response()->json([
            'message' => 'User has been designated successfully',
        ]);
    }
}
