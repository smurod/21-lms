<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>GitLab — School 21</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
</head>
<body class="gitlab-page">
<div class="gl-topbar">
    <a class="gl-logo" href="{{ route('public.home') }}" title="Back to 21-LMS" aria-label="Back to 21-LMS">
        <svg width="44" height="26" viewBox="0 0 44 26" shape-rendering="crispEdges" aria-hidden="true">
            <rect x="2"  y="2"  width="14" height="4" fill="#8bea9a"/>
            <rect x="12" y="6"  width="4"  height="4" fill="#8bea9a"/>
            <rect x="6"  y="10" width="8"  height="4" fill="#8bea9a"/>
            <rect x="2"  y="14" width="4"  height="4" fill="#8bea9a"/>
            <rect x="2"  y="18" width="14" height="4" fill="#8bea9a"/>
            <rect x="26" y="2"  width="8" height="4"  fill="#8bea9a"/>
            <rect x="30" y="6"  width="4" height="12" fill="#8bea9a"/>
            <rect x="24" y="18" width="16" height="4" fill="#8bea9a"/>
        </svg>
    </a>
</div>

<main class="gl-main">
    <section class="gl-brand">
        <h1>GitLab</h1>
        <h2>School21 GitLab</h2>
        <p>21-LMS автоматически создаёт и обслуживает GitLab API-доступ для проектов.</p>
        <p>Ручное подключение token отключено: доступ проверяется сервером через GitLab admin API.</p>
    </section>

    <section class="gl-panels gl-panels-wide">
        <div class="gl-panel gl-compact-connect">
            @if($gitlabError)
                <div class="gl-error show">{{ $gitlabError }}</div>
            @endif
            @if($errors->has('gitlab'))
                <div class="gl-error show">{{ $errors->first('gitlab') }}</div>
            @endif
            @if(session('success'))
                <div class="gl-success show">{{ session('success') }}</div>
            @endif
            @if($gitlabReady)
                <div class="gl-success show">GitLab API доступен.</div>
            @endif

            <div class="gl-sso-title">Sign in with</div>
            <form method="POST" action="{{ route('public.gitlab.sign-in') }}">
                @csrf
                <button class="gl-sso-btn gl-sso-btn-full" type="submit">School21</button>
            </form>

            <a class="gl-manual-login-link" href="{{ $gitlabLoginUrl }}" target="_blank" rel="noopener noreferrer">
                Войти в GitLab вручную по username/password
            </a>
        </div>
    </section>
</main>

<footer class="gl-footer">
    <a href="{{ route('public.home') }}">Back to dashboard</a>
    <a href="{{ route('public.projects.index') }}">Projects</a>
    <a href="{{ route('terms') }}">Terms</a>
</footer>

<script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>
