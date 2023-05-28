<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Resources\CategoryViewResource;
use App\Http\Resources\CategoryTableResource;
use App\Http\Resources\CategoryDescendantResource;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        return CategoryTableResource::collection(Category::query()->with('media')->whereNull('parent_id')->where('name', 'like', "%{$request->search}%")->get());
    }

    public function store(Request $request)
    {
        return Category::create($request->all());
    }

    public function show(Category $category)
    {
        return new CategoryViewResource($category);
    }

    public function featuredIndex()
    {
        return CategoryTableResource::collection(Category::query()->with('media')->whereIn('id', Category::getFeaturedCategoryIds())->get());
    }

    public function update(Request $request, Category $category)
    {
        return $category->update($request->all());
    }

    public function destroy(Category $category)
    {
        return $category->delete();
    }

    public function organizations(Category $category)
    {
        return $category->organizations;
    }

    public function givelists(Category $category)
    {
        return $category->givelists;
    }

    public function groupedCategories()
    {
        return CategoryDescendantResource::collection(Category::query()->whereNull('parent_id')->get());
    }
}
