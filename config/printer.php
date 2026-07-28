<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Network Thermal Printer Settings
    |--------------------------------------------------------------------------
    |
    | IP address and port of the WiFi/LAN thermal printer.
    | The backend connects directly to the printer via raw TCP (ESC/POS protocol).
    |
    | Standard raw print port for most thermal printers is 9100.
    |
    */

    'ip'   => env('PRINTER_IP',   '192.168.18.186'),
    'port' => env('PRINTER_PORT', 9100),
];