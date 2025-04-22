        
        return $this->success(null, 'Notification deleted successfully');
    }
    
    /**
     * Get unread notification count.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount(Request $request)
    {
        $user = Auth::user();
        
        $count = Notification::where('user_id', $user->user_id)
                           ->where('is_read', false)
                           ->count();
        
        return $this->success(['count' => $count]);
    }
}
