<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanySettingsController extends Controller
{
    public function show(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $company = Company::query()->findOrFail($user->company_id);

        $settings = $company->settings;
        if (! $settings) {
            $settings = $company->settings()->create([
                'ui_theme' => 'light',
                'primary_color' => '#2563eb',
                'mail_notifications_enabled' => true,
            ]);
        }

        return response()->json($settings);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $company = Company::query()->findOrFail($user->company_id);

        $data = $request->validate([
            'ui_theme' => ['sometimes', 'in:light,dark,system'],
            'primary_color' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'mail_notifications_enabled' => ['sometimes', 'boolean'],
        ]);

        $settings = $company->settings()->firstOrCreate(
            [],
            [
                'ui_theme' => 'light',
                'primary_color' => '#2563eb',
                'mail_notifications_enabled' => true,
            ]
        );

        $settings->update($data);

        return response()->json($settings->refresh());
    }

    public function toggleMail(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $company = Company::query()->findOrFail($user->company_id);

        $settings = $company->settings()->firstOrCreate(
            [],
            [
                'ui_theme' => 'light',
                'primary_color' => '#2563eb',
                'mail_notifications_enabled' => true,
            ]
        );

        $settings->update([
            'mail_notifications_enabled' => ! $settings->mail_notifications_enabled,
        ]);

        return response()->json([
            'mail_notifications_enabled' => $settings->fresh()->mail_notifications_enabled,
        ]);
    }
}
