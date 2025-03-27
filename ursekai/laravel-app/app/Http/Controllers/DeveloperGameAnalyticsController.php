<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Developer;
use App\Models\AnalyticsGameMetrics;
use App\Models\GamePlaytime;
use App\Models\Transaction;
use App\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeveloperGameAnalyticsController extends Controller
{
    use ApiResponser;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Get general analytics for a game.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $gameId)
    {
        // Ensure user has a developer profile
        $developer = $this->getDeveloperProfile();
        if (!$developer) {
            return $this->error('Developer profile not found', 404);
        }

        // Ensure the game belongs to this developer
        $game = Game::where('game_id', $gameId)
                    ->where('developer_id', $developer->developer_id)
                    ->firstOrFail();

        // Get date range (default to last 30 days)
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(30);
        
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            
            // Limit date range to 365 days max
            if ($startDate->diffInDays($endDate) > 365) {
                $startDate = $endDate->copy()->subDays(365);
            }
        }

        // Get daily metrics for the date range
        $metrics = AnalyticsGameMetrics::where('game_id', $gameId)
                                    ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                                    ->orderBy('date', 'asc')
                                    ->get();

        // Calculate summary statistics
        $summary = [
            'total_plays' => $metrics->sum('total_plays'),
            'unique_players' => $metrics->sum('unique_players'),
            'new_players' => $metrics->sum('new_players'),
            'total_playtime_minutes' => $metrics->sum('total_playtime_minutes'),
            'average_playtime_minutes' => $metrics->avg('average_playtime_minutes'),
            'total_revenue' => $metrics->sum('revenue'),
            'ad_revenue' => $metrics->sum('ad_revenue'),
            'average_rating' => $metrics->avg('average_rating'),
            'ratings_count' => $metrics->sum('ratings_count'),
            'achievement_unlocks' => $metrics->sum('achievement_unlocks'),
            'in_app_purchases' => $metrics->sum('in_app_purchases'),
        ];

        // Get peak concurrent players across all days
        $summary['peak_concurrent_players'] = $metrics->max('peak_concurrent_players');

        // Format the response
        $response = [
            'summary' => $summary,
            'daily' => $metrics,
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'total_days' => $startDate->diffInDays($endDate) + 1,
            ],
        ];

        return $this->success($response);
    }

    /**
     * Get user analytics for a game.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function users(Request $request, $gameId)
    {
        // Ensure user has a developer profile
        $developer = $this->getDeveloperProfile();
        if (!$developer) {
            return $this->error('Developer profile not found', 404);
        }

        // Ensure the game belongs to this developer
        $game = Game::where('game_id', $gameId)
                    ->where('developer_id', $developer->developer_id)
                    ->firstOrFail();

        // Get date range (default to last 30 days)
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(30);
        
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            
            // Limit date range to 365 days max
            if ($startDate->diffInDays($endDate) > 365) {
                $startDate = $endDate->copy()->subDays(365);
            }
        }

        // Get user demographics from game_playtime
        $demographics = GamePlaytime::where('game_id', $gameId)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->select(
                DB::raw('COUNT(DISTINCT user_id) as total_users'),
                DB::raw('COUNT(DISTINCT CASE WHEN user_id IS NULL THEN session_id ELSE NULL END) as anonymous_users'),
                DB::raw('SUM(duration_minutes) as total_playtime'),
                DB::raw('AVG(duration_minutes) as avg_session_length')
            )
            ->first();

        // Get country distribution
        $countries = GamePlaytime::where('game_id', $gameId)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->whereNotNull('country')
            ->select('country', DB::raw('COUNT(DISTINCT COALESCE(user_id, session_id)) as user_count'))
            ->groupBy('country')
            ->orderBy('user_count', 'desc')
            ->limit(10)
            ->get();

        // Get device distribution
        $devices = GamePlaytime::where('game_id', $gameId)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->whereNotNull('device_type')
            ->select('device_type', DB::raw('COUNT(DISTINCT COALESCE(user_id, session_id)) as user_count'))
            ->groupBy('device_type')
            ->orderBy('user_count', 'desc')
            ->get();

        // Get browser distribution
        $browsers = GamePlaytime::where('game_id', $gameId)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->whereNotNull('browser')
            ->select('browser', DB::raw('COUNT(DISTINCT COALESCE(user_id, session_id)) as user_count'))
            ->groupBy('browser')
            ->orderBy('user_count', 'desc')
            ->get();

        // Get OS distribution
        $operatingSystems = GamePlaytime::where('game_id', $gameId)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->whereNotNull('operating_system')
            ->select('operating_system', DB::raw('COUNT(DISTINCT COALESCE(user_id, session_id)) as user_count'))
            ->groupBy('operating_system')
            ->orderBy('user_count', 'desc')
            ->get();

        // Get retention data (return rate)
        $retention = DB::select(
            "SELECT 
                DATE_FORMAT(first_day.start_time, '%Y-%m-%d') as cohort_date,
                COUNT(DISTINCT first_day.user_id) as cohort_size,
                SUM(CASE WHEN DATEDIFF(return_day.start_time, first_day.start_time) BETWEEN 0 AND 1 THEN 1 ELSE 0 END) / COUNT(DISTINCT first_day.user_id) * 100 as day1_retention,
                SUM(CASE WHEN DATEDIFF(return_day.start_time, first_day.start_time) BETWEEN 0 AND 7 THEN 1 ELSE 0 END) / COUNT(DISTINCT first_day.user_id) * 100 as day7_retention,
                SUM(CASE WHEN DATEDIFF(return_day.start_time, first_day.start_time) BETWEEN 0 AND 30 THEN 1 ELSE 0 END) / COUNT(DISTINCT first_day.user_id) * 100 as day30_retention
            FROM 
                (
                    SELECT 
                        user_id, 
                        MIN(start_time) as start_time 
                    FROM 
                        game_playtime 
                    WHERE 
                        game_id = ? AND 
                        user_id IS NOT NULL AND
                        start_time BETWEEN ? AND ?
                    GROUP BY 
                        user_id
                ) as first_day
            LEFT JOIN 
                game_playtime as return_day ON 
                first_day.user_id = return_day.user_id AND 
                return_day.game_id = ? AND
                return_day.start_time >= first_day.start_time
            GROUP BY 
                cohort_date
            ORDER BY 
                cohort_date",
            [$gameId, $startDate, $endDate, $gameId]
        );

        // Format the response
        $response = [
            'demographics' => $demographics,
            'countries' => $countries,
            'devices' => $devices,
            'browsers' => $browsers,
            'operating_systems' => $operatingSystems,
            'retention' => $retention,
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'total_days' => $startDate->diffInDays($endDate) + 1,
            ],
        ];

        return $this->success($response);
    }

    /**
     * Get revenue analytics for a game.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function revenue(Request $request, $gameId)
    {
        // Ensure user has a developer profile
        $developer = $this->getDeveloperProfile();
        if (!$developer) {
            return $this->error('Developer profile not found', 404);
        }

        // Ensure the game belongs to this developer
        $game = Game::where('game_id', $gameId)
                    ->where('developer_id', $developer->developer_id)
                    ->firstOrFail();

        // Get date range (default to last 30 days)
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(30);
        
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            
            // Limit date range to 365 days max
            if ($startDate->diffInDays($endDate) > 365) {
                $startDate = $endDate->copy()->subDays(365);
            }
        }

        // Get daily revenue
        $dailyRevenue = Transaction::where('game_id', $gameId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('SUM(platform_fee) as platform_fee'),
                DB::raw('SUM(developer_cut) as developer_cut'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Get total revenue summary
        $revenueSummary = Transaction::where('game_id', $gameId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('SUM(amount) as total_revenue'),
                DB::raw('SUM(platform_fee) as total_platform_fee'),
                DB::raw('SUM(developer_cut) as total_developer_cut'),
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('COUNT(DISTINCT user_id) as unique_paying_users')
            )
            ->first();

        // Calculate ARPPU (Average Revenue Per Paying User)
        $revenueSummary->arppu = $revenueSummary->unique_paying_users > 0 
            ? $revenueSummary->total_revenue / $revenueSummary->unique_paying_users
            : 0;

        // Get revenue by transaction type
        $revenueByType = Transaction::where('game_id', $gameId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'transaction_type',
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('transaction_type')
            ->orderBy('revenue', 'desc')
            ->get();

        // Get ad revenue if available from analytics metrics
        $adRevenue = AnalyticsGameMetrics::where('game_id', $gameId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select(
                'date',
                'ad_impressions',
                'ad_clicks',
                'ad_revenue'
            )
            ->orderBy('date', 'asc')
            ->get();

        $adRevenueSummary = [
            'total_impressions' => $adRevenue->sum('ad_impressions'),
            'total_clicks' => $adRevenue->sum('ad_clicks'),
            'total_revenue' => $adRevenue->sum('ad_revenue'),
            'click_through_rate' => $adRevenue->sum('ad_impressions') > 0 
                ? ($adRevenue->sum('ad_clicks') / $adRevenue->sum('ad_impressions')) * 100
                : 0,
            'revenue_per_thousand_impressions' => $adRevenue->sum('ad_impressions') > 0
                ? ($adRevenue->sum('ad_revenue') / $adRevenue->sum('ad_impressions')) * 1000
                : 0
        ];

        // Format the response
        $response = [
            'summary' => $revenueSummary,
            'daily_revenue' => $dailyRevenue,
            'revenue_by_type' => $revenueByType,
            'ad_revenue' => [
                'summary' => $adRevenueSummary,
                'daily' => $adRevenue
            ],
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'total_days' => $startDate->diffInDays($endDate) + 1,
            ],
        ];

        return $this->success($response);
    }

    /**
     * Get playtime analytics for a game.
     *
     * @param Request $request
     * @param int $gameId
     * @return \Illuminate\Http\JsonResponse
     */
    public function playtime(Request $request, $gameId)
    {
        // Ensure user has a developer profile
        $developer = $this->getDeveloperProfile();
        if (!$developer) {
            return $this->error('Developer profile not found', 404);
        }

        // Ensure the game belongs to this developer
        $game = Game::where('game_id', $gameId)
                    ->where('developer_id', $developer->developer_id)
                    ->firstOrFail();

        // Get date range (default to last 30 days)
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(30);
        
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            
            // Limit date range to 365 days max
            if ($startDate->diffInDays($endDate) > 365) {
                $startDate = $endDate->copy()->subDays(365);
            }
        }

        // Get daily playtime statistics
        $dailyPlaytime = GamePlaytime::where('game_id', $gameId)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(start_time) as date'),
                DB::raw('COUNT(*) as sessions'),
                DB::raw('COUNT(DISTINCT COALESCE(user_id, session_id)) as unique_players'),
                DB::raw('SUM(duration_minutes) as total_minutes'),
                DB::raw('AVG(duration_minutes) as avg_session_minutes')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Get playtime distribution (session length buckets)
        $playtimeDistribution = GamePlaytime::where('game_id', $gameId)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->select(
                DB::raw('
                    CASE
                        WHEN duration_minutes < 1 THEN "< 1 min"
                        WHEN duration_minutes < 5 THEN "1-5 mins"
                        WHEN duration_minutes < 15 THEN "5-15 mins"
                        WHEN duration_minutes < 30 THEN "15-30 mins"
                        WHEN duration_minutes < 60 THEN "30-60 mins"
                        ELSE "> 60 mins"
                    END as session_length
                '),
                DB::raw('COUNT(*) as session_count')
            )
            ->groupBy('session_length')
            ->orderBy(DB::raw('
                CASE
                    WHEN session_length = "< 1 min" THEN 1
                    WHEN session_length = "1-5 mins" THEN 2
                    WHEN session_length = "5-15 mins" THEN 3
                    WHEN session_length = "15-30 mins" THEN 4
                    WHEN session_length = "30-60 mins" THEN 5
                    ELSE 6
                END
            '))
            ->get();

        // Get user engagement (sessions per user)
        $userEngagement = GamePlaytime::where('game_id', $gameId)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->whereNotNull('user_id')
            ->select(
                'user_id',
                DB::raw('COUNT(*) as session_count'),
                DB::raw('SUM(duration_minutes) as total_minutes')
            )
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) >= 1')
            ->get();

        // Calculate engagement metrics
        $engagementMetrics = [
            'total_sessions' => $userEngagement->sum('session_count'),
            'total_users' => $userEngagement->count(),
            'avg_sessions_per_user' => $userEngagement->count() > 0 
                ? $userEngagement->sum('session_count') / $userEngagement->count()
                : 0,
            'avg_minutes_per_user' => $userEngagement->count() > 0
                ? $userEngagement->sum('total_minutes') / $userEngagement->count()
                : 0,
            'user_retention' => [
                'played_once' => $userEngagement->where('session_count', 1)->count(),
                'played_2_5_times' => $userEngagement->whereBetween('session_count', [2, 5])->count(),
                'played_6_20_times' => $userEngagement->whereBetween('session_count', [6, 20])->count(),
                'played_more_than_20_times' => $userEngagement->where('session_count', '>', 20)->count(),
            ]
        ];

        // Format the response
        $response = [
            'daily_playtime' => $dailyPlaytime,
            'playtime_distribution' => $playtimeDistribution,
            'engagement_metrics' => $engagementMetrics,
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'total_days' => $startDate->diffInDays($endDate) + 1,
            ],
        ];

        return $this->success($response);
    }

    /**
     * Helper method to get the developer profile for the authenticated user.
     *
     * @return Developer|null
     */
    private function getDeveloperProfile()
    {
        $user = Auth::user();
        
        // Check if user has developer role
        if ($user->role !== 'developer' && $user->role !== 'admin') {
            return null;
        }
        
        // Get developer profile
        return Developer::where('user_id', $user->user_id)->first();
    }
}