<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PageDetailController extends Controller
{
    function detail($slug)
    {
        $data = Post::where('status', 'publish')
            ->where('type', 'page')
            ->where('slug', $slug)
            ->firstOrFail();

        return view('components.front.page-detail', [
            'data' => $data,
        ]);
    }
}
