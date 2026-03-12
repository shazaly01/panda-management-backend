<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
        $this->authorizeResource(Category::class, 'category');
    }

    /**
     * العرض الافتراضي (شجرة التصنيفات)
     */
    public function index()
    {
        $tree = $this->categoryService->getCategoryTree();
        return CategoryResource::collection($tree);
    }

    /**
     * نقطة وصول إضافية للحصول على قائمة مسطحة (للقوائم المنسدلة)
     */
    public function list(Request $request)
    {
        $this->authorize('viewAny', Category::class);

        $activeOnly = !$request->has('all');
        $list = $this->categoryService->getFlatList($activeOnly);

        return response()->json($list);
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = $this->categoryService->createCategory($request->validated());
        return new CategoryResource($category);
    }

    public function show(Category $category)
    {
        // تحميل الأبناء عند طلب تصنيف واحد
        return new CategoryResource($category->load('children'));
    }

    public function update(StoreCategoryRequest $request, Category $category)
    {
        $updatedCategory = $this->categoryService->updateCategory($category, $request->validated());
        return new CategoryResource($updatedCategory);
    }

    public function destroy(Category $category): JsonResponse
    {
        try {
            $this->categoryService->deleteCategory($category);
            return response()->json(['message' => 'تم حذف التصنيف بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
