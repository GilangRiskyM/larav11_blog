<?php

use App\Http\Controllers\Front\BlogDetailController;
use App\Http\Controllers\Front\HomepageController;
use App\Http\Controllers\Front\PageDetailController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Member\BlogController;
use App\Http\Controllers\Member\PageController;
use App\Http\Controllers\Member\UserController;

Route::get('/', [HomepageController::class, 'index']);

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'blocked'])->name('dashboard');

Route::middleware(['auth', 'verified', 'blocked'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //Blog route
    Route::resource('member/blogs', BlogController::class)->names([
        'index' => 'member.blogs.index',
        'edit' => 'member.blogs.edit',
        'update' => 'member.blogs.update',
        'create' => 'member.blogs.create',
        'store' => 'member.blogs.store',
        'destroy' => 'member.blogs.destroy'
    ])->parameters([
        'blogs' => 'post',
    ]);

    //Page route
    Route::resource('member/pages', PageController::class)->names([
        'index' => 'member.pages.index',
        'edit' => 'member.pages.edit',
        'update' => 'member.pages.update',
        'create' => 'member.pages.create',
        'store' => 'member.pages.store',
        'destroy' => 'member.pages.destroy'
    ])->parameters([
        'pages' => 'post',
    ])->middleware(['role_or_permission:admin-pages']);

    //User route
    Route::resource('member/users', UserController::class)->names([
        'index' => 'member.users.index',
        'edit' => 'member.users.edit',
        'update' => 'member.users.update',
        'create' => 'member.users.create',
        'store' => 'member.users.store',
        'destroy' => 'member.users.destroy'
    ])->middleware(['role_or_permission:admin-users']);

    //Blokir user
    Route::get('/member/users/{user}/toggle-block', [UserController::class, 'toggleBlock'])->name('member.users.toggle-block');
});

require __DIR__ . '/auth.php';

Route::get('/{slug}', [BlogDetailController::class, 'detail'])->name('blog-detail');
Route::get('/page/{slug}', [PageDetailController::class, 'detail'])->name('page-detail');
