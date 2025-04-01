        foreach ($users as $email => $userId) {
            $rows[] = [
                'user_id' => $userId,
                'email_address' => $email,
                'subject' => $subject,
                'body' => $body,
                'html_body' => $htmlBody,
                'status' => 'pending',
                'created_at' => $now,
                'email_type' => 'newsletter',
            ];
        }
        if ($rows) {
            DB::table('email_queue')->insert($rows);
        }
        return response()->json(['message' => 'Newsletter queued for all users', 'count' => count($rows)]);
    }
}
