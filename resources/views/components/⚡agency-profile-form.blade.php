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

<div class="grid gap-6 lg:grid-cols-[1fr_360px]">
    {{-- ─── Left: form ─────────────────────────────────────────────── --}}
    <div class="space-y-6 min-w-0">
        <form wire:submit="save" class="space-y-6">

            {{-- Section: identity --}}
            @php $sectionHeader = fn ($icon, $title, $desc) => null; @endphp

            <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <header class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="h-9 w-9 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Идентитет</h2>
                        <p class="text-xs text-slate-500">Се појавува како заглавие на твојата јавна страница</p>
                    </div>
                </header>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="label">Име на агенција <span class="text-red-500">*</span></label>
                        <input wire:model.live.debounce.300ms="display_name" type="text" class="input"
                            placeholder="пр. Балкан Травел">
                        @error('display_name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Слоган</label>
                        <input wire:model.live.debounce.300ms="tagline" type="text" class="input"
                            placeholder="пр. Вашите соништа за патување, нашата мисија">
                        @error('tagline') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Опис</label>
                        <textarea wire:model.blur="description" rows="4" class="input"
                            placeholder="Накратко за вашата агенција, искуство, понуди…"></textarea>
                        @error('description') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- Section: brand assets --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <header class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="h-9 w-9 rounded-lg bg-violet-100 text-violet-700 flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Слики</h2>
                        <p class="text-xs text-slate-500">Лого + cover банер за хеаројот на твојата страница</p>
                    </div>
                </header>
                <div class="p-5 grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="label">Лого</label>
                        <div class="flex items-center gap-3">
                            @if (auth()->user()->logo_url)
                                <img src="{{ auth()->user()->logo_url }}" alt="Лого"
                                    class="h-16 w-16 rounded-lg object-cover ring-1 ring-slate-200">
                                <button type="button" wire:click="removeLogo"
                                    class="text-sm text-red-700 hover:underline">Отстрани</button>
                            @else
                                <div class="h-16 w-16 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                                </div>
                            @endif
                        </div>
                        <input wire:model="logo_file" type="file" accept="image/*" class="input mt-3 text-sm">
                        <p class="mt-1 text-xs text-slate-500">PNG/JPG/WEBP до 2 MB. Препорачано квадрат.</p>
                        @error('logo_file') <p class="error">{{ $message }}</p> @enderror
                        <div wire:loading wire:target="logo_file" class="mt-1 text-xs text-slate-500">Се качува…</div>
                        @if ($logo_file)
                            <img src="{{ $logo_file->temporaryUrl() }}" alt="Преглед"
                                class="mt-2 h-16 w-16 rounded-lg object-cover">
                        @endif
                    </div>

                    <div>
                        <label class="label">Cover (банер)</label>
                        <div class="mb-2">
                            @if (auth()->user()->cover_url)
                                <img src="{{ auth()->user()->cover_url }}" alt="Cover"
                                    class="h-20 w-full rounded-lg object-cover ring-1 ring-slate-200">
                                <button type="button" wire:click="removeCover"
                                    class="mt-1 text-sm text-red-700 hover:underline">Отстрани</button>
                            @else
                                <div class="h-20 w-full rounded-lg bg-gradient-to-r from-slate-100 to-slate-200 flex items-center justify-center text-slate-400 text-xs">
                                    нема cover
                                </div>
                            @endif
                        </div>
                        <input wire:model="cover_file" type="file" accept="image/*" class="input text-sm">
                        <p class="mt-1 text-xs text-slate-500">До 4 MB. Препорачано 1600×400.</p>
                        @error('cover_file') <p class="error">{{ $message }}</p> @enderror
                        @if ($cover_file)
                            <img src="{{ $cover_file->temporaryUrl() }}" alt="Преглед"
                                class="mt-2 h-20 w-full rounded-lg object-cover">
                        @endif
                    </div>
                </div>
            </section>

            {{-- Section: color + URL --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <header class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="h-9 w-9 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 0 0 5.304 0l6.401-6.402M6.75 21A3.75 3.75 0 0 1 3 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 0 0 3.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008Z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Боја и URL</h2>
                        <p class="text-xs text-slate-500">Акцент што облекува копчиња + ваша адреса</p>
                    </div>
                </header>
                <div class="p-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="label">Акцент боја</label>
                        <div class="flex items-center gap-2">
                            <label class="relative inline-block h-10 w-10 cursor-pointer rounded-lg ring-2 ring-slate-200 overflow-hidden"
                                style="background-color: {{ $accent_color }};">
                                <input wire:model.live="accent_color" type="color" class="absolute inset-0 opacity-0 cursor-pointer">
                            </label>
                            <input wire:model.live.debounce.300ms="accent_color" type="text" class="input flex-1 font-mono tabular-nums"
                                placeholder="#0284c7">
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Се применува на копчиња + херо на твојата страница.</p>
                        @error('accent_color') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Веб URL</label>
                        <div class="flex items-stretch rounded-md ring-1 ring-slate-300 focus-within:ring-2 focus-within:ring-sky-500 overflow-hidden">
                            <span class="bg-slate-50 text-slate-500 text-sm px-3 inline-flex items-center border-r border-slate-300">/agencija/</span>
                            <input wire:model.blur="slug_input" type="text"
                                class="block w-full border-0 px-3 py-2 text-sm focus:ring-0"
                                placeholder="balkan-travel">
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            Тековно:
                            <a href="{{ route('agency.show', auth()->user()) }}" target="_blank" rel="noopener"
                                class="text-sky-700 hover:underline">/agencija/{{ auth()->user()->slug }}</a>
                        </p>
                        @error('slug_input') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- Section: contact --}}
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <header class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="h-9 w-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Контакт</h2>
                        <p class="text-xs text-slate-500">Како клиенти можат да ве најдат</p>
                    </div>
                </header>
                <div class="p-5 grid gap-4 md:grid-cols-2">
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
                        <label class="label flex items-center gap-1.5">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M7.75 2A5.75 5.75 0 0 0 2 7.75v8.5A5.75 5.75 0 0 0 7.75 22h8.5A5.75 5.75 0 0 0 22 16.25v-8.5A5.75 5.75 0 0 0 16.25 2h-8.5Zm-4.25 5.75A4.25 4.25 0 0 1 7.75 3.5h8.5a4.25 4.25 0 0 1 4.25 4.25v8.5a4.25 4.25 0 0 1-4.25 4.25h-8.5A4.25 4.25 0 0 1 3.5 16.25v-8.5Zm8.5-1A5.25 5.25 0 1 0 17.25 12 5.25 5.25 0 0 0 12 6.75Zm0 1.5A3.75 3.75 0 1 1 8.25 12 3.75 3.75 0 0 1 12 8.25Zm5.5-2.25a1 1 0 1 0 1 1 1 1 0 0 0-1-1Z"/></svg>
                            Instagram handle
                        </label>
                        <input wire:model.blur="instagram_handle" type="text" class="input"
                            placeholder="@nasa_agencija">
                        @error('instagram_handle') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label flex items-center gap-1.5">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.69.24 2.69.24v2.97h-1.52c-1.49 0-1.96.93-1.96 1.89v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/></svg>
                            Facebook URL
                        </label>
                        <input wire:model.blur="facebook_url" type="url" class="input"
                            placeholder="https://facebook.com/…">
                        @error('facebook_url') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- Sticky save bar --}}
            <div class="sticky bottom-4 z-10 flex items-center justify-between rounded-xl border border-slate-200 bg-white px-5 py-3 shadow-lg">
                <p class="text-xs text-slate-500">Промените се применуваат веднаш по зачувување.</p>
                <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700 disabled:opacity-60"
                    wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Зачувај профил</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-1">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        Се зачувува…
                    </span>
                </button>
            </div>
        </form>

        {{-- WhatsApp pairing — own card, outside the save form --}}
        @php $u = auth()->user(); @endphp
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <header class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                <div class="h-9 w-9 rounded-lg bg-green-100 text-green-700 flex items-center justify-center shrink-0">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413"/></svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">WhatsApp директен upload</h2>
                    <p class="text-xs text-slate-500">Праќај слики директно преку WhatsApp</p>
                </div>
            </header>

            <div class="p-5">
                @if ($u->isWhatsAppPaired())
                    <div class="flex items-center justify-between gap-3 flex-wrap rounded-lg bg-emerald-50 border border-emerald-200 p-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-emerald-500 text-white flex items-center justify-center">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-emerald-900">Парирано</p>
                                <p class="text-xs text-emerald-800"><strong>{{ $u->wa_phone_e164 }}</strong> · од {{ $u->wa_paired_at->format('d.m.Y') }}</p>
                            </div>
                        </div>
                        <button type="button" wire:click="unpairWhatsApp"
                            class="text-sm font-medium text-red-700 hover:underline">Одврзи број</button>
                    </div>
                @else
                    <p class="text-sm text-slate-600 mb-3">
                        Испрати го кодот <strong>како прва порака</strong> на нашиот WhatsApp Business број.
                        Потоа може да праќаш слики директно; кога завршиш, напиши <code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs">ГОТОВО</code>.
                    </p>
                    <div class="flex items-center gap-3 flex-wrap">
                        <div class="flex-1 min-w-0">
                            <code class="block rounded-lg bg-slate-900 px-4 py-3 text-center text-2xl font-bold tracking-[0.4em] text-white">
                                {{ $u->wa_pairing_code ?? '— — — — — —' }}
                            </code>
                        </div>
                        <button type="button" wire:click="regenerateWhatsAppCode"
                            class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                            {{ $u->wa_pairing_code ? 'Нов код' : 'Генерирај' }}
                        </button>
                    </div>
                @endif
            </div>
        </section>
    </div>

    {{-- ─── Right: live preview ────────────────────────────────────── --}}
    <aside class="space-y-4 lg:sticky lg:top-6 lg:self-start">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-2 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Жив преглед</span>
                <span class="text-xs text-slate-400">јавна страница</span>
            </div>

            {{-- Hero replica --}}
            <div class="relative" style="background-color: {{ $accent_color }};">
                @if (auth()->user()->cover_url)
                    <img src="{{ auth()->user()->cover_url }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-50">
                @endif
                <div class="relative px-5 py-6 text-white">
                    <div class="flex items-center gap-3">
                        @if (auth()->user()->logo_url || $logo_file)
                            <img src="{{ $logo_file ? $logo_file->temporaryUrl() : auth()->user()->logo_url }}"
                                alt="" class="h-12 w-12 rounded-lg bg-white p-1 object-contain shadow-md">
                        @else
                            <div class="h-12 w-12 rounded-lg bg-white/20 flex items-center justify-center text-xl font-bold shadow-md">
                                {{ mb_substr($display_name ?: 'A', 0, 1) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <h3 class="text-lg font-bold leading-tight truncate">{{ $display_name ?: 'Име на агенција' }}</h3>
                            @if ($tagline)
                                <p class="text-xs opacity-90 line-clamp-2">{{ $tagline }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sample CTA + summary --}}
            <div class="px-5 py-4 space-y-3">
                <button type="button"
                    class="w-full rounded-md py-2 text-sm font-semibold text-white"
                    style="background-color: {{ $accent_color }};"
                    disabled>
                    Прашај за понуда
                </button>

                <dl class="space-y-1.5 text-xs">
                    @if ($phone)
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-500">тел</dt>
                            <dd class="font-medium text-slate-900 truncate">{{ $phone }}</dd>
                        </div>
                    @endif
                    @if ($website)
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-500">web</dt>
                            <dd class="font-medium truncate" style="color: {{ $accent_color }};">{{ $website }}</dd>
                        </div>
                    @endif
                    @if ($instagram_handle)
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-500">IG</dt>
                            <dd class="font-medium text-slate-900">@{{ ltrim($instagram_handle, '@') }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($description)
                    <div class="rounded-md bg-slate-50 p-3 text-xs text-slate-700 line-clamp-4">
                        {{ $description }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Plan badge --}}
        @php $tierName = \App\Models\User::TIER_LABELS[auth()->user()->effectiveTier()] ?? ucfirst(auth()->user()->effectiveTier()); @endphp
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Тековен план</div>
                <div class="text-lg font-bold text-slate-900">{{ $tierName }}</div>
            </div>
            <a href="{{ route('upgrade') }}" class="text-xs font-medium text-sky-700 hover:underline">Прегледи планови →</a>
        </div>
    </aside>
</div>
