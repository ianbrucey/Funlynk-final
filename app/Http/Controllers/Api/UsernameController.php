<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UsernameController extends Controller
{
    /**
     * Check if a username is available.
     */
    public function checkAvailability(Request $request)
    {
        $request->validate([
            'username' => 'required|string|min:3|max:50',
            'exclude_user_id' => 'nullable|string|exists:users,id',
        ]);

        $username = Str::lower(Str::slug($request->username));

        // Check if username exists, excluding current user if provided
        $query = User::where('username', $username);
        
        if ($request->exclude_user_id) {
            $query->where('id', '!=', $request->exclude_user_id);
        }

        $exists = $query->exists();

        return response()->json([
            'available' => !$exists,
            'username' => $username,
        ]);
    }
}

