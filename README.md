## Laravel - F&MD Digital Webhook

[![Downloads](https://img.shields.io/packagist/dt/agenciafmd/laravel-fmd-digital-webhook.svg?style=flat-square)](https://packagist.org/packages/agenciafmd/laravel-fmd-digital)
[![Licença](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

- Envia as conversões para o Webhook da F&MD Digital

## Instalação

```bash
composer require agenciafmd/laravel-fmd-digital:dev-master
```

## Configuração

Primeiro, vamos solicitar a url do Webhook ao time de Digital.

Colocamos esta url no nosso .env

```dotenv
FMD_DIGITAL_WEBHOOK=https://xxxxxx-api.fmd.digital/webhook/e06cab81-6923-40a0-b052-e7d2a1b37c3e
```

## Uso

Envie os campos no formato de array para o SendConversionsToFmdDigitalWebhook.

Para que o processo funcione pelos **jobs**, é preciso passar os valores dos cookies conforme mostrado abaixo.

```php
use Agenciafmd\FmdDigital\Jobs\SendConversionsToFmdDigitalWebhook;
use Illuminate\Support\Facades\Cookie;

$data['email'] = 'irineu@fmd.ag';
$data['nome'] = 'Irineu Junior';

SendConversionsToFmdDigitalWebhook::dispatch($data + [
        'identificador' => 'seja-um-parceiro',
        'utm_campaign' => Cookie::get('utm_campaign', ''),
        'utm_content' => Cookie::get('utm_content', ''),
        'utm_medium' => Cookie::get('utm_medium', ''),
        'utm_source' => Cookie::get('utm_source', ''),
        'utm_term' => Cookie::get('utm_term', ''),
        'gclid_' => Cookie::get('gclid', ''),
        'cid' => Cookie::get('cid', ''),
    ])
    ->delay(5)
    ->onQueue('low');
```

Note que no nosso exemplo, enviamos o job para a fila **low**.

Certifique-se de estar rodando no seu queue:work esteja semelhante ao abaixo.

```shell
php artisan queue:work --tries=3 --delay=5 --timeout=60 --queue=high,default,low
```