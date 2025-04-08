            }
        } catch (\Exception $e) {
            return $this->error('Invalid payment method: ' . $e->getMessage(), 422);
        }
    }

    /**
     * Get the saved Stripe payment method for the user.
     */
    public function getPaymentMethod(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $user = Auth::user();
        if (!$user->stripe_payment_method_id) {
            return $this->success(null, 'No payment method saved.');
        }
        try {
            $paymentMethod = PaymentMethod::retrieve($user->stripe_payment_method_id);
            $card = $paymentMethod->card;
            return $this->success([
                'id' => $paymentMethod->id,
                'brand' => $card->brand,
                'last4' => $card->last4,
                'exp_month' => $card->exp_month,
                'exp_year' => $card->exp_year,
