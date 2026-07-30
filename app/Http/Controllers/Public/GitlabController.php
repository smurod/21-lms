<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\GitlabService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GitlabController extends Controller
{
    public function show(GitlabService $gitlab): View|RedirectResponse
    {
        $user = auth()->user();
        $gitlabError = null;
        $gitlabReady = false;

        try {
            if ($user) {
                $gitlab->ensureUserAccount($user);
                $gitlabReady = true;
            }
        } catch (\Throwable $e) {
            $gitlabError = 'GitLab API сейчас недоступен для вашей учётной записи: ' . $e->getMessage();
        }

        return view('public.gitlab', [
            'gitlabError' => $gitlabError,
            'gitlabReady' => $gitlabReady,
            'gitlabWebUrl' => $gitlab->ssoUrl(),
            'gitlabLoginUrl' => $gitlab->loginUrl(),
        ]);
    }

    public function signIn(Request $request, GitlabService $gitlab): RedirectResponse
    {
        try {
            $gitlab->ensureUserAccount($request->user());
        } catch (\Throwable $e) {
            return redirect()->route('public.gitlab')
                ->withErrors(['gitlab' => 'GitLab API token недоступен перед SSO: ' . $e->getMessage()]);
        }

        return redirect()->away($gitlab->ssoUrl());
    }
}
