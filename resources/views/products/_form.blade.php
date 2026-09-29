@php $product = $product ?? null; @endphp

<div>
    <label class="ts-label" for="name">Tên khóa học</label>
    <input id="name" name="name" class="ts-input" value="{{ old('name', $product->name ?? '') }}" required>
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>
<div>
    <label class="ts-label" for="code">Mã (để trống sẽ tự tạo)</label>
    <input id="code" name="code" class="ts-input" value="{{ old('code', $product->code ?? '') }}">
    <x-input-error :messages="$errors->get('code')" class="mt-2" />
</div>
<div>
    <label class="ts-label" for="price">Giá (VNĐ)</label>
    <input id="price" name="price" type="number" min="0" step="1000" class="ts-input" value="{{ old('price', $product->price ?? '') }}" required>
    <x-input-error :messages="$errors->get('price')" class="mt-2" />
</div>
<div>
    <label class="ts-label" for="sort_order">Thứ tự</label>
    <input id="sort_order" name="sort_order" type="number" min="0" class="ts-input" value="{{ old('sort_order', $product->sort_order ?? 0) }}">
</div>
<div class="md:col-span-2">
    <label class="ts-label" for="description">Mô tả</label>
    <textarea id="description" name="description" rows="3" class="ts-textarea">{{ old('description', $product->description ?? '') }}</textarea>
</div>
<div class="flex items-center gap-2 md:col-span-2">
    <input type="hidden" name="is_active" value="0">
    <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-slate-300"
        @checked(old('is_active', $product->is_active ?? true))>
    <label for="is_active" class="text-sm font-semibold text-slate-600">Đang bán</label>
</div>
