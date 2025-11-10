<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Storage::disk('public')->makeDirectory('blogs');

        $users = User::all();

        if ($users->isEmpty()) {
            $users = User::factory()->count(3)->create();
        }

        $users->each(function (User $user) use ($users) {
            $blogs = Blog::factory()
                ->count(3)
                ->for($user)
                ->create();

            $blogs->each(function (Blog $blog) use ($users) {
                $potentialLikers = $users
                    ->pluck('id')
                    ->reject(fn (int $id) => $id === $blog->user_id)
                    ->values();

                if ($potentialLikers->isEmpty()) {
                    return;
                }

                $likeCount = random_int(0, $potentialLikers->count());

                $potentialLikers->shuffle()
                    ->take($likeCount)
                    ->each(function (int $likerId) use ($blog) {
                        $blog->likes()->firstOrCreate([
                            'user_id' => $likerId,
                        ]);
                    });
            });
        });
    }
}
