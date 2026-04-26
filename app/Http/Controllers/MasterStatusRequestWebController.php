<?php

namespace App\Http\Controllers;

use App\Enums\AppBrand;
use App\Http\Services\MasterStatusRequestService;
use App\Models\MasterStatusRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterStatusRequestWebController extends Controller
{
    public function show(string $token): View
    {
        $statusRequest = MasterStatusRequest::with('master')
            ->where('id', $token)
            ->orWhere('meta->token', $token)
            ->firstOrFail();

        $brand = AppBrand::fromHeader((string) ($statusRequest->master->app ?? 'carbeat'));

        return view('status-request', [
            'requestModel' => $statusRequest,
            'brandConfig' => $this->brandConfig($brand),
        ]);
    }

    public function respond(
        Request $request,
        string $token,
        MasterStatusRequestService $service
    ): RedirectResponse {
        $statusRequest = MasterStatusRequest::with('master')
            ->where('id', $token)
            ->orWhere('meta->token', $token)
            ->firstOrFail();

        $payload = $request->validate([
            'answer' => ['required', 'in:free,busy'],
        ]);

        $service->respond($statusRequest, $payload['answer'], 'sms');

        return redirect()
            ->route('status-request.show', ['token' => $token])
            ->with('status_request_answered', $payload['answer']);
    }

    private function brandConfig(AppBrand $brand): array
    {
        return match ($brand) {
            AppBrand::FLOXCITY => [
                'app' => 'floxcity',
                'name' => 'FloxCity',
                'eyebrow' => 'FloxCity Request',
                'cta' => 'У додатку зручніше',
                'cta_text' => 'Відповідайте швидше, керуйте профілем і статусом майстра просто в FloxCity.',
                'store_url' => 'https://play.google.com/store/apps/details?id=online.floxcity.app',
                'logo' => 'FC',
                'hero_badge' => '💅 Майстер краси онлайн',
                'request_copy' => 'Клієнт FloxCity хоче приїхати прямо зараз. Підтвердіть, будь ласка, чи ви вільні в цю хвилину.',
            ],
            default => [
                'app' => 'carbeat',
                'name' => 'CarBeat',
                'eyebrow' => 'CarBeat Request',
                'cta' => 'У додатку зручніше',
                'cta_text' => 'Відповідайте швидше, керуйте профілем і отримуйте більше клієнтів просто в CarBeat.',
                'store_url' => config('app.deep_links.android_store_url'),
                'logo' => 'CB',
                'hero_badge' => '🔧 СТО онлайн',
                'request_copy' => 'Клієнт CarBeat хоче приїхати прямо зараз. Підтвердіть, будь ласка, чи ви вільні в цю хвилину.',
            ],
        };
    }
}
