     *
     * @return Developer|null
     */
    private function getDeveloperProfile()
    {
        $user = Auth::user();

        // Check if user has developer role
        if ($user->role !== "developer" && $user->role !== "admin") {
            return null;
        }

        // Get developer profile
        return Developer::where("user_id", $user->user_id)->first();
    }
}
