<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Models\NotificationMessage;
use Fleetbase\TeraHarvest\Services\Notifications\AfricasTalkingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NotificationController extends Controller
{
    public function __construct(private readonly AfricasTalkingService $at) {}

    public function ussdCallback(Request $request): \Illuminate\Http\Response
    {
        $response = $this->at->handleUssdRequest($request);
        return response($response, 200, ['Content-Type' => 'text/plain']);
    }

    public function sendSms(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone'    => 'required|string',
            'message'  => 'required|string|max:160',
            'language' => 'nullable|in:am,en',
        ]);

        $result = $this->at->sendSms($validated['phone'], $validated['message'], $validated['language'] ?? 'am');
        return response()->json($result);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            NotificationMessage::where('recipient_id', auth()->id())
                ->latest()
                ->paginate(30)
        );
    }

    public function markRead(string $id): JsonResponse
    {
        $msg = NotificationMessage::where('recipient_id', auth()->id())->findOrFail($id);
        $msg->update(['status' => 'delivered', 'delivered_at' => now()]);
        return response()->json($msg);
    }
}
