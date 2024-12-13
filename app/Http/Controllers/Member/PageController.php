<?php

namespace App\Http\Controllers\Member;

use App\Models\Post;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PageController extends Controller
{
    protected $type = 'page';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $data = Post::where('type', $this->type)
            ->where(function ($query) use ($search) {
                if ($search) {
                    $query->where('title', 'like', '%' . $search . '%')
                        ->orWhere('content', 'like', '%' . $search . '%');
                }
            })
            ->orderBy('id', 'desc')
            ->paginate(3)
            ->withQueryString();
        return view('member.pages.index', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('member.pages.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
            'thumbnail' => 'image|mimes:jpg,jpeg,png|max:10240',
        ], [
            'title.required' => 'Judul wajib diisi!',
            'content.required' => 'Konten wajib diisi!',
            'thumbnail.image' => 'Thumbnail harus berupa gambar!',
            'thumbnail.mimes' => 'Ekstensi yang diperbolehkan hanya untuk format jpeg, jpg, dan png!',
            'thumbnail.max' => 'Ukuran gambar tidak boleh melebihi 10MB',
        ]);


        if ($request->hasFile('thumbnail')) {
            $image = $request->file('thumbnail');
            $image_name = time() . '_' . $image->getClientOriginalName();
            $destination_path = public_path(getenv('CUSTOM_THUMBNAIL_LOCATION'));
            $image->move($destination_path, $image_name);
        }

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'content' => $request->content,
            'status' => $request->status,
            'thumbnail' => isset($image_name) ? $image_name : null,
            'slug' => $this->generateSlug($request->title), // generate slug
            'user_id' => Auth::user()->id,
            'type' => $this->type
        ];

        Post::create($data);
        return redirect()->route('member.pages.index')->with('success', 'Data berhasil ditambah!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        if ($post->type != $this->type) {
            return redirect()->route('member.pages.index');
        }

        $data = Post::findOrFail($post->id);
        return view('member.pages.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
            'thumbnail' => 'image|mimes:jpg,jpeg,png|max:10240',
        ], [
            'title.required' => 'Judul wajib diisi!',
            'content.required' => 'Konten wajib diisi!',
            'thumbnail.image' => 'Thumbnail harus berupa gambar!',
            'thumbnail.mimes' => 'Ekstensi yang diperbolehkan hanya untuk format jpeg, jpg, dan png!',
            'thumbnail.max' => 'Ukuran gambar tidak boleh melebihi 10MB',
        ]);


        if ($request->hasFile('thumbnail')) {
            if (isset($post->thumbnail) && file_exists(public_path(getenv('CUSTOM_THUMBNAIL_LOCATION')) . '/' . $post->thumbnail)) {
                unlink(public_path(getenv('CUSTOM_THUMBNAIL_LOCATION')) . '/' . $post->thumbnail);
            }
            $image = $request->file('thumbnail');
            $image_name = time() . '_' . $image->getClientOriginalName();
            $destination_path = public_path(getenv('CUSTOM_THUMBNAIL_LOCATION'));
            $image->move($destination_path, $image_name);
        }

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'content' => $request->content,
            'status' => $request->status,
            'thumbnail' => isset($image_name) ? $image_name : $post->thumbnail,
            'slug' => $this->generateSlug($request->title, $post->id), // generate slug

        ];

        Post::findOrFail($post->id)->update($data);
        return redirect()->route('member.pages.index')->with('success', 'Data berhasil diupdate!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        if (isset($post->thumbnail) && file_exists(public_path(getenv('CUSTOM_THUMBNAIL_LOCATION')) . '/' . $post->thumbnail)) {
            unlink(public_path(getenv('CUSTOM_THUMBNAIL_LOCATION')) . '/' . $post->thumbnail);
        }
        Post::where('type', $this->type)->findOrFail($post->id)->delete();
        return redirect()->route('member.pages.index')->with('success', 'Data berhasil dihapus!');
    }

    private function generateSlug($title, $id = null)
    {
        $slug = Str::slug($title);
        $count = Post::where('slug', $slug)->when($id, function ($query, $id) {
            return $query->where('id', '!=', $id);
        })->count();

        if ($count > 0) {
            $slug = $slug . '-' . ($count + 1);
        }

        return $slug;
    }
}
