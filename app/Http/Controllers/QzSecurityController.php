<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class QzSecurityController extends Controller
{
    public function certificate()
    {
        $path = storage_path('app/private/qz/digital-certificate.txt');

        if (!file_exists($path)) {
            abort(404, 'digital-certificate.txt not found');
        }

        return Response::make(file_get_contents($path), 200, [
            'Content-Type' => 'text/plain',
        ]);
    }

    // Sign QZ message using private key
    public function sign(Request $request)
    {
        $dataToSign = $request->input('data');

        if (!$dataToSign) {
            return response('Missing data', 400);
        }

        $privateKeyPath = storage_path('app/private/qz/private-key.pem');

        if (!file_exists($privateKeyPath)) {
            return response('Private key not found', 500);
        }

        $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));

        if (!$privateKey) {
            return response('Invalid private key', 500);
        }

        $signature = '';
        $ok = openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$ok) {
            return response('Signing failed', 500);
        }

        return base64_encode($signature);
    }
}
