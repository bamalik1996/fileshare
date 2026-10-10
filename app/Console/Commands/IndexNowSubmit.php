<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Submit URLs to IndexNow (Bing, Yandex, Seznam, Naver...; DuckDuckGo uses Bing).
 *
 *   php artisan indexnow:submit                 # every <loc> in the live sitemap
 *   php artisan indexnow:submit /airdrop-for-pc  # specific paths or full URLs
 */
class IndexNowSubmit extends Command
{
    protected $signature = 'indexnow:submit {urls?* : Paths or full URLs; defaults to all sitemap URLs}';

    protected $description = 'Notify IndexNow search engines about new or updated URLs';

    public function handle(): int
    {
        $key = config('services.indexnow.key');
        $host = config('services.indexnow.host');

        if (! $key || ! $host) {
            $this->error('IndexNow key/host missing (services.indexnow).');

            return self::FAILURE;
        }

        $base = 'https://'.$host;
        $urls = collect($this->argument('urls'))
            ->map(fn ($u) => str_starts_with($u, 'http') ? $u : $base.'/'.ltrim($u, '/'));

        if ($urls->isEmpty()) {
            $xml = Http::timeout(20)->get($base.'/sitemap.xml')->body();
            preg_match_all('#<loc>(.*?)</loc>#', $xml, $m);
            $urls = collect($m[1] ?? []);
        }

        $urls = $urls->filter(fn ($u) => str_starts_with($u, $base))->unique()->values();

        if ($urls->isEmpty()) {
            $this->warn('No URLs to submit.');

            return self::SUCCESS;
        }

        $response = Http::timeout(20)->post('https://api.indexnow.org/indexnow', [
            'host' => $host,
            'key' => $key,
            'keyLocation' => $base.'/'.$key.'.txt',
            'urlList' => $urls->all(),
        ]);

        $this->info('Submitted '.$urls->count().' URL(s) — HTTP '.$response->status());

        return $response->successful() ? self::SUCCESS : self::FAILURE;
    }
}
