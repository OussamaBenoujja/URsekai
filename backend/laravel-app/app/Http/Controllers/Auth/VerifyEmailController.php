
        return redirect()->intended(
            config('app.frontend_url').'/dashboard?verified=1'
        );
    }
}
