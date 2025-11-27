<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PostReactionController extends Controller
{
    public function __construct(private PostService $postService)
    {
    }

    /**
     * React to a post.
     */
    public function react(Request $request, Post $post): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reaction_type' => 'required|in:im_down,invite_friends',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $reaction = $this->postService->reactToPost(
                $post->id,
                $request->input('reaction_type'),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'reaction' => $reaction,
                'message' => 'Reaction added successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove reaction from a post.
     */
    public function unreact(Request $request, Post $post): JsonResponse
    {
        try {
            $deleted = $post->reactions()
                ->where('user_id', $request->user()->id)
                ->delete();

            // Update reaction count
            $reactionCount = $post->reactions()->count();
            $post->update(['reaction_count' => $reactionCount]);

            return response()->json([
                'success' => true,
                'message' => 'Reaction removed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all reactions for a post.
     */
    public function getReactions(Post $post): JsonResponse
    {
        try {
            $reactions = $this->postService->getPostReactions($post->id);

            return response()->json([
                'success' => true,
                'reactions' => $reactions,
                'count' => $reactions->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Invite friends to a post.
     */
    public function invite(Request $request, Post $post): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'friend_ids' => 'required|array',
            'friend_ids.*' => 'required|uuid|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $invitations = $this->postService->inviteFriendsToPost(
                $post->id,
                $request->input('friend_ids'),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'invitations' => $invitations,
                'count' => $invitations->count(),
                'message' => 'Invitations sent successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pending invitations for the authenticated user.
     */
    public function getInvitations(Request $request): JsonResponse
    {
        try {
            $invitations = $this->postService->getUserPendingInvitations($request->user()->id);

            return response()->json([
                'success' => true,
                'invitations' => $invitations,
                'count' => $invitations->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
