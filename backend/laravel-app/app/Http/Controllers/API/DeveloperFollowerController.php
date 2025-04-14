        }
        
        // Get followers with pagination
        $followers = DeveloperFollower::where('developer_id', $developer->developer_id)
            ->with('user:user_id,display_name,username,avatar_url')
            ->paginate($request->input('per_page', 20));
        
        return $this->success($followers);
    }
}
