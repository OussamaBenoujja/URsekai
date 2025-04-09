                'funding' => $card->funding,
            ]);
        } catch (\Exception $e) {
            return $this->error('Could not retrieve payment method: ' . $e->getMessage(), 422);
        }
    }

    /**
     * Delete the saved Stripe payment method for the user.
     */
    public function deletePaymentMethod(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $user = Auth::user();
        if (!$user->stripe_payment_method_id) {
            return $this->success(null, 'No payment method to delete.');
        }
        $user->stripe_payment_method_id = null;
        $user->save();
        return $this->success(null, 'Payment method deleted.');
    }
}
