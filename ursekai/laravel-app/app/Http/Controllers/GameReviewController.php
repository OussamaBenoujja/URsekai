<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameReview;
use App\Models\ReviewVote;
use App\Models\ReviewComment;
use App\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GameReviewController extends Controller
{
    use ApiResponser;

    /**
     * Get reviews for a specific game.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $gameId)
    {
        $game = Game::findOrFail($gameId);
        
        $query = GameReview::where('game_id', $gameId)
                         ->visible();

        // Filter by rating
        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        // Sort options
        $sort = $request->input('sort', 'recent');
        if ($sort === 'helpful') {
            $query->byHelpfulness();
        } else {
            $query->recent();
        }

        // Include user details
        $query->with(['user:user_id,username,display_name,avatar_url']);

        // Paginate results
        $reviews = $query->paginate($request->input('per_page', 10));

        // Add user's vote if authenticated
        if (Auth::check()) {
            $reviews->getCollection()->transform(function ($review) {
                $vote = ReviewVote::where('review_id', $review->review_id)
                                ->where('user_id', Auth::id())
                                ->first();
                $review->user_vote = $vote ? $vote->vote_type : null;
                return $review;
            });
        }

        return $this->success($reviews);
    }

    /**
     * Create a new review for a game.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $gameId)
    {
        if (!Auth::check()) {
            return $this->error('Authentication required', 401);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
            'title' => 'required|string|max:100',
            'content' => 'required|string|min:10',
            'has_spoilers' => 'boolean',
            'playtime_minutes' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $game = Game::findOrFail($gameId);
        
        // Check if user has already reviewed this game
        $existingReview = GameReview::where('game_id', $gameId)
                                   ->where('user_id', Auth::id())
                                   ->first();
                                   
        if ($existingReview) {
            return $this->error('You have already reviewed this game', 422);
        }

        // Get user's game progress to verify they've played the game
        $userProgress = \App\Models\UserGameProgress::where('user_id', Auth::id())
                                                 ->where('game_id', $gameId)
                                                 ->first();
                                                 
        $isVerifiedPlayer = $userProgress !== null;
        
        // Check if user has purchased the game (if it's a premium game)
        $isVerifiedPurchase = false;
        if ($game->monetization_type !== 'free' && $game->price > 0) {
            $purchaseExists = \App\Models\Transaction::where('user_id', Auth::id())
                                                  ->where('game_id', $gameId)
                                                  ->where('transaction_type', 'purchase')
                                                  ->where('status', 'completed')
                                                  ->exists();
            $isVerifiedPurchase = $purchaseExists;
        } else {
            // Free games are automatically "verified purchases"
            $isVerifiedPurchase = true;
        }

        // Create the review
        $review = new GameReview([
            'user_id' => Auth::id(),
            'game_id' => $gameId,
            'rating' => $request->rating,
            'title' => $request->title,
            'content' => $request->content,
            'has_spoilers' => $request->input('has_spoilers', false),
            'playtime_at_review_minutes' => $request->input('playtime_minutes', $userProgress ? $userProgress->total_time_played_minutes : null),
            'is_verified_purchase' => $isVerifiedPurchase,
            'is_verified_player' => $isVerifiedPlayer,
            'device_type' => $request->header('X-Device-Type'),
            'browser' => $request->header('User-Agent'),
            'created_at' => now(),
        ]);

        $review->save();

        // Update game rating (this would be handled by a database trigger in production)
        $this->updateGameRating($gameId);

        return $this->success($review, 'Review submitted successfully');
    }

    /**
     * Update an existing review.
     *
     * @param Request $request
     * @param int $reviewId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $reviewId)
    {
        if (!Auth::check()) {
            return $this->error('Authentication required', 401);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
            'title' => 'required|string|max:100',
            'content' => 'required|string|min:10',
            'has_spoilers' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Find the review and make sure it belongs to the authenticated user
        $review = GameReview::where('review_id', $reviewId)
                          ->where('user_id', Auth::id())
                          ->firstOrFail();

        // Update the review
        $review->update([
            'rating' => $request->rating,
            'title' => $request->title,
            'content' => $request->content,
            'has_spoilers' => $request->input('has_spoilers', $review->has_spoilers),
            'updated_at' => now(),
        ]);

        // Update game rating
        $this->updateGameRating($review->game_id);

        return $this->success($review, 'Review updated successfully');
    }

    /**
     * Delete a review.
     *
     * @param int $reviewId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($reviewId)
    {
        if (!Auth::check()) {
            return $this->error('Authentication required', 401);
        }

        // Find the review and make sure it belongs to the authenticated user or user is admin/moderator
        $review = GameReview::where('review_id', $reviewId);
        
        if (Auth::user()->role !== 'admin' && Auth::user()->role !== 'moderator') {
            $review->where('user_id', Auth::id());
        }
        
        $review = $review->firstOrFail();

        $gameId = $review->game_id;
        
        // Delete the review
        $review->delete();

        // Update game rating
        $this->updateGameRating($gameId);

        return $this->success(null, 'Review deleted successfully');
    }

    /**
     * Vote on a review (helpful/not helpful).
     *
     * @param Request $request
     * @param int $reviewId
     * @return \Illuminate\Http\JsonResponse
     */
    public function vote(Request $request, $reviewId)
    {
        if (!Auth::check()) {
            return $this->error('Authentication required', 401);
        }

        $validator = Validator::make($request->all(), [
            'vote' => 'required|in:upvote,downvote,remove',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $review = GameReview::findOrFail($reviewId);
        
        // Check if user is trying to vote on their own review
        if ($review->user_id === Auth::id()) {
            return $this->error('You cannot vote on your own review', 422);
        }

        // Check if user has already voted on this review
        $existingVote = ReviewVote::where('review_id', $reviewId)
                                ->where('user_id', Auth::id())
                                ->first();

        if ($request->vote === 'remove' && $existingVote) {
            // Remove vote
            if ($existingVote->vote_type === 'upvote') {
                $review->decrement('upvotes');
            } else {
                $review->decrement('downvotes');
            }
            
            $existingVote->delete();
            
            return $this->success(['upvotes' => $review->upvotes, 'downvotes' => $review->downvotes], 'Vote removed');
        } else if ($existingVote) {
            // Update vote if it's different
            if ($existingVote->vote_type !== $request->vote) {
                if ($existingVote->vote_type === 'upvote') {
                    $review->decrement('upvotes');
                    $review->increment('downvotes');
                } else {
                    $review->decrement('downvotes');
                    $review->increment('upvotes');
                }
                
                $existingVote->vote_type = $request->vote;
                $existingVote->save();
            }
        } else {
            // Create new vote
            ReviewVote::create([
                'review_id' => $reviewId,
                'user_id' => Auth::id(),
                'vote_type' => $request->vote,
                'created_at' => now(),
            ]);
            
            if ($request->vote === 'upvote') {
                $review->increment('upvotes');
            } else {
                $review->increment('downvotes');
            }
        }

        return $this->success([
            'upvotes' => $review->upvotes, 
            'downvotes' => $review->downvotes
        ], 'Vote recorded');
    }

    /**
     * Get comments for a review.
     *
     * @param Request $request
     * @param int $reviewId
     * @return \Illuminate\Http\JsonResponse
     */
    public function comments(Request $request, $reviewId)
    {
        $review = GameReview::findOrFail($reviewId);
        
        $comments = ReviewComment::where('review_id', $reviewId)
                               ->where('is_hidden', false)
                               ->with(['user:user_id,username,display_name,avatar_url'])
                               ->orderBy('created_at', 'asc')
                               ->paginate($request->input('per_page', 20));

        return $this->success($comments);
    }

    /**
     * Add a comment to a review.
     *
     * @param Request $request
     * @param int $reviewId
     * @return \Illuminate\Http\JsonResponse
     */
    public function addComment(Request $request, $reviewId)
    {
        if (!Auth::check()) {
            return $this->error('Authentication required', 401);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|min:2|max:1000',
            'parent_comment_id' => 'nullable|exists:review_comments,comment_id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $review = GameReview::findOrFail($reviewId);
        
        // Create the comment
        $comment = ReviewComment::create([
            'review_id' => $reviewId,
            'user_id' => Auth::id(),
            'parent_comment_id' => $request->input('parent_comment_id'),
            'content' => $request->content,
            'created_at' => now(),
        ]);

        // Load the user relationship
        $comment->load('user:user_id,username,display_name,avatar_url');

        return $this->success($comment, 'Comment added successfully');
    }

    /**
     * Delete a comment.
     *
     * @param int $commentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteComment($commentId)
    {
        if (!Auth::check()) {
            return $this->error('Authentication required', 401);
        }

        // Find the comment and make sure it belongs to the authenticated user or user is admin/moderator
        $comment = ReviewComment::where('comment_id', $commentId);
        
        if (Auth::user()->role !== 'admin' && Auth::user()->role !== 'moderator') {
            $comment->where('user_id', Auth::id());
        }
        
        $comment = $comment->firstOrFail();
        
        // Delete the comment
        $comment->delete();

        return $this->success(null, 'Comment deleted successfully');
    }

    /**
     * Helper function to update a game's average rating.
     *
     * @param int $gameId
     * @return void
     */
    private function updateGameRating($gameId)
    {
        $avgRating = GameReview::where('game_id', $gameId)
                            ->where('is_hidden', false)
                            ->avg('rating');
                            
        $totalRatings = GameReview::where('game_id', $gameId)
                                ->where('is_hidden', false)
                                ->count();
                                
        $game = Game::find($gameId);
        
        if ($game) {
            $game->average_rating = $avgRating ?? 0;
            $game->total_ratings = $totalRatings;
            $game->save();
        }
    }
}