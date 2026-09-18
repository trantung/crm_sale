<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(): View
    {
        $users = User::query()->orderBy('role')->orderBy('name')->paginate(20);

        return view('users.index', [
            'users' => $users,
            'defaultPassword' => config('crm.default_password'),
        ]);
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        User::query()->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
            'password' => Hash::make(config('crm.default_password')),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('users.index')->with(
            'status',
            'Đã tạo user. Mật khẩu mặc định: '.config('crm.default_password')
        );
    }

    public function edit(User $user): View
    {
        return view('users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if ($user->isAdmin() && $data['role'] !== User::ROLE_ADMIN) {
            $adminCount = User::query()->where('role', User::ROLE_ADMIN)->where('is_active', true)->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['role' => 'Không thể hạ quyền admin cuối cùng.']);
            }
        }

        if ($request->user()->id === $user->id && ! $request->boolean('is_active', true)) {
            return back()->withErrors(['is_active' => 'Không thể tự vô hiệu hóa tài khoản đang đăng nhập.']);
        }

        $user->fill([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ])->save();

        return redirect()->route('users.index')->with('status', 'Đã cập nhật user.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return back()->withErrors(['user' => 'Không thể xóa chính mình.']);
        }

        if ($user->isAdmin()) {
            $adminCount = User::query()->where('role', User::ROLE_ADMIN)->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['user' => 'Không thể xóa admin cuối cùng.']);
            }
        }

        $user->assignedLeads()->update(['owner_id' => null, 'assigned_at' => null]);
        $user->delete();

        return redirect()->route('users.index')->with('status', 'Đã xóa user. Lead đang phụ trách được đưa về chưa phân.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        $user->update([
            'password' => Hash::make(config('crm.default_password')),
        ]);

        return back()->with(
            'status',
            'Đã reset mật khẩu của '.$user->username.' về: '.config('crm.default_password')
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:64',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_SALE])],
        ]);
    }
}
