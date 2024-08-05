<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TagController extends Controller
{
    /**
     * Display a paginated listing of the tags.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('manage-tags')) {
            abort(403, 'Unauthorized action.');
        }

        $perPage = $request->input('per_page', 15);
        $tags = Tag::where('user_id', $user->id)->paginate($perPage);

        return TagResource::collection($tags);
    }

    /**
     * Store a newly created tag in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('manage-tags')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'label' => ['required', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $tag = Tag::create($validated + ['user_id' => $user->id]);

        return (new TagResource($tag))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified tag.
     */
    public function show(Request $request, Tag $tag): TagResource
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('manage-tags')) {
            abort(403, 'Unauthorized action.');
        }

        Gate::authorize('view', $tag);

        return new TagResource($tag);
    }

    /**
     * Update the specified tag in storage.
     */
    public function update(Request $request, Tag $tag): TagResource
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('manage-tags')) {
            abort(403, 'Unauthorized action.');
        }

        Gate::authorize('update', $tag);

        $validated = $request->validate([
            'label' => ['sometimes', 'required', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $tag->update($validated);

        return new TagResource($tag);
    }

    /**
     * Remove the specified tag from storage.
     */
    public function destroy(Request $request, Tag $tag): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan('manage-tags')) {
            abort(403, 'Unauthorized action.');
        }

        Gate::authorize('forceDelete', $tag);

        $tag->delete();

        return response()->noContent();
    }
}
