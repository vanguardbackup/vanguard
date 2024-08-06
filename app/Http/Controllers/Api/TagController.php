<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Handles CRUD operations for tags in the API.
 */
class TagController extends Controller
{
    /**
     * Display a paginated listing of the user's tags.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeRequest($request, 'manage-tags');

        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $perPage = (int) $request->input('per_page', 15);
        $tags = Tag::where('user_id', $user->id)->paginate($perPage);

        return TagResource::collection($tags);
    }

    /**
     * Store a newly created tag.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeRequest($request, 'manage-tags');

        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        try {
            $validated = $this->validateTag($request);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tag = Tag::create($validated + ['user_id' => $user->id]);

        return (new TagResource($tag))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified tag.
     */
    public function show(Request $request, mixed $id): TagResource|JsonResponse
    {
        $this->authorizeRequest($request, 'manage-tags');

        $tag = $this->findTag($id);

        if (! $tag instanceof Tag) {
            return response()->json(['message' => 'Tag not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('view', $tag);

        return new TagResource($tag);
    }

    /**
     * Update the specified tag.
     */
    public function update(Request $request, mixed $id): TagResource|JsonResponse
    {
        $this->authorizeRequest($request, 'manage-tags');

        $tag = $this->findTag($id);

        if (! $tag instanceof Tag) {
            return response()->json(['message' => 'Tag not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('update', $tag);

        try {
            $validated = $this->validateTag($request, true);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tag->update($validated);

        return new TagResource($tag);
    }

    /**
     * Remove the specified tag.
     */
    public function destroy(Request $request, mixed $id): Response|JsonResponse
    {
        $this->authorizeRequest($request, 'manage-tags');

        $tag = $this->findTag($id);

        if (! $tag instanceof Tag) {
            return response()->json(['message' => 'Tag not found'], Response::HTTP_NOT_FOUND);
        }

        Gate::authorize('forceDelete', $tag);

        $tag->delete();

        return response()->noContent();
    }

    /**
     * Authorize the request based on the given ability.
     */
    private function authorizeRequest(Request $request, string $ability): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->tokenCan($ability)) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Validate the tag data.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validateTag(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];

        if ($isUpdate) {
            $rules['label'] = ['sometimes', 'required', 'string', 'max:255'];
        }

        return $request->validate($rules);
    }

    /**
     * Find a tag by ID.
     */
    private function findTag(mixed $id): ?Tag
    {
        if (! is_numeric($id)) {
            return null;
        }

        return Tag::find((int) $id);
    }
}
