<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\FollowerUser;
use Illuminate\Http\Request;

use App\Http\Resources\OtherUserViewResource;

class OtherUserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return new OtherUserViewResource($user);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }

    public function follow(User $user)
    {
        $follow = new FollowerUser();
        $follow->follower()->associate(auth()->user());
        $follow->user()->associate($user);
        $follow->requested_at = $user->is_private ? now() : null;
        $follow->save();
        return new OtherUserViewResource($user);
    }


    public function unfollow(User $user)
    {
        auth()->user()->followings()->detach($user->id);
        auth()->user()->save();
        return new OtherUserViewResource($user);
    }
}
