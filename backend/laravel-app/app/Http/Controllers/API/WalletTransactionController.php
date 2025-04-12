            'transaction_' . $type,
            $message,
            'Transaction Update',
            [
                'link' => '/transactions',
                'related_id' => $transactionId,
                'related_type' => 'transaction'
            ]
        );
    }
}
