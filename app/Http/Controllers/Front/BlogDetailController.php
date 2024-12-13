<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class BlogDetailController extends Controller
{
    function detail($slug)
    {
        $data = Post::where('status', 'publish')
            ->where('type', 'blog')
            ->where('slug', $slug)
            ->firstOrFail();
        $pagination = $this->pagination($data->id);

        return view('components.front.blog-detail', [
            'data' => $data,
            'pagination' => $pagination
        ]);
    }

    private function pagination($id)
    {
        $dataPrev = Post::where('status', 'publish')
            ->where('type', 'blog')
            ->where('id', '<', $id)
            ->orderBy('id', 'desc')
            ->first();

        $dataNext = Post::where('status', 'publish')
            ->where('type', 'blog')
            ->where('id', '>', $id)
            ->orderBy('id', 'desc')
            ->first();

        $data = [
            'prev' => $dataPrev,
            'next' => $dataNext
        ];

        return $data;
    }
}
