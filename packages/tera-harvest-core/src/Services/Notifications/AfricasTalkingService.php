<?php

namespace Fleetbase\TeraHarvest\Services\Notifications;

use Fleetbase\TeraHarvest\Models\UssdSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AfricasTalkingService
{
    private string $apiKey;
    private string $username;
    private string $senderId;
    private string $apiBase = 'https://api.africastalking.com/version1';

    public function __construct()
    {
        $this->apiKey   = config('tera_harvest.africas_talking.api_key');
        $this->username = config('tera_harvest.africas_talking.username');
        $this->senderId = config('tera_harvest.africas_talking.sender_id', 'TeraHarvest');
    }

    public function sendSms(string $phone, string $message, string $language = 'am'): array
    {
        $response = Http::withHeaders([
            'apiKey' => $this->apiKey,
            'Accept' => 'application/json',
        ])->post("{$this->apiBase}/messaging", [
            'username'   => $this->username,
            'to'         => $phone,
            'message'    => $message,
            'from'       => $this->senderId,
        ]);

        if ($response->failed()) {
            Log::error('AfricasTalking SMS failed', ['phone' => $phone, 'body' => $response->body()]);
        }

        return $response->json() ?? [];
    }

    public function sendBulkSms(array $recipients, string $message): array
    {
        $phones = implode(',', $recipients);
        return $this->sendSms($phones, $message);
    }

    public function handleUssdRequest(Request $request): string
    {
        $sessionId   = $request->input('sessionId');
        $phone       = $request->input('phoneNumber');
        $text        = $request->input('text', '');

        $session = UssdSession::firstOrCreate(
            ['session_id' => $sessionId],
            ['phone_number' => $phone, 'current_menu' => 'main', 'status' => 'active']
        );

        return $this->processMenu($session, $text);
    }

    private function processMenu(UssdSession $session, string $text): string
    {
        $parts   = explode('*', $text);
        $current = end($parts);

        if ($text === '') {
            return $this->mainMenu();
        }

        return match ($session->current_menu) {
            'main' => $this->handleMainMenuInput($session, $current),
            default => $this->mainMenu(),
        };
    }

    private function mainMenu(): string
    {
        return "CON ወደ ተራ ሃርቨስት እንኳን ደህና መጡ\n"
            . "1. የኔ ትዕዛዞች\n"
            . "2. የፈጠርኩት ዝርዝር\n"
            . "3. የዋጋ ማረጋገጫ\n"
            . "4. ቅሬታ ለመምዝገብ\n"
            . "0. ለመውጣት";
    }

    private function handleMainMenuInput(UssdSession $session, string $input): string
    {
        return match ($input) {
            '1' => "END የትዕዛዝ ዝርዝር ለማየት ድህረ-ገፁን ይጎብኙ።",
            '2' => "END የዝርዝር ዝርዝር ለማየት ድህረ-ገፁን ይጎብኙ።",
            '3' => "END ዋጋ ዝርዝር ለማግኘት ቆየት ብለው ያነጋግሩን።",
            '4' => "END ቅሬታ ተቀምጧል። ሀ/ቁጥ: " . now()->timestamp,
            '0' => "END ስለተጠቀሙ አስተናጋጅ እናመሰግናለን።",
            default => $this->mainMenu(),
        };
    }
}
