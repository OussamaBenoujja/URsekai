            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password_hash' => Hash::make($request->string('password')),
            
        ]);

        event(new Registered($user));

        Auth::login($user);

        return response()->noContent();
    }
}
