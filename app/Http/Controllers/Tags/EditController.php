<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tags;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for editing tags.
 *
 * This controller is responsible for displaying the edit form
 * for a specific tag.
 */
class EditController extends Controller
{
    /**
     * Display the edit form for the specified tag.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  Tag  $tag  The tag to be edited.
     * @return View The view containing the edit form.
     */
    public function __invoke(Request $request, Tag $tag): View
    {
        return view('tags.edit', [
            'tag' => $tag,
        ]);
    }
}
