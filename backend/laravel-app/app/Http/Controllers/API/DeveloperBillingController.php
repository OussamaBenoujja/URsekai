    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $user = Auth::user();
        $developer = Developer::where('user_id', $user->user_id)->firstOrFail();
        if (!$developer->stripe_payment_method_id) {
            return $this->success(null, 'No payment method to delete.');
        }
        // Optionally, detach or delete from Stripe here if needed
        $developer->stripe_payment_method_id = null;
        $developer->save();
        return $this->success(null, 'Payment method deleted.');
    }
}
