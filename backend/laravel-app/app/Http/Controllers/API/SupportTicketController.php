            $ticket->responded_at = now();
        }
        $ticket->updated_at = now();
        $ticket->save();

        // Notify the ticket creator
        $this->notificationService->create(
            $ticket->user_id,
            'support_ticket_' . $status,
            "Your support ticket '{$ticket->subject}' has been updated to status: {$status}.",
            'Support Ticket Update',
            [
                'link' => '/support/tickets',
                'related_id' => $ticketId,
                'related_type' => 'support_ticket'
            ]
        );

        return response()->json(['message' => 'Support ticket updated', 'data' => $ticket]);
    }
}
