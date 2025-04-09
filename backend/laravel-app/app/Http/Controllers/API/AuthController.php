     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            "access_token" => $token,
            "token_type" => "bearer",
            "expires_in" => Auth::guard("api")->factory()->getTTL() * 60,
            "user" => Auth::guard("api")->user(),
        ]);
    }
}
