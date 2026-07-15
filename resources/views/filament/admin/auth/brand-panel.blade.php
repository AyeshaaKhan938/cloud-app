{{--
    VMFS USA — Custom Login Brand Panel
    Injected via render hook (panels::body.start) only on unauthenticated pages.
    Creates a split-screen layout: left branding / right Filament login form.
--}}
<style>
    /* ── Reset body to flex row (split layout) ──────────────────────────── */
    body.fi-body {
        display:         flex        !important;
        flex-direction:  row         !important;
        align-items:     stretch     !important;
        padding:         0           !important;
        gap:             0           !important;
        min-height:      100vh       !important;
        background:      #040d18     !important;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    /* ── Left brand panel ───────────────────────────────────────────────── */
    .vmfs-brand-panel {
        width:            44%;
        min-height:       100vh;
        flex-shrink:      0;
        background:       linear-gradient(152deg, #002244 0%, #003d7a 48%, #0066cc 100%);
        display:          flex;
        flex-direction:   column;
        justify-content:  space-between;
        padding:          60px 52px;
        position:         relative;
        overflow:         hidden;
    }

    /* Decorative glow — bottom-right */
    .vmfs-brand-panel::before {
        content:       '';
        position:      absolute;
        width:         520px;
        height:        520px;
        border-radius: 50%;
        background:    radial-gradient(circle, rgba(255,255,255,0.09) 0%, transparent 65%);
        bottom:        -160px;
        right:         -160px;
        pointer-events: none;
    }

    /* Decorative glow — top-left */
    .vmfs-brand-panel::after {
        content:       '';
        position:      absolute;
        width:         280px;
        height:        280px;
        border-radius: 50%;
        background:    radial-gradient(circle, rgba(0,40,100,0.5) 0%, transparent 70%);
        top:           -70px;
        left:          -70px;
        pointer-events: none;
    }

    /* ── Brand content (above footer) ──────────────────────────────────── */
    .vmfs-brand-inner {
        position:       relative;
        z-index:        1;
        display:        flex;
        flex-direction: column;
        justify-content: center;
        flex:           1;
    }

    /* Logo row */
    .vmfs-logo-row {
        display:     flex;
        align-items: center;
        gap:         16px;
        margin-bottom: 52px;
    }

    .vmfs-logo-box {
        width:            56px;
        height:           56px;
        background:       rgba(255,255,255,0.12);
        border:           1px solid rgba(255,255,255,0.22);
        border-radius:    14px;
        display:          flex;
        align-items:      center;
        justify-content:  center;
        flex-shrink:      0;
        backdrop-filter:  blur(6px);
    }

    .vmfs-logo-box svg {
        width:        30px;
        height:       30px;
        stroke:       #ffffff;
        fill:         none;
        stroke-width: 1.6;
        stroke-linecap:  round;
        stroke-linejoin: round;
    }

    .vmfs-logo-texts .vmfs-name {
        font-size:     26px;
        font-weight:   800;
        color:         #ffffff;
        line-height:   1;
        letter-spacing: -0.4px;
    }

    .vmfs-logo-texts .vmfs-sub {
        font-size:      10px;
        font-weight:    500;
        color:          rgba(255,255,255,0.50);
        letter-spacing: 3.5px;
        text-transform: uppercase;
        margin-top:     5px;
        display:        block;
    }

    /* Headline */
    .vmfs-headline {
        font-size:      40px;
        font-weight:    700;
        color:          #ffffff;
        line-height:    1.15;
        letter-spacing: -0.8px;
        margin-bottom:  16px;
    }

    .vmfs-desc {
        font-size:     15px;
        color:         rgba(255,255,255,0.58);
        line-height:   1.7;
        max-width:     360px;
        margin-bottom: 52px;
    }

    /* Feature list */
    .vmfs-features {
        list-style:     none;
        margin:         0;
        padding:        0;
        display:        flex;
        flex-direction: column;
        gap:            15px;
    }

    .vmfs-features li {
        display:     flex;
        align-items: center;
        gap:         13px;
        font-size:   14px;
        font-weight: 500;
        color:       rgba(255,255,255,0.82);
    }

    .vmfs-dot {
        width:         7px;
        height:        7px;
        border-radius: 50%;
        background:    rgba(255,255,255,0.45);
        flex-shrink:   0;
    }

    /* Footer */
    .vmfs-brand-foot {
        position:  relative;
        z-index:   1;
        font-size: 12px;
        color:     rgba(255,255,255,0.28);
    }

    /* ── Right side — Filament v5 real class names ─────────────────────
     *  Structure (confirmed via curl):
     *    body.fi-body
     *      └── div.fi-simple-layout          ← flex child, must be flex:1
     *            └── div.fi-simple-main-ctn  ← outer card wrapper
     *                  └── div.fi-simple-main.fi-width-lg  ← card content
     * ──────────────────────────────────────────────────────────────── */

    /* Wrapper that sits alongside our brand panel */
    .fi-simple-layout {
        flex:            1             !important;
        min-width:       0             !important;
        background:      #040d18       !important;
        min-height:      100vh         !important;
        display:         flex          !important;
        align-items:     center        !important;
        justify-content: center        !important;
    }

    /* Card container — apply the dark glass look here */
    .fi-simple-main-ctn {
        background:    #0c1a2e                          !important;
        border:        1px solid rgba(255,255,255,0.08) !important;
        box-shadow:    0 28px 72px rgba(0,0,0,0.60)     !important;
        border-radius: 20px                             !important;
        overflow:      hidden                           !important;
        padding:       8px                              !important;
    }

    /* Inner card — remove Filament's own bg so our container shows */
    .fi-simple-main {
        background:    transparent !important;
        padding:       32px 36px   !important;
    }

    /* Brand name + "Sign in" heading */
    .fi-simple-header-heading,
    .fi-logo {
        color: #ffffff !important;
    }

    /* Sub-heading (Sign in) */
    .fi-simple-header .fi-simple-header-heading:last-child {
        color: rgba(255,255,255,0.55) !important;
        font-size: 15px !important;
    }
</style>

<div class="vmfs-brand-panel">

    {{-- ── Brand content ── --}}
    <div class="vmfs-brand-inner">

        {{-- Logo --}}
        <div class="vmfs-logo-row">
            <div class="vmfs-logo-box">
                <svg viewBox="0 0 24 24">
                    {{-- Vending machine outline --}}
                    <rect x="3"    y="2"    width="18" height="20" rx="2"/>
                    <rect x="6.5"  y="5"    width="4"  height="3.5" rx="0.8"/>
                    <rect x="13.5" y="5"    width="4"  height="3.5" rx="0.8"/>
                    <rect x="6.5"  y="11.5" width="4"  height="3.5" rx="0.8"/>
                    <rect x="13.5" y="11.5" width="4"  height="3.5" rx="0.8"/>
                    <line x1="3"   y1="10"  x2="21"    y2="10"/>
                    <line x1="12"  y1="10"  x2="12"    y2="22"/>
                    <rect x="9"    y="18.5" width="6"  height="1.5" rx="0.5"/>
                </svg>
            </div>
            <div class="vmfs-logo-texts">
                <div class="vmfs-name">VMFS USA</div>
                <span class="vmfs-sub">Admin Portal</span>
            </div>
        </div>

        {{-- Headline --}}
        <h1 class="vmfs-headline">Vending Machine<br>Management Platform</h1>
        <p class="vmfs-desc">
            Complete control over your vending network — machines, inventory, lottery &amp; analytics, all in one place.
        </p>

        {{-- Features --}}
        <ul class="vmfs-features">
            <li><span class="vmfs-dot"></span> Machine Monitoring &amp; Remote Control</li>
            <li><span class="vmfs-dot"></span> Lottery &amp; Promotional Engine</li>
            <li><span class="vmfs-dot"></span> Inventory &amp; Slot Management</li>
            <li><span class="vmfs-dot"></span> Sales Analytics &amp; Reporting</li>
        </ul>

    </div>

    {{-- ── Footer ── --}}
    <div class="vmfs-brand-foot">
        &copy; {{ date('Y') }} VMFS USA &middot; All rights reserved
    </div>

</div>
