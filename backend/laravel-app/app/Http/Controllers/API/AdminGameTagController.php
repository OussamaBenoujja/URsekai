            return $this->error('Validation failed', 422, $validator->errors());
        }

        $tags = GameTag::active()
                     ->where('name', 'LIKE', '%' . $request->query . '%')
                     ->withCount('games')
                     ->orderBy('games_count', 'desc')
                     ->take(20)
                     ->get();

        return $this->success($tags);
    }
}
