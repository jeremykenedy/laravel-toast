<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Jeremykenedy\LaravelToast\Facades\Toast;
use Jeremykenedy\LaravelToast\Http\Requests\UpdateToastSettingsRequest;
use Jeremykenedy\LaravelToast\Support\ToastSettings;

class ToastSettingsController extends Controller
{
    public function index(): View
    {
        return view(config('toast.settings.view') ?: 'toast::settings-page');
    }

    public function show(): JsonResponse
    {
        return response()->json(ToastSettings::payload());
    }

    public function update(UpdateToastSettingsRequest $request): JsonResponse|RedirectResponse
    {
        if (!ToastSettings::ready()) {
            return $this->notReady($request);
        }

        ToastSettings::save($request->validated());

        if ($request->expectsJson()) {
            return response()->json(ToastSettings::payload());
        }

        Toast::success(__('toast::toast.settings_saved'));

        return back();
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        if (!ToastSettings::ready()) {
            return $this->notReady($request);
        }

        ToastSettings::reset();

        if ($request->expectsJson()) {
            return response()->json(ToastSettings::payload());
        }

        Toast::success(__('toast::toast.settings_reset'));

        return back();
    }

    private function notReady(Request $request): JsonResponse|RedirectResponse
    {
        $message = __('toast::toast.settings_not_ready');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 409);
        }

        Toast::error($message);

        return back();
    }
}
