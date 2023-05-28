<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use App\Models\Givelist;
use App\Models\Organization;

use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostViewResource;
use Exception;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     */
    public function index(Request $request)
    {
        // TODO: add filter for $user->getMyImpactStories()
        $posts = QueryBuilder::for(Post::class)
            ->defaultSort('-posted_at')
            ->allowedFilters([
                AllowedFilter::exact('organizations.id'),
                AllowedFilter::exact('issues.id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::scope('givelist_id', 'forGivelist'),
            ]);

        if ($request->user_id) {
            $user = User::find($request->user_id);
            $impactedOrgs = $user->impactedOrganizations();
            $posts = $posts->whereIn('organization_id', $impactedOrgs);
        }

        if ($request->organization_id) {
            $org = Organization::find($request->organization_id);
            $posts = $org->posts();
        }

        if ($request->givelist_id) {
            $givelist = Givelist::find($request->givelist_id);
            $posts = $givelist->posts();
        }

        if ($request->user_id && $request->organization_id) {
            $user = User::find($request->user_id);
            $posts = $user->organizationImpact($request->organization_id);
        }

        $categoryIds = $request->category_ids;
        if (is_string($categoryIds)) {
            $categoryIds = explode(',', $categoryIds);

            $posts = $posts->whereHas('organization.categories', function ($query) use ($categoryIds) {
                $query->whereIn('id', $categoryIds);
            });
        }

        if ($request->view === 'following') {
            $followers = $request->user('sanctum')->followers()->whereNull('requested_at')->orWhereNotNull('requested_at')->whereNotNull('accepted_at')->get();

            $impactedOrgs = array();
            foreach ($followers as $user) {
                $impactedOrgs = array_merge($impactedOrgs, $user->impactedOrganizations());
            }
            $posts = $posts->whereIn('organization_id', $impactedOrgs);
        }

        $perPage = min(50, $request->per_page ?? 10);

        return PostResource::collection($posts->paginate($perPage));
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(Post $post)
    {
        return new PostViewResource($post);
    }

    public function update(Request $request, Post $post)
    {

        if ($request->media) {
            $results = $post->addAllMediaFromRequest();
            foreach ($results as $result) {
                $result->toMediaCollection('media');
            }
        }

        $post->update($request->only(['title', 'content', 'posted_at']));



        return;
    }

    public function destroy(Post $post)
    {
        return $post->delete();
    }

    public function comment(Request $request, Post $post)
    {
        $comment = new Comment(['user_id' => auth()->user()->id, 'content' => $request->comment]);
        $post->comments()->save($comment);
        return CommentResource::collection($post->comments);
    }

    public function removeMedia(Request $request, Post $post, Media $media)
    {

        abort_unless($media->model()->is($post), 404, 'Media not found as it relates to post');

        $media->delete();

        return;
    }

    public function store(Request $request)
    {
        $login = auth()->user()->logins()->where('loginable_type', 'organization')->where('loginable_id', $request->organization_id)->first();
        abort_unless($login, 403, 'Unauthorized');

        $post = new Post();

        if ($request->media) {
            $results = $post->addAllMediaFromRequest();
            foreach ($results as $result) {
                $result->toMediaCollection('media');
            }
        }


        $post->content = $request->get('content');
        $post->title = $request->get('title');
        $post->user()->associate(auth()->user());
        $post->organization_id = auth()->user()->currentLogin->id;
        $post->save();
        if ($request->donors) $post->donors()->save($request->donors);

        return new PostResource($post);
    }
}
