<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AmoCrmClient;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->json()->all();
        Log::channel('single')->info('Webhook received', $data);

        $client = new AmoCrmClient();

        foreach ($data as $entity => $actions) {
            foreach ($actions as $action => $items) {
                foreach ($items as $item) {
                    $id = $item['id'] ?? 'неизвестен';
                    $note = "Событие: $entity / $action\nID: $id\nВремя: " . now();
                    $client->addNote($entity, $id, $note);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
