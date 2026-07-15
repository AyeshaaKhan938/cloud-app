@php
    /** @var \App\Models\User $user */
    $user    = auth()->user();
    $balance = (float) $user->wallet_balance;
    $excess  = (float) $user->wallet_excess_amount;
@endphp

@push('styles')
<style>
/* ─── Wallet page tokens ──────────────────────────────────── */
.rw-hero {
    background: linear-gradient(135deg, #d97706 0%, #92400e 100%);
    border-radius: 1rem;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 25px -5px rgba(217,119,6,.40);
}
.rw-hero-circle {
    position: absolute;
    border-radius: 9999px;
    background: rgba(255,255,255,.10);
    pointer-events: none;
}
.rw-hero-inner {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
@media (min-width: 640px) {
    .rw-hero-inner { flex-direction: row; align-items: center; justify-content: space-between; }
}
.rw-hero-label  { font-size: .8125rem; font-weight: 500; color: rgba(255,255,255,.80); display:flex; align-items:center; gap:.5rem; margin-bottom:.25rem; }
.rw-hero-amount { font-size: 3rem;     font-weight: 800; color: #fff; letter-spacing: -.025em; line-height:1; }
.rw-badge {
    display: inline-flex; align-items: center; gap: .375rem;
    border-radius: 9999px; padding: .25rem .75rem;
    font-size: .75rem; font-weight: 600; margin-top: .5rem;
}
.rw-badge-excess { background: rgba(220,38,38,.25); color: #fca5a5; }
.rw-badge-ok     { background: rgba(255,255,255,.15); color: rgba(255,255,255,.85); }

.rw-topup-btn {
    display: inline-flex; align-items: center; gap: .5rem;
    background: #fff; color: #92400e;
    border: none; border-radius: .75rem;
    padding: .75rem 1.25rem;
    font-size: .875rem; font-weight: 700;
    cursor: pointer; white-space: nowrap;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,.15);
    transition: opacity .15s, transform .1s;
}
.rw-topup-btn:hover { opacity: .92; }
.rw-topup-btn:active { transform: scale(.97); }

/* ─── Stat cards ──────────────────────────────────────────── */
.rw-cards {
    display: grid; gap: 1rem;
    grid-template-columns: 1fr;
}
@media (min-width: 640px) { .rw-cards { grid-template-columns: repeat(3, 1fr); } }

.rw-card {
    display: flex; align-items: flex-start; gap: 1rem;
    border-radius: .875rem; padding: 1.25rem;
    border: 1px solid;
}
/* Light mode */
.rw-card-amber  { border-color: #fcd34d; background: #fffbeb; }
.rw-card-green  { border-color: #6ee7b7; background: #ecfdf5; }
.rw-card-blue   { border-color: #93c5fd; background: #eff6ff; }

/* Dark mode */
.dark .rw-card-amber  { border-color: #78350f; background: #1c1003; }
.dark .rw-card-green  { border-color: #064e3b; background: #022c22; }
.dark .rw-card-blue   { border-color: #1e3a8a; background: #0c1a3a; }

.rw-icon-wrap {
    display: flex; align-items: center; justify-content: center;
    width: 2.75rem; height: 2.75rem; border-radius: 9999px; flex-shrink: 0;
}
.rw-icon-wrap-amber { background: #fef3c7; }
.rw-icon-wrap-green { background: #d1fae5; }
.rw-icon-wrap-blue  { background: #dbeafe; }
.dark .rw-icon-wrap-amber { background: #451a03; }
.dark .rw-icon-wrap-green { background: #022c22; }
.dark .rw-icon-wrap-blue  { background: #1e3a8a; }

.rw-icon-amber { color: #d97706; }
.rw-icon-green { color: #059669; }
.rw-icon-blue  { color: #2563eb; }
.dark .rw-icon-amber { color: #fbbf24; }
.dark .rw-icon-green { color: #34d399; }
.dark .rw-icon-blue  { color: #60a5fa; }

.rw-stat-label {
    font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
    margin-bottom: .25rem;
}
.rw-label-amber { color: #92400e; } .dark .rw-label-amber { color: #fcd34d; }
.rw-label-green { color: #065f46; } .dark .rw-label-green { color: #6ee7b7; }
.rw-label-blue  { color: #1e3a8a; } .dark .rw-label-blue  { color: #93c5fd; }

.rw-stat-value { font-size: 1.5rem; font-weight: 700; }
.rw-value-light { color: #1c1917; }  .dark .rw-value-light { color: #f5f5f4; }

/* ─── Info notice ─────────────────────────────────────────── */
.rw-notice {
    display: flex; align-items: flex-start; gap: .75rem;
    border-radius: .875rem; padding: 1rem;
    border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8;
    font-size: .875rem; line-height: 1.5;
}
.dark .rw-notice { border-color: #1e3a8a; background: #0c1a3a; color: #93c5fd; }
.rw-notice-icon { color: #3b82f6; flex-shrink: 0; margin-top: .125rem; }
.dark .rw-notice-icon { color: #60a5fa; }
</style>
@endpush

<x-filament-panels::page>
    <div style="display:flex; flex-direction:column; gap:1.5rem;">

        {{-- ── HERO ─────────────────────────────────────────────── --}}
        <div class="rw-hero">
            <div class="rw-hero-circle" style="width:180px;height:180px;top:-45px;right:-45px;"></div>
            <div class="rw-hero-circle" style="width:100px;height:100px;bottom:-25px;right:90px;opacity:.6;"></div>

            <div class="rw-hero-inner">
                <div>
                    <p class="rw-hero-label">
                        <x-filament::icon icon="heroicon-o-wallet" class="h-4 w-4" />
                        Wallet Balance
                    </p>
                    <p class="rw-hero-amount">${{ number_format($balance, 2) }}</p>

                    @if ($excess > 0)
                        <span class="rw-badge rw-badge-excess">
                            <x-filament::icon icon="heroicon-o-exclamation-circle" class="h-3.5 w-3.5" />
                            Excess: ${{ number_format($excess, 2) }}
                        </span>
                    @else
                        <span class="rw-badge rw-badge-ok">
                            <x-filament::icon icon="heroicon-o-check-circle" class="h-3.5 w-3.5" />
                            No pending excess
                        </span>
                    @endif
                </div>

                <div>
                    <button type="button" class="rw-topup-btn" wire:click="mountAction('walletTopUp')">
                        <x-filament::icon icon="heroicon-o-plus-circle" class="h-5 w-5" />
                        Top up wallet
                    </button>
                </div>
            </div>
        </div>

        {{-- ── STAT CARDS ───────────────────────────────────────── --}}
        <div class="rw-cards">

            {{-- Recharge pending --}}
            <div class="rw-card rw-card-amber">
                <div class="rw-icon-wrap rw-icon-wrap-amber">
                    <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5 rw-icon-amber" />
                </div>
                <div>
                    <p class="rw-stat-label rw-label-amber">Recharge pending review</p>
                    <p class="rw-stat-value rw-value-light">
                        ${{ number_format((float) $user->wallet_recharge_pending, 2) }}
                    </p>
                </div>
            </div>

            {{-- Accumulated --}}
            <div class="rw-card rw-card-green">
                <div class="rw-icon-wrap rw-icon-wrap-green">
                    <x-filament::icon icon="heroicon-o-arrow-trending-up" class="h-5 w-5 rw-icon-green" />
                </div>
                <div>
                    <p class="rw-stat-label rw-label-green">Accumulated recharge</p>
                    <p class="rw-stat-value rw-value-light">
                        ${{ number_format((float) $user->wallet_accumulated_recharge, 2) }}
                    </p>
                </div>
            </div>

            {{-- Withdrawal pending --}}
            <div class="rw-card rw-card-blue">
                <div class="rw-icon-wrap rw-icon-wrap-blue">
                    <x-filament::icon icon="heroicon-o-arrow-up-tray" class="h-5 w-5 rw-icon-blue" />
                </div>
                <div>
                    <p class="rw-stat-label rw-label-blue">Withdrawal pending review</p>
                    <p class="rw-stat-value rw-value-light">
                        ${{ number_format((float) $user->wallet_withdrawal_pending, 2) }}
                    </p>
                </div>
            </div>

        </div>

        {{-- ── NOTICE ───────────────────────────────────────────── --}}
        <div class="rw-notice">
            <x-filament::icon icon="heroicon-o-information-circle" class="h-5 w-5 rw-notice-icon" />
            <p>
                Top-up requests are reviewed within <strong>1–2 business days</strong>.
                The minimum recharge amount is <strong>$100</strong>.
                Funds will appear in your balance once the review is complete.
            </p>
        </div>

    </div>
</x-filament-panels::page>
