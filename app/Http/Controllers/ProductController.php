<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(): View
    {
        $products = Product::query()->orderBy('sort_order')->orderBy('name')->paginate(30);

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        return view('products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $product = new Product($data);
        $product->code = $data['code'] ?: 'TMP-'.substr(uniqid(), -8);
        $product->save();
        if (! $data['code']) {
            $product->code = Product::assignCode($product);
            $product->save();
        }

        return redirect()->route('products.index')->with('status', 'Đã tạo khóa học / bảng giá.');
    }

    public function edit(Product $product): View
    {
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        if (empty($data['code'])) {
            unset($data['code']);
        }
        $product->fill($data)->save();

        return redirect()->route('products.index')->with('status', 'Đã cập nhật khóa học.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('products.index')->with('status', 'Đã xóa khóa học.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:32', Rule::unique('products', 'code')->ignore($product?->id)],
            'price' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['code'] = trim((string) ($data['code'] ?? '')) ?: null;

        return $data;
    }
}
