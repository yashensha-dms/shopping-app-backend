<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * NetworkPrinterController
 *
 * Forwards raw ESC/POS print jobs to a network (WiFi) thermal printer
 * via a TCP socket connection to the printer's IP:port.
 *
 * The browser cannot open raw TCP connections, so the backend acts as a bridge:
 *   Browser  --(HTTP POST: binary ESC/POS data)-->  Backend  --(TCP:9100)-->  Printer
 *
 * Printer IP is configured via PRINTER_IP / PRINTER_PORT env variables.
 */
class NetworkPrinterController extends Controller
{
    /** Default raw print port for most network thermal printers */
    const DEFAULT_PORT = 9100;

    /** Timeout in seconds for TCP connection to printer */
    const CONNECT_TIMEOUT = 5;

    /**
     * Receive raw ESC/POS bytes from the browser and forward to the network printer.
     *
     * Request body: raw binary ESC/POS data (application/octet-stream)
     *   OR JSON with base64-encoded data: { "data": "<base64>" }
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function print(Request $request): JsonResponse
    {
        // Check if a dynamic IP is provided in the request, otherwise fall back to config/env.
        $printerIp   = $request->input('ip') ?: config('printer.ip', env('PRINTER_IP', '192.168.18.186'));
        $printerPort = (int) config('printer.port', env('PRINTER_PORT', self::DEFAULT_PORT));

        // Accept raw bytes (octet-stream) OR base64 JSON body
        $contentType = $request->header('Content-Type', '');

        if (str_contains($contentType, 'application/json')) {
            $base64 = $request->input('data');
            if (!$base64) {
                return response()->json(['error' => 'Missing data field.'], 422);
            }
            $rawData = base64_decode($base64);
            if ($rawData === false) {
                return response()->json(['error' => 'Invalid base64 data.'], 422);
            }
        } else {
            // Raw binary body
            $rawData = $request->getContent();
        }

        if (empty($rawData)) {
            return response()->json(['error' => 'No print data received.'], 422);
        }

        // Open TCP connection to the printer
        $socket = @fsockopen($printerIp, $printerPort, $errno, $errstr, self::CONNECT_TIMEOUT);

        if (!$socket) {
            return response()->json([
                'error'   => "Cannot connect to printer at {$printerIp}:{$printerPort}.",
                'details' => "{$errno}: {$errstr}",
            ], 503);
        }

        $written = fwrite($socket, $rawData);
        fclose($socket);

        if ($written === false || $written === 0) {
            return response()->json([
                'error' => 'Connected to printer but failed to send data.',
            ], 500);
        }

        return response()->json([
            'success'      => true,
            'printer_ip'   => $printerIp,
            'printer_port' => $printerPort,
            'bytes_sent'   => $written,
        ]);
    }

    /**
     * Connectivity test — just tries to open a TCP connection to the printer.
     *
     * @return JsonResponse
     */
    public function ping(Request $request): JsonResponse
    {
        $printerIp   = $request->query('ip') ?: config('printer.ip', env('PRINTER_IP', '192.168.18.186'));
        $printerPort = (int) config('printer.port', env('PRINTER_PORT', self::DEFAULT_PORT));

        $socket = @fsockopen($printerIp, $printerPort, $errno, $errstr, self::CONNECT_TIMEOUT);

        if (!$socket) {
            return response()->json([
                'online'  => false,
                'printer' => "{$printerIp}:{$printerPort}",
                'error'   => "{$errno}: {$errstr}",
            ], 503);
        }

        fclose($socket);

        return response()->json([
            'online'  => true,
            'printer' => "{$printerIp}:{$printerPort}",
        ]);
    }
}