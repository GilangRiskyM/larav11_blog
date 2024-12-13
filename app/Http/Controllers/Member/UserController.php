<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $data = User::where(function ($query) use ($search) {
            if ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            }
        })
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('member.users.index', [
            'data' => $data
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::get();
        return view('member.users.create', [
            'permissions' => $permissions
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|same:password_confirmation|required_with:password_confirmation',
            'password_confirmation' => 'required_with:password',
        ], [
            'name.required' => 'Nama wajib diisi!',
            'email.required' => 'Email wajib diisi!',
            'email.email' => 'Email harus berformat email!',
            'email.unique' => 'Email sudah terdaftar, silahkan gunakan email lain!',
            'password.required' => 'Password wajib diisi!',
            'password.required_with' => 'Password harus diisi!',
            'password.same' => 'Password harus sama dengan Konfirmasi Password!',
            'password.min' => 'Password harus minimal :min karakter!',
            'password_confirmation.required_with' => 'Konfirmasi Password harus diisi!',
        ]);

        $email_verified = $request->email_verified_at ? Carbon::now() : null;

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'email_verified_at' => $email_verified,
            'password' => bcrypt($request->password)
        ];

        $userBaru = User::create($data);

        $userBaru->syncPermissions($request->permissions);

        return redirect()->route('member.users.index')->with('success', 'Tambah data user berhasil!');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $permissions = Permission::get();
        $userPermissions = $user->getPermissionNames()->toArray();
        $data = $user;
        return view('member.users.edit', [
            'data' => $data,
            'permissions' => $permissions,
            'userPermissions' => $userPermissions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'new_password' => 'nullable|min:8|same:new_password_confirmation|required_with:new_password_confirmation',
            'new_password_confirmation' => 'required_with:new_password',
        ], [
            'name.required' => 'Nama wajib diisi!',
            'email.required' => 'Email wajib diisi!',
            'email.email' => 'Email harus berformat email!',
            'email.unique' => 'Email sudah terdaftar, silahkan gunakan email lain!',
            'new_password.required_with' => 'Password harus diisi!',
            'new_password.same' => 'Password harus sama dengan Konfirmasi Password!',
            'new_password.min' => 'Password harus minimal :min karakter!',
            'new_password_confirmation.required_with' => 'Konfirmasi Password harus diisi!',
        ]);

        $email_verified = $user->email_verified_at ? $user->email_verified_at : Carbon::now();

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'email_verified_at' => $email_verified,
            'password' => $request->new_password ? bcrypt($request->new_password) : $user->password
        ];

        User::findOrFail($user->id)->update($data);

        $user->syncPermissions($request->permissions);

        return redirect()->route('member.users.index')->with('success', 'Data user berhasil diupdate!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $posts = Post::where('user_id', $user->id)->get();
        foreach ($posts as $post) {
            if (file_exists(public_path(getenv("CUSTOM_THUMBNAIL_LOCATION") . "/" . $post->thumbnail)) && isset($post->thumbnail)) {
                unlink(public_path(getenv("CUSTOM_THUMBNAIL_LOCATION") . "/" . $post->thumbnail));
            }
        }

        User::findOrFail($user->id)->delete();

        return redirect()->back()->with('success', 'Data User berhasil dihapus!');
    }

    function toggleBlock(User $user)
    {
        $message = '';

        if ($user->blocked_at == null) {
            $data = [
                'blocked_at' => now(),
            ];

            $message = "User " . $user->name . " telah di-blokir!";
        } else {
            $data = [
                'blocked_at' => null,
            ];

            $message = "User " . $user->name . " telah di-unblokir!";
        }

        User::findOrFail($user->id)->update($data);

        return redirect()->back()->with('success', $message);
    }
}
