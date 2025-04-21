<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Models\User;
use App\Rules\ValidHexColour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
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
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $perPage = (int) $request->input('per_page', 15);
        $tags = Tag::where('user_id', $user->getAttribute('id'))->paginate($perPage);

        return TagResource::collection($tags);
    }

    /**
     * Store a newly created tag.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $validated = $this->validateTag($request);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation Error',
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tag = Tag::create($validated + ['user_id' => $user->getAttribute('id')]);

        return (new TagResource($tag))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified tag.
     */
    public function show(Request $request, mixed $id): TagResource|JsonResponse
    {
        $tag = $this->findTag($id);

        if (! $tag instanceof Tag) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Tag not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if (Gate::denies('view', $tag)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You are not authorized to view this tag',
            ], Response::HTTP_FORBIDDEN);
        }

        return new TagResource($tag);
    }

    /**
     * Update the specified tag.
     */
    public function update(Request $request, mixed $id): TagResource|JsonResponse
    {
        $tag = $this->findTag($id);

        if (! $tag instanceof Tag) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Tag not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if (Gate::denies('update', $tag)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You are not authorized to update this tag',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $validated = $this->validateTag($request, true);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation Error',
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
        $tag = $this->findTag($id);

        if (! $tag instanceof Tag) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Tag not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if (Gate::denies('forceDelete', $tag)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You are not authorized to delete this tag',
            ], Response::HTTP_FORBIDDEN);
        }

        $tag->delete();

        return response()->noContent();
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
            'colour' => ['nullable', 'string', new ValidHexColour],
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
