@php
    $lead = $lead ?? null;
@endphp

<div>
    <label class="ts-label" for="name">Tên khách hàng</label>
    <input id="name" name="name" class="ts-input" value="{{ old('name', $lead->name ?? '') }}" required>
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>
<div>
    <label class="ts-label" for="phone">Số điện thoại</label>
    <input id="phone" name="phone" class="ts-input" value="{{ old('phone', $lead->phone ?? '') }}" required>
    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
</div>
<div>
    <label class="ts-label" for="email">Email</label>
    <input id="email" name="email" type="email" class="ts-input" value="{{ old('email', $lead->email ?? '') }}">
    <x-input-error :messages="$errors->get('email')" class="mt-2" />
</div>
<div>
    <label class="ts-label" for="source_id">Nguồn</label>
    <select id="source_id" name="source_id" class="ts-select" required>
        <option value="">— Chọn nguồn —</option>
        @foreach ($sources as $source)
            <option value="{{ $source->id }}" @selected(old('source_id', $lead->source_id ?? '') == $source->id)>{{ $source->name }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('source_id')" class="mt-2" />
</div>
@if (Auth::user()->isAdmin() && empty($lead))
    <div>
        <label class="ts-label" for="owner_id">Phân cho sale</label>
        <select id="owner_id" name="owner_id" class="ts-select">
            <option value="">Chưa phân</option>
            @foreach ($sales as $sale)
                <option value="{{ $sale->id }}" @selected(old('owner_id') == $sale->id)>{{ $sale->name }}</option>
            @endforeach
        </select>
    </div>
@endif
<div>
    <label class="ts-label" for="interested_product">Sản phẩm quan tâm</label>
    <select id="interested_product" name="interested_product" class="ts-select">
        <option value="">—</option>
        @foreach (['live_class' => 'Lớp live', 'practice_room' => 'Phòng luyện', 'combo' => 'Combo'] as $value => $label)
            <option value="{{ $value }}" @selected(old('interested_product', $lead->interested_product ?? '') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="ts-label" for="sso_id">SSO ID (nếu có)</label>
    <input id="sso_id" name="sso_id" class="ts-input" value="{{ old('sso_id', $lead->sso_id ?? '') }}">
</div>
<div class="md:col-span-2">
    <label class="ts-label" for="note">Ghi chú</label>
    <textarea id="note" name="note" rows="3" class="ts-textarea">{{ old('note', $lead->note ?? '') }}</textarea>
</div>
