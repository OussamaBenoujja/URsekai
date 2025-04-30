        // Check if user has developer role
        if ($user->role !== "developer" && $user->role !== "admin") {
            return null;
        }

        // Get developer profile
        return \App\Models\Developer::where("user_id", $user->user_id)->first();
    }
}
