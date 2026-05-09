<?php

use App\Models\User;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:120')]
    public string $display_name = '';

    #[Validate('nullable|string|max:200')]
    public string $tagline = '';

    #[Validate('nullable|string|max:5000')]
    public string $description = '';

    #[Validate('nullable|url|max:255')]
    public string $website = '';

    #[Validate('nullable|string|max:50')]
    public string $phone = '';

    #[Validate('nullable|string|max:200')]
    public string $address = '';

    #[Validate('nullable|regex:/^@?[a-zA-Z0-9._]{1,30}$/')]
    public string $instagram_handle = '';

    #[Validate('nullable|url|max:255')]
    public string $facebook_url = '';

    #[Validate('nullable|regex:/^#[0-9a-fA-F]{6}$/')]
    public string $accent_color = '#0284c7';

    #[Validate('nullable|image|max:2048')]
    public $logo_file = null;

    #[Validate('nullable|image|max:4096')]
    public $cover_file = null;

    public string $slug_input = '';

    public function mount(): void
    {
        $u = auth()->user();
        $this->display_name = $u->display_name ?: $u->name;
        $this->tagline = $u->tagline ?? '';
        $this->description = $u->description ?? '';
        $this->website = $u->website ?? '';
        $this->phone = $u->phone ?? '';
        $this->address = $u->address ?? '';
        $this->instagram_handle = $u->instagram_handle ?? '';
        $this->facebook_url = $u->facebook_url ?? '';
        $this->accent_color = $u->accent_color ?: User::DEFAULT_ACCENT;
        $this->slug_input = $u->slug ?? '';
    }

    public function rules(): array
    {
        return [
            'slug_input' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/',
                'not_in:'.implode(',', User::RESERVED_SLUGS)],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        $user = auth()->user();

        if ($this->logo_file) {
            $path = $this->logo_file->store('agency-logos', 'public');
            $user->logo_url = \Storage::url($path);
        }

        if ($this->cover_file) {
            $path = $this->cover_file->store('agency-covers', 'public');
            $user->cover_url = \Storage::url($path);
        }

        $user->fill([
            'display_name' => $this->display_name,
            'tagline' => $this->tagline ?: null,
            'description' => $this->description ?: null,
            'website' => $this->website ?: null,
            'phone' => $this->phone ?: null,
            'address' => $this->address ?: null,
            'instagram_handle' => $this->instagram_handle ? ltrim($this->instagram_handle, '@') : null,
            'facebook_url' => $this->facebook_url ?: null,
            'accent_color' => $this->accent_color ?: null,
        ]);

        if ($this->slug_input !== '' && $this->slug_input !== $user->slug) {
            $user->slug = User::generateUniqueSlug($this->slug_input, $user->id);
        }

        $user->save();

        $this->logo_file = null;
        $this->cover_file = null;

        session()->flash('status', 'Профилот е ажуриран.');

        return redirect()->route('agency.profile.edit');
    }

    public function removeLogo(): void
    {
        auth()->user()->update(['logo_url' => null]);
    }

    public function removeCover(): void
    {
        auth()->user()->update(['cover_url' => null]);
    }

    public function regenerateWhatsAppCode(): void
    {
        auth()->user()->regenerateWhatsAppPairingCode();
        session()->flash('status', 'Нов код за WhatsApp е генериран.');
    }

    public function unpairWhatsApp(): void
    {
        auth()->user()->forceFill([
            'wa_phone_e164' => null,
            'wa_paired_at' => null,
            'wa_pairing_code' => null,
        ])->save();
        session()->flash('status', 'WhatsApp бројот е одврзан.');
    }
};
?>

<div class="space-y-8">
    <form wire:submit="save" class="space-y-8">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Бренд</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="label">Име на агенција <span class="text-red-500">*</span></label>
                    <input wire:model.blur="display_name" type="text" class="input"
                        placeholder="пр. Балкан Травел">
                    @error('display_name') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="label">Слоган</label>
                    <input wire:model.blur="tagline" type="text" class="input"
                        placeholder="пр. Вашите соништа за патување, нашата мисија">
                    @error('tagline') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="label">Опис</label>
                    <textarea wire:model.blur="description" rows="5" class="input"
                        placeholder="Накратко за вашата агенција, искуство, понуди…"></textarea>
                    @error('description') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Лого и cover</h2>
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label class="label">Лого</label>
                    <div class="flex items-center gap-3">
                        @if (auth()->user()->logo_url)
                            <img src="{{ auth()->user()->logo_url }}" alt="Лого"
                                class="h-16 w-16 rounded-md object-cover border border-slate-200">
                            <button type="button" wire:click="removeLogo"
                                class="text-sm text-red-700 hover:underline">Отстрани</button>
                        @endif
                    </div>
                    <input wire:model="logo_file" type="file" accept="image/*" class="input mt-2">
                    <p class="mt-1 text-xs text-slate-500">PNG/JPG/WEBP, до 2 MB. Препорачано квадрат.</p>
                    @error('logo_file') <p class="error">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="logo_file" class="mt-1 text-xs text-slate-500">
                        Се качува…
                    </div>
                    @if ($logo_file)
                        <img src="{{ $logo_file->temporaryUrl() }}" alt="Преглед"
                            class="mt-2 h-16 w-16 rounded-md object-cover">
                    @endif
                </div>

                <div>
                    <label class="label">Cover (банер)</label>
                    <div class="mb-2">
                        @if (auth()->user()->cover_url)
                            <img src="{{ auth()->user()->cover_url }}" alt="Cover"
                                class="h-24 w-full rounded-md object-cover border border-slate-200">
                            <button type="button" wire:click="removeCover"
                                class="mt-1 text-sm text-red-700 hover:underline">Отстрани</button>
                        @endif
                    </div>
                    <input wire:model="cover_file" type="file" accept="image/*" class="input">
                    <p class="mt-1 text-xs text-slate-500">До 4 MB. Препорачано 1600×400.</p>
                    @error('cover_file') <p class="error">{{ $message }}</p> @enderror
                    @if ($cover_file)
                        <img src="{{ $cover_file->temporaryUrl() }}" alt="Преглед"
                            class="mt-2 h-24 w-full rounded-md object-cover">
                    @endif
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Боја и URL</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="label">Акцент боја</label>
                    <div class="flex items-center gap-3">
                        <input wire:model.live="accent_color" type="color"
                            class="h-10 w-20 rounded-md border border-slate-300 cursor-pointer">
                        <input wire:model.blur="accent_color" type="text" class="input flex-1"
                            placeholder="#0284c7">
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Се применува на вашата страница и копчиња.</p>
                    @error('accent_color') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Веб URL (адреса на страницата)</label>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-slate-500 whitespace-nowrap">/agencija/</span>
                        <input wire:model.blur="slug_input" type="text" class="input"
                            placeholder="balkan-travel">
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        Само мали букви, броеви и цртичка. Тековно:
                        <a href="{{ route('agency.show', auth()->user()) }}" target="_blank"
                            class="text-sky-700 hover:underline">/agencija/{{ auth()->user()->slug }}</a>
                    </p>
                    @error('slug_input') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Контакт</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="label">Телефон</label>
                    <input wire:model.blur="phone" type="text" class="input" placeholder="+389 …">
                    @error('phone') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Веб-сајт</label>
                    <input wire:model.blur="website" type="url" class="input" placeholder="https://…">
                    @error('website') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="label">Адреса</label>
                    <input wire:model.blur="address" type="text" class="input"
                        placeholder="ул. …, Скопје">
                    @error('address') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Instagram handle</label>
                    <input wire:model.blur="instagram_handle" type="text" class="input"
                        placeholder="@nasa_agencija">
                    <p class="mt-1 text-xs text-slate-500">Без @ или со — двете работат.</p>
                    @error('instagram_handle') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Facebook URL</label>
                    <input wire:model.blur="facebook_url" type="url" class="input"
                        placeholder="https://facebook.com/…">
                    @error('facebook_url') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <div class="flex items-center justify-between">
            <a href="{{ route('agency.show', auth()->user()) }}" target="_blank"
                class="text-sm font-medium text-sky-700 hover:underline">Прегледај јавна страница →</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Зачувај профил</span>
                <span wire:loading wire:target="save">Се зачувува…</span>
            </button>
        </div>
    </form>

    {{-- WhatsApp pairing (extra section, separate from the save form) --}}
    @php $u = auth()->user(); @endphp
    <section class="mt-8 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-2 text-lg font-semibold text-slate-900">WhatsApp директен upload</h2>
        <p class="text-sm text-slate-600">
            Парирајте го вашиот WhatsApp број за да можете да праќате слики директно.
            Така можете брзо да поставувате огласи од терен.
        </p>

        @if ($u->isWhatsAppPaired())
            <div class="mt-4 flex items-center justify-between gap-3 flex-wrap rounded-md bg-emerald-50 border border-emerald-200 p-3">
                <div>
                    <p class="text-sm font-medium text-emerald-900">Парирано ✓</p>
                    <p class="text-xs text-emerald-800">Број: <strong>{{ $u->wa_phone_e164 }}</strong> · од {{ $u->wa_paired_at->format('d.m.Y') }}</p>
                </div>
                <button type="button" wire:click="unpairWhatsApp" class="text-sm text-red-700 hover:underline">
                    Одврзи број
                </button>
            </div>
        @else
            <div class="mt-4 rounded-md bg-slate-50 border border-slate-200 p-4">
                <p class="text-sm font-medium text-slate-700 mb-1">Ваш код за парирање:</p>
                <div class="flex items-center gap-3 flex-wrap">
                    <code class="rounded bg-white border border-slate-300 px-3 py-1.5 text-lg font-bold tracking-widest">
                        {{ $u->wa_pairing_code ?? '—' }}
                    </code>
                    <button type="button" wire:click="regenerateWhatsAppCode" class="btn-secondary text-sm">
                        {{ $u->wa_pairing_code ? 'Генерирај нов' : 'Генерирај код' }}
                    </button>
                </div>
                <p class="mt-3 text-sm text-slate-600">
                    Како: испратете го кодот <strong>како прва порака</strong> на нашиот WhatsApp Business број.
                    После тоа може директно да праќате слики; кога завршите, напишете <code>ГОТОВО</code>.
                </p>
            </div>
        @endif
    </section>
</div>
