                   ->where('is_read', false)
                   ->update([
                       'is_read' => true,
                       'read_at' => now()
                   ]);
        
        return $this->success(null, 'All notifications marked as read');
    }
    
    /**
     * Delete a notification.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();
        
        $notification = Notification::where('notification_id', $id)
                                   ->where('user_id', $user->user_id)
                                   ->firstOrFail();
        
        $notification->delete();
