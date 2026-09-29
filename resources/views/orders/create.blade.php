@php
    $catalog = $products->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'price' => $p->price,
    ])->values();
    $leadOptions = $leads->map(fn ($lead) => [
        'id' => $lead->id,
        'code' => $lead->code,
        'name' => $lead->name,
        'phone' => $lead->phone,
        'source_id' => $lead->source_id,
        'owner_id' => $lead->owner_id,
    ])->values();
@endphp

<x-app-layout title="Tạo đơn hàng">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Doanh thu <span>›</span>
            <a href="{{ route('orders.index') }}" class="hover:underline">Đơn hàng</a>
            <span>›</span> <strong>Tạo đơn nội bộ</strong>
        </div>
    </div>

    <form method="POST" action="{{ route('orders.store') }}" class="space-y-4"
        x-data="{
            products: {{ Js::from($catalog) }},
            leads: {{ Js::from($leadOptions) }},
            leadId: '{{ old('lead_id', $selectedLead?->id) }}',
            customerName: @js(old('customer_name', $selectedLead?->name ?? '')),
            customerPhone: @js(old('customer_phone', $selectedLead?->phone ?? '')),
            sourceId: '{{ old('source_id', $selectedLead?->source_id) }}',
            items: {{ Js::from(old('items', [['product_id' => '', 'quantity' => 1, 'discount_percent' => 0]])) }},
            pickLead() {
                const lead = this.leads.find(l => String(l.id) === String(this.leadId));
                if (!lead) return;
                this.customerName = lead.name || '';
                this.customerPhone = lead.phone || '';
                if (lead.source_id) this.sourceId = String(lead.source_id);
            },
            addItem() {
                this.items.push({ product_id: '', quantity: 1, discount_percent: 0 });
            },
            removeItem(index) {
                if (this.items.length > 1) this.items.splice(index, 1);
            },
            productPrice(id) {
                return this.products.find(p => String(p.id) === String(id))?.price || 0;
            },
            lineTotal(item) {
                const qty = Math.max(1, parseInt(item.quantity || 1, 10));
                const discount = Math.min(100, Math.max(0, parseFloat(item.discount_percent || 0)));
                return Math.round(this.productPrice(item.product_id) * qty * (1 - discount / 100));
            },
            get subtotal() {
                return this.items.reduce((sum, item) => sum + this.productPrice(item.product_id) * Math.max(1, parseInt(item.quantity || 1, 10)), 0);
            },
            get total() {
                return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
            },
            money(n) {
                return new Intl.NumberFormat('vi-VN').format(n || 0) + ' đ';
            }
        }">
        @csrf

        <div class="ts-panel p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2 ts-card-title">Khách hàng</div>
            <div>
                <label class="ts-label">Chọn lead (nếu có)</label>
                <select name="lead_id" class="ts-select" x-model="leadId" @change="pickLead()">
                    <option value="">— Không gắn lead —</option>
                    @foreach ($leads as $lead)
                        <option value="{{ $lead->id }}">{{ $lead->code }} · {{ $lead->name }} · {{ $lead->phone }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ts-label">Nguồn đơn hàng</label>
                <select name="source_id" class="ts-select" x-model="sourceId">
                    <option value="">— Chọn nguồn —</option>
                    @foreach ($sources as $source)
                        <option value="{{ $source->id }}">{{ $source->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ts-label">Họ tên</label>
                <input name="customer_name" class="ts-input" x-model="customerName" required>
                <x-input-error :messages="$errors->get('customer_name')" class="mt-2" />
            </div>
            <div>
                <label class="ts-label">Số điện thoại</label>
                <input name="customer_phone" class="ts-input" x-model="customerPhone">
            </div>
            @if (Auth::user()->isAdmin())
                <div>
                    <label class="ts-label">Tư vấn viên</label>
                    <select name="owner_id" class="ts-select">
                        <option value="{{ Auth::id() }}">{{ Auth::user()->name }} (quản lý)</option>
                        @foreach ($sales as $sale)
                            <option value="{{ $sale->id }}" @selected(old('owner_id', $selectedLead?->owner_id) == $sale->id)>{{ $sale->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="{{ Auth::user()->isAdmin() ? '' : 'md:col-span-2' }}">
                <label class="ts-label">Ghi chú</label>
                <input name="note" class="ts-input" value="{{ old('note') }}" placeholder="Ghi chú nội bộ (hoa hồng, kế toán...)">
            </div>
        </div>

        <div class="ts-panel p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="ts-card-title mb-0">Khóa học</div>
                <div class="flex gap-2">
                    <a href="{{ route('products.create') }}" class="ts-btn ts-btn-ghost">+ Tạo khóa / bảng giá</a>
                    <button type="button" class="ts-btn ts-btn-ghost" @click="addItem()">+ Thêm dòng</button>
                </div>
            </div>
            @if ($products->isEmpty())
                <div class="ts-flash ts-flash-err">Chưa có khóa học. Hãy tạo sản phẩm / bảng giá trước khi lập đơn.</div>
            @endif

            <div class="space-y-3">
                <template x-for="(item, index) in items" :key="index">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 border border-slate-200 rounded-lg p-3">
                        <div class="md:col-span-5">
                            <label class="ts-label">Khóa học</label>
                            <select class="ts-select" :name="'items['+index+'][product_id]'" x-model="item.product_id" required>
                                <option value="">— Chọn khóa học —</option>
                                <template x-for="p in products" :key="p.id">
                                    <option :value="p.id" x-text="p.name + ' · ' + money(p.price)"></option>
                                </template>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="ts-label">Số lượng</label>
                            <input type="number" min="1" class="ts-input" :name="'items['+index+'][quantity]'" x-model="item.quantity">
                        </div>
                        <div class="md:col-span-2">
                            <label class="ts-label">% chiết khấu</label>
                            <input type="number" min="0" max="100" step="0.5" class="ts-input" :name="'items['+index+'][discount_percent]'" x-model="item.discount_percent">
                        </div>
                        <div class="md:col-span-2">
                            <label class="ts-label">Thành tiền</label>
                            <div class="ts-input bg-slate-50 font-semibold" x-text="money(lineTotal(item))"></div>
                        </div>
                        <div class="md:col-span-1 flex items-end">
                            <button type="button" class="ts-btn ts-btn-danger w-full justify-center" @click="removeItem(index)" x-show="items.length > 1">Xóa</button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex justify-end gap-8 mt-4 text-sm">
                <div>Tạm tính: <strong x-text="money(subtotal)"></strong></div>
                <div>Tổng thanh toán: <strong class="text-lg text-[#0f2744]" x-text="money(total)"></strong></div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('orders.index') }}" class="ts-btn ts-btn-ghost">Hủy</a>
            <button class="ts-btn ts-btn-primary" type="submit">Tạo đơn hàng</button>
        </div>
    </form>
</x-app-layout>
