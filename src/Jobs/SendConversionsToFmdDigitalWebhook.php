<?php

namespace Agenciafmd\FmdDigital\Jobs;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class SendConversionsToFmdDigitalWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $retryAfter = 60;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        if (!config('laravel-fmd-digital.webhook')) {
            return;
        }

        try {
            $client = $this->getClientRequest();

            $response = $client->request('POST', config('laravel-fmd-digital.webhook'), [
                'json' => $this->data,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            if (($response->getStatusCode() !== 200) && (config('laravel-fmd-digital.error_email'))) {
                Mail::raw($response->getBody(), function (Message $message) {
                    $message->to(config('laravel-fmd-digital.error_email'))
                        ->subject('[F&MD Digital][' . config('app.url') . '] - Falha na integração - ' . now()->format('d/m/Y H:i:s'));
                });

                // IMPORTANTE: força falha do job → Laravel faz retry
                throw new \Exception('Webhook retornou status: '.$response->getStatusCode());
            }

        } catch (\Throwable $e) {
            \Log::error('Erro no envio webhook FMD Digital', [
                'error' => $e->getMessage(),
                'data' => $this->data,
            ]);

            throw $e; // <- ESSENCIAL para retry da fila
        }
    }

    private function getClientRequest(): Client
    {
        $logger = new Logger('FmdDigital');
        $logger->pushHandler(new StreamHandler(storage_path('logs/fmd-digital-' . date('Y-m-d') . '.log')));

        $stack = HandlerStack::create();
        $stack->push(
            Middleware::log(
                $logger,
                new MessageFormatter('{method} {uri} HTTP/{version} {req_body} | RESPONSE: {code} - {res_body}')
            )
        );

        return new Client([
            'timeout' => 60,
            'connect_timeout' => 60,
            'http_errors' => false,
            'verify' => false,
            'handler' => $stack,
        ]);
    }
}
