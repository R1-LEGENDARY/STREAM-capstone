<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reading;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TcpServer extends Command
{
    protected $signature = 'tcp:serve';
    protected $description = 'TCP server to receive Arduino data';

    /*
     * DOCU: Receives, validates, and stores sensor readings from the Arduino TCP client.
     * @returns {void} The command remains active while the TCP server is running.
     */
    public function handle(): void
    {
        $address = env('TCP_SERVER_ADDRESS', '0.0.0.0');
        $port    = (int) env('TCP_SERVER_PORT', 5000);

        $sock = stream_socket_server("tcp://{$address}:{$port}", $errno, $errstr);
        if (!$sock) {
            $this->error("Socket error: $errstr ($errno)");
            return;
        }
        $this->info("TCP server running on {$address}:{$port}");

        while ($conn = @stream_socket_accept($sock)) {
            $raw = trim(fread($conn, 2048));

            if (preg_match('/\{.*\}/s', $raw, $match)) {
                $data = json_decode($match[0], true);
                $temperature = $data['Temperature'] ?? null;
                $ph = $data['pH'] ?? null;
                $dissolvedOxygen = $data['DO'] ?? null;

                if (
                    json_last_error() === JSON_ERROR_NONE
                    && is_numeric($temperature)
                    && is_numeric($ph)
                    && is_numeric($dissolvedOxygen)
                    && (float) $temperature >= 0
                    && (float) $temperature <= 60
                    && (float) $ph >= 0
                    && (float) $ph <= 14
                    && (float) $dissolvedOxygen >= 0
                    && (float) $dissolvedOxygen <= 30
                ) {
                    Reading::create([
                        'recorded_at'      => Carbon::now(),
                        'dissolved_oxygen' => (float) $dissolvedOxygen,
                        'ph'               => (float) $ph,
                        'temperature'      => (float) $temperature,
                    ]);
                    Log::info('Reading saved', $data);
                } else {
                    Log::warning('Rejected invalid TCP reading payload.');
                }
            } else {
                Log::warning('Rejected malformed TCP payload.');
            }

            fwrite($conn, "OK\n");
            fclose($conn);
        }

        fclose($sock);
    }
}
