<?php
declare(strict_types=1);
namespace Phptop50;

require __DIR__ . '/../vendor/autoload.php';
use GuzzleHttp\Client;

class Crawler
{
    private Client $http;
    private string $dataDir;
    private string $docsDir;
    private string $relDir;
    private string $readme;

    public function __construct()
    {
        $this->http    = new Client([
            'base_uri' => 'https://api.github.com',
            'headers'  => [
                'Accept'        => 'application/vnd.github+json',
                'User-Agent'    => 'PHP-top50-weekly',
                'Authorization' => isset($_ENV['GITHUB_TOKEN']) ? 'Bearer ' . $_ENV['GITHUB_TOKEN'] : '',
            ],
        ]);
        $this->dataDir = __DIR__ . '/../data';
        $this->docsDir = __DIR__ . '/../docs';
        $this->relDir  = __DIR__ . '/../releases';
        $this->readme  = __DIR__ . '/../README.md';
        foreach ([$this->dataDir, $this->docsDir, $this->relDir] as $d) {
            is_dir($d) or mkdir($d, 0755, true);
        }
    }

    public function run(): void
    {
        $week   = date('W');
        $year   = date('Y');
        $date   = date('Y-m-d');
        $fileId = "{$year}-W{$week}";

        echo "[*] Fetching top50 PHP repos …\n";
        $list = $this->fetchtop50();

        $payload = [
            'year'  => $year,
            'week'  => $week,
            'date'  => $date,
            'list'  => $list,
        ];

        // 1) json 存档
        file_put_contents(
            "{$this->dataDir}/top50-{$fileId}.json",
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        // 2) HTML 2006 风
        $html = (new HtmlTemplate())->render($payload);
        file_put_contents("{$this->docsDir}/top50-{$fileId}.html", $html);

        // 3) Markdown
        $md = (new MarkdownTemplate())->render($payload);
        file_put_contents("{$this->relDir}/top50-{$fileId}.md", $md);

        // 4) README 追加索引
        $this->appendReadme($payload);

        echo "[+] Week $week finished.\n";
    }

    private function fetchtop50(): array
    {
        $res = $this->http->get('/search/repositories', [
            'query' => [
                'q'        => 'language:php',
                'sort'     => 'stars',
                'order'    => 'desc',
                'per_page' => 50,
            ],
        ]);
        $body = json_decode($res->getBody()->getContents(), true);
        $out  = [];
        foreach ($body['items'] as $i => $item) {
            $out[] = [
                'rank' => $i + 1,
                'name' => $item['full_name'],
                'desc' => $item['description'] ?? 'No description',
                'star' => $item['stargazers_count'],
                'fork' => $item['forks_count'],
                'url'  => $item['html_url'],
                'topics' => $item['topics'] ?? [],
            ];
        }
        return $out;
    }

    private function appendReadme(array $pl): void
    {
        $line = "- 第{$pl['week']}期 ({$pl['date']}) [HTML](docs/top50-{$pl['year']}-W{$pl['week']}.html) · [MD](releases/top50-{$pl['year']}-W{$pl['week']}.md)";
        if (!is_file($this->readme)) {
            file_put_contents($this->readme, "# PHP GitHub top50 周刊\n\n## 历史期数\n\n");
        }
        file_put_contents($this->readme, $line . PHP_EOL, FILE_APPEND);
    }
}

/* ---------- 运行 ---------- */
(new Crawler())->run();
