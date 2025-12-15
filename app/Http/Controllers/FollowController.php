<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class FollowController extends Controller
{
    public function follow(Request $request, $userId)
    {
        $currentUser = Auth::user();
        if ($currentUser->id === $userId) {
            return response()->json(['message' => 'You cannot follow yourself'], 400);
        }

        $userToFollow = User::find($userId);
        if (!$userToFollow) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $currentUser->follow($userId);

        return response()->json(['message' => 'Successfully followed the user']);
    }

    public function unfollow(Request $request, $userId)
    {
        $user = Auth::user();
        $user->unfollow($userId);

        return response()->json(['message' => 'Successfully unfollowed the user.']);
    }

}


