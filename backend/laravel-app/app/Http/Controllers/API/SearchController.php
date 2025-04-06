                    ->paginate($perPage);
                break;
            case 'groups':
                $results = Group::where('is_active', true)
                    ->where(function($q) use ($query) {
                        $q->where('name', 'like', "%$query%")
                          ->orWhere('description', 'like', "%$query%")
                          ->orWhere('slug', 'like', "%$query%")
                          ;
                    })
                    ->orderByDesc('member_count')
                    ->paginate($perPage);
                break;
            case 'players':
                $results = User::where('is_active', true)
                    ->where('role', 'player')
                    ->where(function($q) use ($query) {
                        $q->where('username', 'like', "%$query%")
                          ->orWhere('display_name', 'like', "%$query%")
                          ->orWhere('bio', 'like', "%$query%")
                          ;
                    })
                    ->orderByDesc('account_level')
                    ->paginate($perPage);
                break;
            default:
                return response()->json(['error' => 'Invalid type'], 400);
        }

        return response()->json($results);
    }
}
