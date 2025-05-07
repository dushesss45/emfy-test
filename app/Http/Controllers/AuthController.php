<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function callback(Request $request)
    {
        $code = $request->query('code');
        if (!$code) {
            return response('No code provided.', 400);
        }

        $settings = config('amocrm');

        $data = [
            'client_id'     => $settings['client_id'],
            'client_secret' => $settings['client_secret'],
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $settings['redirect_uri'],
        ];

        $url = "https://{$settings['subdomain']}.amocrm.ru/oauth2/access_token";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'amoCRM-oAuth-client/1.0',
            CURLOPT_HTTPHEADER => ['Content-Type:application/json'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return response('cURL error: ' . curl_error($ch), 500);
        }

        curl_close($ch);

        $tokens = json_decode($response, true);

        if (!$tokens || isset($tokens['status'])) {
            return response('amoCRM error: ' . ($tokens['hint'] ?? json_encode($tokens)), 400);
        }

        $tokens['created_at'] = time();
        file_put_contents(base_path('tokens.json'), json_encode($tokens));

        return response('Tokens saved.');
    }
}
