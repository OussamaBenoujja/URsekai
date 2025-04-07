    }

    /**
     * Disable 2FA after verifying password.
     */
    public function disable(Request $request)
    {
        $user = Auth::user();
        $request->validate(['password' => 'required|string']);
        if (!\Hash::check($request->password, $user->password)) {
            return $this->error('Incorrect password.', 401);
        }
        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->save();
        return $this->success(null, '2FA disabled successfully.');
    }
}
