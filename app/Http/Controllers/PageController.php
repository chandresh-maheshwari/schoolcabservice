<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HomePage;
use App\Models\HeroModel;

class PageController extends Controller
{
    // Render a page by id (or slug) and include its assigned template
    public function show($id)
    {
        $page = HomePage::where('deleted', 0)->findOrFail($id);

        // For demo: use 'template' field if present; else decide by title keyword
        $template = $page->template ?? null;
        if (!$template) {
            $template = str_contains(strtolower($page->title), 'hero') ? 'hero' : 'default';
        }

        // Collect data per template
        $data = [];
        if ($template === 'hero') {
            $data['hero'] = HeroModel::where('deleted', 0)->first();
        }

        return view('pages.show', [
            'page' => $page,
            'template' => $template,
        ] + $data);
    }
}


