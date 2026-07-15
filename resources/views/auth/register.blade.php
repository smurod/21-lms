<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register — 21-LMS</title>
    <link rel="stylesheet" href="{{asset('assets/css/style.css')}}" />
</head>
<body class="auth-page">
<div class="auth-card">
    <!-- Decorative pixel staircases -->
    <div class="deco deco-tl" aria-hidden="true">
        <svg width="220" height="200" viewBox="0 0 220 200" fill="none">
            <rect x="120" y="-40" width="80" height="80" rx="18" fill="url(#g1)"/>
            <rect x="60"  y="20"  width="56" height="56" rx="14" fill="url(#g1)" opacity="0.85"/>
            <rect x="16"  y="66"  width="40" height="40" rx="11" fill="url(#g1)" opacity="0.7"/>
            <rect x="-14" y="100" width="28" height="28" rx="8"  fill="url(#g1)" opacity="0.55"/>
            <defs>
                <linearGradient id="g1" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#7dd3fc"/>
                    <stop offset="1" stop-color="#8bea9a"/>
                </linearGradient>
            </defs>
        </svg>
    </div>
    <div class="deco deco-br" aria-hidden="true">
        <svg width="220" height="200" viewBox="0 0 220 200" fill="none">
            <rect x="120" y="-40" width="80" height="80" rx="18" fill="url(#g2)"/>
            <rect x="60"  y="20"  width="56" height="56" rx="14" fill="url(#g2)" opacity="0.85"/>
            <rect x="16"  y="66"  width="40" height="40" rx="11" fill="url(#g2)" opacity="0.7"/>
            <rect x="-14" y="100" width="28" height="28" rx="8"  fill="url(#g2)" opacity="0.55"/>
            <defs>
                <linearGradient id="g2" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#8bea9a"/>
                    <stop offset="1" stop-color="#7dd3fc"/>
                </linearGradient>
            </defs>
        </svg>
    </div>
    <div class="deco deco-tr" aria-hidden="true">
        <svg width="180" height="220" viewBox="0 0 180 220" fill="none">
            <rect x="120" y="-30" width="90" height="90" rx="20" fill="url(#g3)"/>
            <rect x="66"  y="46"  width="60" height="60" rx="15" fill="url(#g3)" opacity="0.8"/>
            <rect x="26"  y="102" width="44" height="44" rx="12" fill="url(#g3)" opacity="0.6"/>
            <rect x="-4"  y="142" width="32" height="32" rx="9"  fill="url(#g3)" opacity="0.45"/>
            <defs>
                <linearGradient id="g3" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#8bea9a"/>
                    <stop offset="1" stop-color="#34d399"/>
                </linearGradient>
            </defs>
        </svg>
    </div>
    <div class="deco deco-bl" aria-hidden="true">
        <svg width="180" height="220" viewBox="0 0 180 220" fill="none">
            <rect x="120" y="-30" width="90" height="90" rx="20" fill="url(#g4)"/>
            <rect x="66"  y="46"  width="60" height="60" rx="15" fill="url(#g4)" opacity="0.8"/>
            <rect x="26"  y="102" width="44" height="44" rx="12" fill="url(#g4)" opacity="0.6"/>
            <rect x="-4"  y="142" width="32" height="32" rx="9"  fill="url(#g4)" opacity="0.45"/>
            <defs>
                <linearGradient id="g4" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#a78bfa"/>
                    <stop offset="1" stop-color="#7dd3fc"/>
                </linearGradient>
            </defs>
        </svg>
    </div>

    <!-- Brand -->
    <div class="auth-brand">
        <div class="auth-brand-mark">
            <!-- Pixel 21 logo -->
            <svg width="48" height="30" viewBox="0 0 44 26" shape-rendering="crispEdges" aria-hidden="true">
                <rect x="2"  y="2"  width="14" height="4" fill="#111827"/>
                <rect x="12" y="6"  width="4"  height="4" fill="#111827"/>
                <rect x="6"  y="10" width="8"  height="4" fill="#111827"/>
                <rect x="2"  y="14" width="4"  height="4" fill="#111827"/>
                <rect x="2"  y="18" width="14" height="4" fill="#111827"/>
                <rect x="26" y="2"  width="8"  height="4" fill="#111827"/>
                <rect x="30" y="6"  width="4"  height="12" fill="#111827"/>
                <rect x="24" y="18" width="16" height="4" fill="#111827"/>
            </svg>
        </div>
        <div class="auth-brand-text">
            <div class="t1">21-LMS</div>
            <div class="t2">GROW FOCUS REPEAT</div>
        </div>
    </div>

    <!-- Left: slogan carousel -->
    <section class="auth-left">
        <div class="slide active">
            <h1>Peer-to-Peer</h1>
            <p>Achieve success together with your team, share your experience, be a participant or a coach.</p>
        </div>
        <div class="slide">
            <h1>Learn by doing</h1>
            <p>No teachers and no classes — real projects, real code reviews and a map of skills that grows with you.</p>
        </div>
        <div class="slide">
            <h1>Gamification</h1>
            <p>Earn XP and coins, level up, collect badges and compete with your tribe in tournaments.</p>
        </div>
        <div class="dots" role="tablist">
            <button class="dot active" data-slide="0" aria-label="Slide 1"></button>
            <button class="dot" data-slide="1" aria-label="Slide 2"></button>
            <button class="dot" data-slide="2" aria-label="Slide 3"></button>
        </div>
    </section>

    <!-- Right: auth panel -->
    <section class="auth-panel">

        <!-- ===== Register form ===== -->
        <form class="auth-form active" id="registerForm" novalidate>
            @csrf
            <h2>Join School 21</h2>
            <p class="auth-sub">Create an account to start your journey at the next-gen School</p>

            <div class="auth-error" id="registerError"></div>

            <div class="field">
                <label for="regName">name</label>
                <input type="text" id="regName" name="name" autocomplete="name" placeholder="Your name" required />
            </div>

            <div class="field">
                <label for="regEmail">email</label>
                <input type="email" id="regEmail" name="email" autocomplete="email" placeholder="you@example.com" required />
            </div>

            <div class="field">
                <label for="regPassword">password</label>
                <input type="password" id="regPassword" name="password" autocomplete="new-password" placeholder="min. 8 characters" required />
                <button class="eye-btn" type="button" data-eye="regPassword" aria-label="Show password">
                    <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/><line x1="4" y1="3" x2="20" y2="21"/></svg>
                </button>
            </div>

            <div class="field">
                <label for="regPassword2">confirm password</label>
                <input type="password" id="regPassword2" name="password_confirmation" autocomplete="new-password" placeholder="repeat the password" required />
                <button class="eye-btn" type="button" data-eye="regPassword2" aria-label="Show password">
                    <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/><line x1="4" y1="3" x2="20" y2="21"/></svg>
                </button>
            </div>

            <div class="auth-actions">
                <button class="login-btn" type="submit">
                    Register
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>
            </div>

            <div class="auth-divider"></div>

            <div class="auth-hint">
                <h3>Already have an account?</h3>
                <p>Go to the <a href="{{ route('login') }}">Log in</a> page and enter your credentials</p>
            </div>
        </form>
    </section>
</div>

<!-- Toast -->
<div class="auth-toast" id="authToast" role="status" aria-live="polite">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
    <span id="authToastText">Welcome!</span>
</div>

<script src="{{asset('assets/js/app.js')}}"></script>
</body>
</html>
