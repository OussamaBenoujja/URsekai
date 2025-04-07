            $user->ban_expires = null;
        }
        $user->save();
        return response()->json(['message' => 'User banned successfully']);
    }

    // POST /api/v1/admin/users/{id}/unban
    public function unban($id)
    {
        $user = User::findOrFail($id);
        $user->is_banned = false;
        $user->ban_reason = null;
        $user->ban_expires = null;
        $user->save();
        return response()->json(['message' => 'User unbanned successfully']);
    }
}
