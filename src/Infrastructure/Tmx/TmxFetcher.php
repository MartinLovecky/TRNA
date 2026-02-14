<?php

declare(strict_types=1);

namespace Yuha\Trna\Infrastructure\Tmx;

use Yuha\Trna\Core\DTO\TmxRecord;
use Yuha\Trna\Core\Enums\{Field, Misc, Rec};
use Yuha\Trna\Service\{Aseco, HttpClient};

final class TmxFetcher
{
    private const string TMXURI = 'https://tmnforever.tm-exchange.com/apiget.aspx';
    private const string VIDEO = 'https://tmnf.exchange/api/videos';
    public ?int $id = null;
    public ?int $userid = null;
    public ?int $awards = null;
    public ?int $comments = null;
    public ?int $replayid = null;
    public ?int $lbrating = null;
    public ?bool $visible = null;
    public string $uid = '';
    public string $acomment = '';
    public ?string $replayurl = null;
    public ?string $ytlink = null;
    public ?string $ytTitle = null;
    public ?\DateTimeImmutable $publishedAt = null;
    public ?string $game = null;
    public ?string $pageurl = null;
    public ?string $imageurl = null;
    public ?string $thumburl = null;
    public ?string $dloadurl = null;
    public ?string $name = null;
    public ?string $author = null;
    public ?\DateTimeImmutable $uploaded = null;
    public ?\DateTimeImmutable $updated = null;
    public ?string $routes = null;
    public ?string $length = null;
    public ?string $diffic = null;
    public ?string $type = null;
    public ?string $envir = null;
    public ?string $mood = null;
    public ?string $style = null;

    private array $records = [];
    private array $leaderboard = [];

    public function __construct(private HttpClient $httpClient)
    {
        $this->httpClient->setConnectTimeout(60);
    }

    public function initTmx(string $uid): void
    {
        $this->uid = $uid;
        $fields = $this->httpClient->get(self::TMXURI, [
            'action' => 'apitrackinfo',
            'uid'   => $uid,
        ]);

        if (!\is_string($fields)) {
            return;
        }

        $this->populateFields($fields);

        if (!isset($this->id)) {
            return;
        }

        $misc = $this->httpClient->get(self::TMXURI, [
            'action'  => 'apisearch',
            'trackid' => $this->id,
        ]);

        $records = $this->httpClient->get(self::TMXURI, [
            'action' => 'apitrackrecords',
            'id' => $this->id,
        ]);

        $tmxYoutube = $this->httpClient->get(self::VIDEO, [
            'fields'  => 'LinkId,Title,PublishedAt',
            'trackid' => $this->id,
        ]);

        $this->populateMisc($misc);
        $this->populateRecords($records);
        $this->populateLink($tmxYoutube);
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    public function getLeaderboard(): array
    {
        return $this->leaderboard;
    }

    private function populateFields(string $fields): void
    {
        $parts = explode("\t", $fields);
        $conv  = $this->convertFields($parts);

        foreach (Field::cases() as $case) {
            $this->{$case->name} = $conv[$case->value] ?? null;
        }

        $this->acomment = $this->formatComment($conv[Field::comment->value] ?? '');

        if ($this->id !== null) {
            $id = $this->id;
            $this->pageurl  = "https://tmnforever.tm-exchange.com/main.aspx?action=trackshow&id=$id";
            $this->imageurl = "https://tmnforever.tm-exchange.com/get.aspx?action=trackscreen&id=$id";
            $this->thumburl = "https://tmnforever.tm-exchange.com/get.aspx?action=trackscreensmall&id=$id";
            $this->dloadurl = "https://tmnforever.tm-exchange.com/get.aspx?action=trackgbx&id=$id";
        }
    }

    private function populateMisc(string $misc): void
    {
        $fields = explode("\t", $misc);

        $this->awards   = (int)($fields[Misc::awards->value] ?? 0);
        $this->comments = (int)($fields[Misc::comments->value] ?? 0);
        $this->replayid = (int)($fields[Misc::replayid->value] ?? 0);

        if ($this->replayid > 0) {
            $this->replayurl = self::TMXURI . "?action=recordgbx&id={$this->replayid}";
        }
    }

    private function populateRecords(string $rawData): void
    {
        $lines = explode("\r\n", $rawData);
        $rawRecords = \array_slice($lines, 0, 10);

        $this->records = array_values(array_filter(
            array_map(
                fn ($line) => $this->parseRecord($line),
                $rawRecords,
            ),
            static fn ($record) => $record !== null,
        ));

        usort($this->records, static fn (TmxRecord $a, TmxRecord $b) => $a->time <=> $b->time);

        $this->buildLeaderboard();
    }

    private function populateLink(string $raw): void
    {
        $r = Aseco::safeJsonDecode($raw)['Results'][0] ?? [];

        if (!empty($r)) {
            $this->ytlink  = $r['LinkId'];
            $this->ytTitle = $r['Title'];
            $this->publishedAt = $this->tryParseDate($r['PublishedAt']);
        }
    }

    private function parseRecord(string $line): ?TmxRecord
    {
        if ($line === '') {
            return null;
        }

        $f = array_map(
            $this->convertField(...),
            explode("\t", $line),
        );

        return new TmxRecord(
            $f[Rec::replayid->value],
            $f[Rec::userid->value],
            $f[Rec::name->value],
            $f[Rec::time->value],
            $f[Rec::replayat->value] instanceof \DateTimeImmutable ? $f[Rec::replayat->value] : null,
            $f[Rec::trackat->value]  instanceof \DateTimeImmutable ? $f[Rec::trackat->value] : null,
            (bool) $f[Rec::approved->value],
            $f[Rec::score->value],
            $f[Rec::expires->value],
            $f[Rec::lockspan->value],
        );
    }

    private function buildLeaderboard(): void
    {
        if (!$this->records) {
            $this->leaderboard = [];
            return;
        }

        $best = $this->records[0]->time;

        $this->leaderboard = array_map(
            static fn (TmxRecord $r, int $i) => [
                'position' => $i + 1,
                'name'     => $r->name,
                'time'     => Aseco::getFormattedTime($r->time),
                'diff'     => Aseco::getDifference($r->time, $best),
                'score'    => $r->score,
                'url'      => $r->getReplayUrl(),
            ],
            $this->records,
            array_keys($this->records),
        );
    }

    private function convertFields(array $fields): array
    {
        return array_map($this->convertField(...), $fields);
    }

    private function convertField(mixed $value): mixed
    {
        $str = trim((string) $value);
        $lower = strtolower($str);

        return match (true) {
            $str === ''             => null,
            $lower === 'true',
            $str === '1'            => true,
            $lower === 'false',
            $str === '0'            => false,
            $lower === 'null'       => null,
            is_numeric($str)        => ctype_digit($str) ? (int)$str : (float)$str,
            default                 => $this->tryParseDate($str) ?? $str,
        };
    }

    private function tryParseDate(string $str): ?\DateTimeImmutable
    {
        foreach (['m/d/Y g:i:s A', 'Y-m-d H:i:s', 'Y-m-d\TH:i:s', DATE_ATOM] as $fmt) {
            $dt = \DateTimeImmutable::createFromFormat($fmt, $str);
            if ($dt !== false) {
                return $dt;
            }
        }
        return null;
    }

    private function formatComment(string $comment): string
    {
        $comment = htmlspecialchars($comment, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $search  = [\chr(31), '[b]', '[/b]', '[i]', '[/i]', '[u]', '[/u]', '[url]', '[/url]'];
        $replace = ['<br/>', '<b>', '</b>', '<i>', '</i>', '<u>', '</u>', '<i>', '</i>'];

        $formatted = str_ireplace($search, $replace, $comment);

        return preg_replace('/\[url=".*"\]/', '<i>', $formatted) ?? $formatted;
    }
}
