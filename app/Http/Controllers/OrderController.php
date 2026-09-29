<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders)
    {
        $this->authorizeResource(Order::class, 'order');
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Order::query()
            ->with(['items', 'source', 'owner'])
            ->visibleTo($user)
            ->orderByDesc('id');

        if ($code = trim((string) $request->input('code', ''))) {
            $query->where('code', 'like', '%'.$code.'%');
        }

        if ($request->filled('source_id')) {
            $query->where('source_id', (int) $request->input('source_id'));
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->input('created_from'));
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->input('created_to'));
        }

        if ($user->isAdmin() && $request->filled('owner_id')) {
            $query->where('owner_id', (int) $request->input('owner_id'));
        }

        $operator = (string) $request->input('total_op', '');
        if ($request->filled('total_value') && in_array($operator, ['=', '>', '<', '>=', '<='], true)) {
            $query->where('total', $operator, (int) $request->input('total_value'));
        }

        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [20, 50, 100], true)) {
            $perPage = 20;
        }

        $orders = $query->paginate($perPage)->withQueryString();
        $visible = Order::query()->visibleTo($user);

        return view('orders.index', [
            'orders' => $orders,
            'sources' => LeadSource::query()->orderBy('sort_order')->get(),
            'sales' => $user->isAdmin()
                ? User::query()->where('role', User::ROLE_SALE)->where('is_active', true)->orderBy('name')->get()
                : collect(),
            'filters' => $request->only([
                'code', 'source_id', 'created_from', 'created_to', 'owner_id', 'total_op', 'total_value', 'per_page',
            ]),
            'revenueTotal' => (clone $visible)->sum('total'),
            'orderCount' => (clone $visible)->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $selectedLead = null;
        if ($request->filled('lead_id')) {
            $selectedLead = Lead::query()->visibleTo($user)->find((int) $request->input('lead_id'));
        }

        return view('orders.create', [
            'products' => Product::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'sources' => LeadSource::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'sales' => $user->isAdmin()
                ? User::query()->where('role', User::ROLE_SALE)->where('is_active', true)->orderBy('name')->get()
                : collect(),
            'leads' => Lead::query()->visibleTo($user)->orderByDesc('id')->limit(200)->get(['id', 'code', 'name', 'phone', 'source_id', 'owner_id']),
            'selectedLead' => $selectedLead,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lead_id' => ['nullable', 'exists:leads,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'source_id' => ['nullable', 'exists:lead_sources,id'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $order = $this->orders->create($data, $request->user());

        return redirect()->route('orders.show', $order)->with('status', 'Đã tạo đơn hàng nội bộ '.$order->code.'.');
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'source', 'owner', 'creator', 'lead']);

        return view('orders.show', compact('order'));
    }
}
