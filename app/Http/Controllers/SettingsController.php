<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Display settings page.
     */
    public function index(Request $request)
    {
        $users = User::orderBy('name')->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'username' => $u->username,
            'role' => $u->role,
            'is_active' => $u->is_active,
            'telegram_chat_id' => $u->telegram_chat_id,
            'created_at' => $u->created_at?->format('d/m/Y'),
        ]);

        $categories = Category::withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        $units = Unit::orderBy('name')->get();

        return Inertia::render('Pengaturan', [
            'users' => $users,
            'categories' => $categories,
            'units' => $units,
        ]);
    }

    /**
     * Update Telegram chat ID for a user (link Telegram account).
     */
    public function linkTelegram(Request $request)
    {
        $validated = $request->validate([
            'telegram_chat_id' => 'required|string|max:100',
        ]);

        $request->user()->update([
            'telegram_chat_id' => $validated['telegram_chat_id'],
        ]);

        return back()->with('success', 'Akun Telegram berhasil ditautkan.');
    }
}
