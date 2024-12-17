<?php

namespace App\Http\Controllers\Member;

use DOMDocument;
use App\Models\Post;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;

class BlogController extends Controller
{
    //Jika ada tanda garis merah abaikan saja

    protected $type = 'blog';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $user = Auth::user();
        $search = $request->search;
        if ($user->can('admin-blogs')) {
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
        } else {
            $data = Post::where('user_id', $user->id)
                ->where('type', $this->type)
                ->where(function ($query) use ($search) {
                    if ($search) {
                        $query->where('title', 'like', '%' . $search . '%')
                            ->orWhere('content', 'like', '%' . $search . '%');
                    }
                })
                ->orderBy('id', 'desc')
                ->paginate(3)
                ->withQueryString();
        }

        return view('member.blogs.index', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('member.blogs.create');
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
            $destination_path = public_path('thumbnails');
            $image->move($destination_path, $image_name);
        }

        $content = $request->content;

        $dom = new DOMDocument();
        $dom->loadHTML($content, 9);
        $contentImages = $dom->getElementsByTagName('img');

        foreach ($contentImages as $key => $value) {
            $data = base64_decode(explode(',', explode(';', $value->getAttribute('src'))[1])[1]);
            $contentImageName = "/upload/" . time() . $key . '.png';
            file_put_contents(public_path() . $contentImageName, $data);

            $value->removeAttribute('src'); // remove src attribute
            $value->setAttribute('src', $contentImageName);
        }

        $content = $dom->saveHTML();

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'content' => $content,
            'status' => $request->status,
            'thumbnail' => isset($image_name) ? $image_name : null,
            'slug' => $this->generateSlug($request->title), // generate slug
            'user_id' => Auth::user()->id,
            'type' => $this->type
        ];

        Post::create($data);

        return redirect()->route('member.blogs.index')->with('success', 'Data berhasil ditambah!');
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
            return redirect()->route('member.blogs.index');
        }
        Gate::authorize('edit', $post);
        $data = Post::findOrFail($post->id);
        return view('member.blogs.edit', compact('data'));
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
            if (isset($post->thumbnail) && file_exists(public_path('thumbnails') . '/' . $post->thumbnail)) {
                unlink(public_path('thumbnails') . '/' . $post->thumbnail);
            }
            $image = $request->file('thumbnail');
            $image_name = time() . '_' . $image->getClientOriginalName();
            $destination_path = public_path('thumbnails');
            $image->move($destination_path, $image_name);
        }

        $content = $request->content;

        $dom = new DOMDocument();
        $dom->loadHTML($content, 9);
        $contentImages = $dom->getElementsByTagName('img');

        foreach ($contentImages as $key => $value) {
            if (strpos($value->getAttribute('src'), 'data:image/') === 0) {

                $data = base64_decode(explode(',', explode(';', $value->getAttribute('src'))[1])[1]);
                $contentImageName = "/upload/" . time() . $key . '.png';
                file_put_contents(public_path() . $contentImageName, $data);

                $value->removeAttribute('src'); // remove src attribute
                $value->setAttribute('src', $contentImageName);
            }
        }
        $content = $dom->saveHTML();

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'content' => $content,
            'status' => $request->status,
            'thumbnail' => isset($image_name) ? $image_name : $post->thumbnail,
            'slug' => $this->generateSlug($request->title, $post->id), // generate slug

        ];

        Post::findOrFail($post->id)->update($data);
        return redirect()->route('member.blogs.index')->with('success', 'Data berhasil diupdate!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);


        $table = Post::where('type', $this->type)->findOrFail($post->id);

        $dom = new DOMDocument();
        $dom->loadHTML($table->content, 9);
        $images = $dom->getElementsByTagName('img');

        foreach ($images as $key => $img) {

            $src = $img->getAttribute('src');
            $path = Str::of($src)->after('/');


            if (File::exists($path)) {
                File::delete($path);
            }
        }

        if (isset($post->thumbnail) && file_exists(public_path('thumbnails') . '/' . $post->thumbnail)) {
            unlink(public_path('thumbnails') . '/' . $post->thumbnail);
        }

        $table->delete();

        return redirect()->route('member.blogs.index')->with('success', 'Data berhasil dihapus!');
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
