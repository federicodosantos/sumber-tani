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
            abort(404, 'Certificate not found');
        }
        
        return Response::make(file_get_contents($path), 200, [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function sign(Request $request)
    {
        $toSign = $request->input('toSign');
        
        if (!$toSign) {
            return response('Missing toSign parameter', 400);
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
        $result = openssl_sign($toSign, $signature, $privateKey, OPENSSL_ALGO_SHA512);
        
        if (!$result) {
            return response('Signing failed', 500);
        }

        return response(base64_encode($signature), 200, [
            'Content-Type' => 'text/plain',
        ]);
    }
}