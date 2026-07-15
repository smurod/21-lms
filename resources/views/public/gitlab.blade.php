<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign in · GitLab — School 21</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
</head>
<body class="gitlab-page">
<!-- Top bar -->
<div class="gl-topbar">
    <a class="gl-logo" href="{{ route('public.home') }}" title="Back to 21-LMS" aria-label="Back to 21-LMS">
        <!-- Pixel "21" logo -->
        <svg width="44" height="26" viewBox="0 0 44 26" shape-rendering="crispEdges" aria-hidden="true">
            <!-- 2 -->
            <rect x="2"  y="2"  width="14" height="4" fill="#8bea9a"/>
            <rect x="12" y="6"  width="4"  height="4" fill="#8bea9a"/>
            <rect x="6"  y="10" width="8"  height="4" fill="#8bea9a"/>
            <rect x="2"  y="14" width="4"  height="4" fill="#8bea9a"/>
            <rect x="2"  y="18" width="14" height="4" fill="#8bea9a"/>
            <!-- 1 -->
            <rect x="26" y="2"  width="8" height="4"  fill="#8bea9a"/>
            <rect x="30" y="6"  width="4" height="12" fill="#8bea9a"/>
            <rect x="24" y="18" width="16" height="4" fill="#8bea9a"/>
        </svg>
    </a>
</div>

<!-- Main -->
<main class="gl-main">
    <section class="gl-brand">
        <h1>GitLab</h1>
        <h2>A complete DevOps platform</h2>
        <p>GitLab is a single application for the entire software development lifecycle. From project planning and source code management to CI/CD, monitoring, and security.</p>
        <p>This is a self-managed instance of GitLab.</p>
    </section>

    <section class="gl-panels">
        <!-- Sign in form -->
        <form class="gl-panel" id="glSignInForm" novalidate>
            <div class="gl-error" id="glError">Invalid login or password.</div>

            <div class="gl-field">
                <label for="glLogin">Username or email</label>
                <input type="text" id="glLogin" name="login" autocomplete="username" autofocus />
            </div>

            <div class="gl-field">
                <label for="glPassword">Password</label>
                <input type="password" id="glPassword" name="password" autocomplete="current-password" />
            </div>

            <div class="gl-row">
                <label class="gl-check">
                    <input type="checkbox" name="remember" />
                    <span>Remember me</span>
                </label>
                <a class="gl-forgot" href="#forgot">Forgot your password?</a>
            </div>

            <button class="gl-signin-btn" type="submit">Sign in</button>
        </form>

        <!-- SSO -->
        <div class="gl-panel">
            <div class="gl-sso-title">Sign in with</div>
            <button class="gl-sso-btn" type="button" id="glSsoBtn">School21</button>
            <label class="gl-check">
                <input type="checkbox" name="remember-sso" />
                <span>Remember me</span>
            </label>
        </div>
    </section>
</main>

<!-- Footer -->
<footer class="gl-footer">
    <a href="#about">About GitLab</a>
    <a href="#help">Help</a>
    <a href="{{ route('public.projects.index') }}">Back to 21-LMS</a>
</footer>

<script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>
