        $update = PlatformUpdate::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'content' => 'string',
            'image_url' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $update->fill($request->only(['title', 'content', 'image_url', 'is_active']));
        $update->updated_at = now();
        $update->save();
        return response()->json($update);
    }

    // Delete an update (admin only)
    public function destroy(Request $request, $id)
    {
        $this->authorize('admin');
        $update = PlatformUpdate::findOrFail($id);
        $update->delete();
        return response()->json(['message' => 'Update deleted']);
    }
}
