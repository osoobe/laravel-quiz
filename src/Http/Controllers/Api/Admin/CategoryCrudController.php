<?php

namespace Osoobe\Quiz\Http\Controllers\Api\Admin;

use Osoobe\Quiz\Http\Requests\StoreCategoryRequest;
use Osoobe\Quiz\Http\Requests\UpdateCategoryRequest;
use Osoobe\Quiz\Http\Resources\CategoryResource;
use Osoobe\Quiz\Models\QuizCategory;

class CategoryCrudController
{
    public function index()
    {
        return CategoryResource::collection(QuizCategory::query()->latest()->paginate(50));
    }

    public function store(StoreCategoryRequest $request)
    {
        return new CategoryResource(QuizCategory::create($request->validated()));
    }

    public function show(QuizCategory $category)
    {
        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, QuizCategory $category)
    {
        $category->update($request->validated());

        return new CategoryResource($category);
    }

    public function destroy(QuizCategory $category)
    {
        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }
}
