<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class HomepageController extends Controller
{
    function index()
    {
        $lastData = $this->lastData();

        $data = Post::where('status', 'publish')
            ->where('type', 'blog')
            ->where('id', '!=', $lastData->id)
            ->orderBy('id', 'desc')
            ->paginate(5);

        return view('components.front.home-pages', [
            'data' => $data,
            'lastData' => $lastData,
        ]);
    }

    function lastData()
    {
        $data = Post::where('status', 'publish')
            ->where('type', 'blog')
            ->orderBy('id', 'desc')
            ->latest()
            ->first();

        return $data;
    }
}
