<?php

use App\Mail\ListingInquiryMail;
use App\Models\Inquiry;
use App\Models\Listing;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public Listing $listing;

    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:50')]
    public string $phone = '';

    #[Validate('required|string|min:10|max:2000')]
    public string $bodyMessage = '';

    /** Honeypot — must remain empty. Real users won't fill it. */
    public string $website = '';

    public bool $sent = false;

    public function mount(Listing $listing): void
    {
        $this->listing = $listing;
    }

    public function submit()
    {
        // Honeypot — silently succeed without sending.
        if ($this->website !== '') {
            $this->sent = true;
            $this->reset(['name', 'email', 'phone', 'bodyMessage', 'website']);

            return;
        }

        $key = 'inquiry:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'bodyMessage' => "Премногу прашања во кратко време. Обидете се повторно за {$seconds} секунди.",
            ]);
        }

        $this->validate();

        RateLimiter::hit($key, 3600);

        Inquiry::create([
            'listing_id' => $this->listing->id,
            'agency_id' => $this->listing->user_id,
            'sender_name' => $this->name,
            'sender_email' => $this->email,
            'sender_phone' => $this->phone ?: null,
            'body' => $this->bodyMessage,
            'ip_hash' => hash('sha256', request()->ip().config('app.key')),
        ]);

        $recipient = $this->listing->user?->email ?? $this->listing->agency_contact;
        if ($recipient && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            Mail::to($recipient)->queue(new ListingInquiryMail(
                listing: $this->listing,
                senderName: $this->name,
                senderEmail: $this->email,
                senderPhone: $this->phone ?: null,
                bodyMessage: $this->bodyMessage,
            ));
        }

        $this->sent = true;
        $this->reset(['name', 'email', 'phone', 'bodyMessage', 'website']);
    }

    public function reopen(): void
    {
        $this->sent = false;
    }
};
?>

<div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-semibold text-slate-900">Прашајте ја агенцијата</h2>
    <p class="text-sm text-slate-600 mb-4">Пополнете го формуларот и агенцијата ќе ви одговори директно на email.</p>

    @if ($sent)
        <div class="rounded-md border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-800">
            <p class="font-medium">Пораката е испратена ✓</p>
            <p class="mt-1">Агенцијата ќе ви се јави на вашата email адреса.</p>
            <button type="button" wire:click="reopen" class="mt-3 text-sm font-medium text-emerald-900 hover:underline">
                Испрати уште една
            </button>
        </div>
    @else
        <form wire:submit="submit" class="space-y-3">
            {{-- Honeypot: visually hidden but accessible to bots that fill all fields. --}}
            <div class="absolute left-[-9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                <label>Website</label>
                <input wire:model="website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="label">Име <span class="text-red-500">*</span></label>
                    <input wire:model.blur="name" type="text" class="input" placeholder="Вашето име">
                    @error('name') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Email <span class="text-red-500">*</span></label>
                    <input wire:model.blur="email" type="email" class="input" placeholder="vie@email.com">
                    @error('email') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="label">Телефон (опционално)</label>
                <input wire:model.blur="phone" type="tel" class="input" placeholder="+389 …">
                @error('phone') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Порака <span class="text-red-500">*</span></label>
                <textarea wire:model.blur="bodyMessage" rows="4" class="input"
                    placeholder="Барам понуда за 2 возрасни и 1 дете…"></textarea>
                @error('bodyMessage') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="submit">
                    <span wire:loading.remove wire:target="submit">Испрати прашање</span>
                    <span wire:loading wire:target="submit">Се испраќа…</span>
                </button>
            </div>
        </form>
    @endif
</div>
