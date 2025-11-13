<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlogRequest;
use App\Http\Requests\UpdateBlogRequest;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use App\Models\Like;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $perPage = (int) $request->integer('per_page', 10);
        $perPage = max(1, min(100, $perPage));

        $query = Blog::query()
            ->select('blogs.*')
            ->with(['user:id,name,email'])
            ->withCount('likes')
            ->addSelect([
                'is_liked' => Like::selectRaw('1')
                    ->whereColumn('likeable_id', 'blogs.id')
                    ->where('likeable_type', Blog::class)
                    ->where('user_id', $user->id)
                    ->exists(1),
            ]);

        /*   $query = Blog::with(['user:id,name,email'])
            ->withCount('likes')
            ->withExists(['likes as is_liked' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }]);
            
            -> automatically adds a boolean column is_liked
            
            */

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sort = $request->query('sort', 'latest');
        if ($sort === 'most_liked') {
            $query->orderByDesc('likes_count')->orderByDesc('created_at');
        } else {
            $query->orderByDesc('created_at');
        }

        $blogs = $query->paginate($perPage)->withQueryString();

        return BlogResource::collection($blogs);
    }

    public function store(StoreBlogRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $imagePath = $request->file('image')->store('blogs', 'public');

        $blog = $user->blogs()->create([
            'title' => $data['title'],
            'description' => $data['description'],
            'image_path' => $imagePath,
        ]);

        $blog->load(['user:id,name,email'])->setAttribute('likes_count', 0)->setAttribute('is_liked', false);

        return (new BlogResource($blog))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateBlogRequest $request, Blog $blog): BlogResource
    {
        $this->authorizeBlog($request->user(), $blog);

        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($blog->image_path) {
                Storage::disk('public')->delete($blog->image_path);
            }

            $data['image_path'] = $request->file('image')->store('blogs', 'public');
        }

        $blog->fill($data)->save();

        $blog->load(['user:id,name,email'])->loadCount('likes');
        $blog->setAttribute('is_liked', $blog->likes()->where('user_id', $request->user()->id)->exists());

        return new BlogResource($blog);
    }

    public function destroy(Request $request, Blog $blog): JsonResponse
    {
        $this->authorizeBlog($request->user(), $blog);

        if ($blog->image_path) {
            Storage::disk('public')->delete($blog->image_path);
        }

        $blog->delete();

        return response()->json([
            'message' => 'Blog deleted successfully.',
        ], Response::HTTP_OK);
    }

    public function toggleLike(Request $request, Blog $blog): JsonResponse
    {
        $user = $request->user();

        $existingLike = $blog->likes()->where('user_id', $user->id)->first();

        if ($existingLike) {
            $existingLike->delete();
            $isLiked = false;
            $message = 'Blog unliked successfully.';
        } else {
            $blog->likes()->create(['user_id' => $user->id]);
            $isLiked = true;
            $message = 'Blog liked successfully.';
        }

        $blog->loadCount('likes');

        return response()->json([
            'message' => $message,
            'is_liked' => $isLiked,
            'likes_count' => (int) $blog->likes_count,
        ]);
    }

    protected function authorizeBlog(?User $user, Blog $blog): void
    {
        if (! $user || $blog->user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN, 'You are not allowed to modify this blog.');
        }
    }
}
