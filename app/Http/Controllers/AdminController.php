<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Listing;
use App\Models\Tier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminController extends Controller
{
    // ─── Dashboard ───────────────────────────────────────────────

    public function index(): View
    {
        $stats = [
            'agencies' => User::where('role', User::ROLE_AGENCY)->count(),
            'customers' => User::where('role', User::ROLE_CUSTOMER)->count(),
            'admins' => User::where('role', User::ROLE_ADMIN)->count(),
            'suspended' => User::whereNotNull('suspended_at')->count(),
            'paid' => User::whereIn('subscription_tier', ['pro', 'premium'])
                ->where(function ($q) {
                    $q->whereNull('subscription_until')->orWhere('subscription_until', '>', now());
                })->count(),
            'listings_total' => Listing::count(),
            'listings_active' => Listing::active()->count(),
            'listings_featured' => Listing::featured()->count(),
            'listings_suspended' => Listing::whereNotNull('suspended_at')->count(),
            'inquiries_30d' => Inquiry::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return view('admin.index', compact('stats'));
    }

    // ─── Agencies ────────────────────────────────────────────────

    public function agencies(Request $request): View
    {
        $query = User::query()
            ->whereIn('role', [User::ROLE_AGENCY, User::ROLE_ADMIN])
            ->withCount(['listings'])
            ->orderByDesc('id');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $agencies = $query->paginate(25)->withQueryString();
        $tiers = Tier::orderBy('sort_order')->get();

        return view('admin.agencies', compact('agencies', 'tiers'));
    }

    public function createAgencyForm(): View
    {
        $tiers = Tier::where('active', true)->orderBy('sort_order')->get();

        return view('admin.agency-create', compact('tiers'));
    }

    public function storeAgency(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subscription_tier' => ['required', 'exists:tiers,key'],
            'subscription_until' => ['nullable', 'date'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $password = $data['password'] ?: \Illuminate\Support\Str::random(12);

        $user = User::create([
            'role' => User::ROLE_AGENCY,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $password,
            'subscription_tier' => $data['subscription_tier'],
            'subscription_until' => $data['subscription_until'] ?? null,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()
            ->route('admin.agencies')
            ->with('status', "Агенцијата {$user->name} е создадена. Лозинка: {$password}");
    }

    public function updateUserTier(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'subscription_tier' => ['required', 'exists:tiers,key'],
            'subscription_until' => ['nullable', 'date'],
            'role' => ['sometimes', 'in:agency,customer,admin'],
        ]);

        $user->update([
            'subscription_tier' => $data['subscription_tier'],
            'subscription_until' => $data['subscription_until'] ?? null,
            'role' => $data['role'] ?? $user->role,
        ]);

        return back()->with('status', "Tier на {$user->name} ажуриран.");
    }

    public function suspendAgency(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $user->suspend($data['reason'] ?? null);

        return back()->with('status', "{$user->name} суспендиран.");
    }

    public function unsuspendAgency(User $user): RedirectResponse
    {
        $user->unsuspend();

        return back()->with('status', "{$user->name} активен.");
    }

    public function destroyAgency(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 422, 'Не можеш да се избришеш самиот себе.');
        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.agencies')
            ->with('status', "Агенцијата {$name} е избришана.");
    }

    // ─── Listings ────────────────────────────────────────────────

    public function listings(Request $request): View
    {
        $query = Listing::query()
            ->with('user')
            ->withCount('inquiries')
            ->orderByDesc('id');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%")
                    ->orWhere('hotel_name', 'like', "%{$search}%");
            });
        }

        $listings = $query->paginate(30)->withQueryString();

        return view('admin.listings', compact('listings'));
    }

    public function featureListing(Request $request, Listing $listing): RedirectResponse
    {
        $data = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $listing->update([
            'featured_until' => now()->addDays((int) $data['days']),
        ]);

        return back()->with('status', "Огласот #{$listing->id} промовиран {$data['days']} дена.");
    }

    public function unfeatureListing(Listing $listing): RedirectResponse
    {
        $listing->update(['featured_until' => null]);

        return back()->with('status', "Огласот #{$listing->id} веќе не е промовиран.");
    }

    public function suspendListing(Request $request, Listing $listing): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $listing->suspend($data['reason'] ?? null);

        return back()->with('status', "Оглас #{$listing->id} суспендиран.");
    }

    public function unsuspendListing(Listing $listing): RedirectResponse
    {
        $listing->unsuspend();

        return back()->with('status', "Оглас #{$listing->id} активен.");
    }

    public function destroyListing(Listing $listing): RedirectResponse
    {
        $id = $listing->id;
        $listing->delete();

        return redirect()
            ->route('admin.listings')
            ->with('status', "Огласот #{$id} е избришан.");
    }

    // ─── Tiers ───────────────────────────────────────────────────

    public function tiers(): View
    {
        $tiers = Tier::orderBy('sort_order')->withCount('users')->get();

        return view('admin.tiers', [
            'tiers' => $tiers,
            'featureMeta' => Tier::FEATURES,
        ]);
    }

    public function createTierForm(): View
    {
        return view('admin.tier-create', [
            'featureMeta' => Tier::FEATURES,
        ]);
    }

    public function storeTier(Request $request): RedirectResponse
    {
        $data = $this->validateTier($request, isUpdate: false);
        Tier::create($data);

        return redirect()->route('admin.tiers')->with('status', "Tier {$data['key']} создаден.");
    }

    public function editTierForm(Tier $tier): View
    {
        return view('admin.tier-edit', [
            'tier' => $tier,
            'featureMeta' => Tier::FEATURES,
        ]);
    }

    public function updateTier(Request $request, Tier $tier): RedirectResponse
    {
        $data = $this->validateTier($request, isUpdate: true, ignoreKey: $tier->key);
        $tier->update($data);

        return redirect()->route('admin.tiers')->with('status', "Tier {$tier->key} ажуриран.");
    }

    public function destroyTier(Tier $tier): RedirectResponse
    {
        if ($tier->users()->exists()) {
            return back()->withErrors([
                'tier' => "Tier {$tier->key} има корисници; премести ги во друг tier пред бришење.",
            ]);
        }
        $tier->delete();

        return redirect()->route('admin.tiers')->with('status', "Tier {$tier->key} избришан.");
    }

    /** @return array<string, mixed> */
    private function validateTier(Request $request, bool $isUpdate, ?string $ignoreKey = null): array
    {
        $rules = [
            'key' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9_-]+$/'],
            'name' => ['required', 'string', 'max:60'],
            'monthly_price' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'in:MKD,EUR,USD'],
            'active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
        // Feature inputs: namespaced under "features.<key>"
        foreach (Tier::FEATURES as $key => $meta) {
            $rules["features.{$key}"] = ['nullable'];
        }

        if (! $isUpdate) {
            $rules['key'][] = 'unique:tiers,key';
        } else {
            $rules['key'][] = "unique:tiers,key,{$ignoreKey},key";
        }

        $data = $request->validate($rules);

        // Normalise features: cast based on declared type.
        $features = [];
        foreach (Tier::FEATURES as $key => $meta) {
            $raw = $data['features'][$key] ?? null;
            $features[$key] = match ($meta['type']) {
                'bool' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
                'int' => (int) ($raw ?? 0),
                'nullable_int' => $raw === '' || $raw === null ? null : (int) $raw,
            };
        }
        $data['features'] = $features;
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
