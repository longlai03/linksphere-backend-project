<?php

namespace App\Http\Controllers;

use App\Services\Follower\FollowerService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FollowerController extends Controller
{
    private FollowerService $followerService;

    public function __construct(FollowerService $followerService)
    {
        $this->followerService = $followerService;
    }

    public function follow($userId): JsonResponse
    {
        try {
            $follower = Auth::user();
            if (!$follower) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $result = $this->followerService->follow($follower->id, $userId);
            if (isset($result['error'])) {
                return response()->json(['message' => $result['error']], 400);
            }
            return response()->json(['message' => $result['message']]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error following user: ' . $e->getMessage()], 500);
        }
    }

    public function acceptFollow(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'follower_id' => 'required|exists:users,id'
            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $result = $this->followerService->acceptFollow($user->id, $request->follower_id);
            if (isset($result['error'])) {
                return response()->json(['message' => $result['error']], 404);
            }
            return response()->json(['message' => $result['message']]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error accepting follow request: ' . $e->getMessage()], 500);
        }
    }

    public function declineFollow(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'follower_id' => 'required|exists:users,id'
            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $result = $this->followerService->declineFollow($user->id, $request->follower_id);
            if (isset($result['error'])) {
                return response()->json(['message' => $result['error']], 404);
            }
            return response()->json(['message' => $result['message']]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error declining follow request: ' . $e->getMessage()], 500);
        }
    }

    public function unfollow($userId): JsonResponse
    {
        try {
            $follower = Auth::user();
            if (!$follower) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $result = $this->followerService->unfollow($follower->id, $userId);
            if (isset($result['error'])) {
                return response()->json(['message' => $result['error']], 404);
            }
            return response()->json(['message' => $result['message']]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error unfollowing user: ' . $e->getMessage()], 500);
        }
    }

    public function getPendingFollowRequests(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $result = $this->followerService->getPendingFollowRequests($user->id);

            if (isset($result['error'])) {
                return response()->json(['error' => $result['error']], 500);
            }
            return response()->json(['pending_requests' => $result['pending_requests']]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error getting pending follow requests: ' . $e->getMessage()], 500);
        }
    }
}
