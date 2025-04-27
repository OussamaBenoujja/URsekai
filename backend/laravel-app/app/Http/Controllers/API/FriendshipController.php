            ->concat($forumPosts)
            ->sortByDesc('created_at')
            ->values()
            ->take(30)
            ->toArray();

        return $this->success(['activities' => $allActivities]);
    }
}
