<?php

declare(strict_types=1);

namespace Yuha\Trna\Service;

use Google\Client as GoogleClient;
use Google\Service\YouTube;
use Google\Service\YouTube\SearchResult;
use Yuha\Trna\Core\DTO\{YoutubeSearchResults, YoutubeVideoResult};

class YoutubeClient
{
    private YouTube $yt;

    public function __construct(private GoogleClient $gClient)
    {
        if (isset($_ENV['G_API_KEY'])) {
            $this->gClient->setDeveloperKey($_ENV['G_API_KEY']);
            $this->yt = new YouTube($this->gClient);
        }
    }

    /**
     * This allow us to search any video on yt
     *
     * @param integer $maxResults
     */
    public function search(string $query, int $maxResults = 20): YoutubeSearchResults
    {
        $response = $this->yt->search->listSearch('snippet', [
            'q'          => $query,
            'maxResults' => $maxResults,
            'type'       => 'video',
        ]);

        /** @var SearchResult[] $items */
        $items = $response['items'] ?? [];
        $ranked = $this->rankResults($items, $query);
        $ranked = $this->filterPlayableVideos($ranked);

        $results = array_map(
            static fn (SearchResult $item) => new YoutubeVideoResult(
                videoLink: 'youtu.be/' . $item->getId()->getVideoId(),
                title: $item->getSnippet()->getTitle(),
                channel: $item->getSnippet()->getChannelTitle(),
                description: $item->getSnippet()->getDescription(),
                publishedAt: new \DateTimeImmutable($item->getSnippet()->getPublishedAt()),
                thumbnail: $item->getSnippet()->getThumbnails()->getDefault()->getUrl(),
            ),
            $ranked,
        );

        return new YoutubeSearchResults($results);
    }

    private function rankResults(array $items, string $query): array
    {
        if (empty($items)) {
            return [];
        }

        $queryNorm = $this->normalizeTitle($query);
        $preferredChannel = $_ENV['Y_PREFERRED'] ?? null;
        $ranked = [];

        foreach ($items as $item) {
            $title = $item->getSnippet()->getTitle() ?? '';
            $titleNorm = $this->normalizeTitle($title);
            $channel  = $item->getSnippet()->getChannelId() ?? '';
            $published = new \DateTimeImmutable($item->getSnippet()->getPublishedAt());

            $wordOverlap = $this->wordOverlapScore($queryNorm, $titleNorm);
            if ($wordOverlap === 0) {
                continue; // No shared words = irrelevant result
            }

            $lev         = Aseco::safeLevenshtein($titleNorm, $queryNorm);
            $levSim      = 1 - ($lev / max(1, max(\strlen($titleNorm), \strlen($queryNorm))));
            if ($levSim < 0.25) {
                continue; // Very weak similarity = garbage
            }

            $damerauSim  = $this->damerauSimilarity($queryNorm, $titleNorm);
            $tagScore    = $this->detectTrackmaniaTags($queryNorm, $titleNorm);
            $exact       = ($titleNorm === $queryNorm);
            $contains    = str_contains($titleNorm, $queryNorm);
            $score =
                ($exact ? 3.0 : 0) +
                ($contains ? 1.5 : 0) +
                ($wordOverlap * 2.0) +
                ($levSim * 1.0) +
                ($damerauSim * 0.7) +
                ($tagScore * 1.4);

            // Preferred channel
            if ($preferredChannel && $preferredChannel === $channel) {
                $score += 0.9;
            }

            // Recency
            $ageDays = (new \DateTimeImmutable())->diff($published)->days;
            $recency = max(0, (730 - $ageDays) / 730);
            $score += $recency * 0.2;

            // Thumbnail quality
            $thumb = $item->getSnippet()->getThumbnails()->getHigh()
                ?? $item->getSnippet()->getThumbnails()->getMedium()
                ?? $item->getSnippet()->getThumbnails()->getDefault();
            if ($thumb && $thumb->getWidth() >= 480) {
                $score += 0.1;
            }

            // Penalty for irrelevant words
            $score -= $this->penaltyIrrelevantWords($titleNorm);

            $ranked[] = [
                'item'  => $item,
                'score' => $score,
            ];
        }

        if (empty($ranked)) {
            return [];
        }

        // Sort by highest score
        usort($ranked, static fn ($a, $b) => $b['score'] <=> $a['score']);

        $best = $ranked[0]['score'];
        $absoluteMin = 2.0;
        $threshold = max($best * 0.7, $absoluteMin);

        return array_map(
            static fn ($r) => $r['item'],
            array_filter($ranked, static fn ($r) => $r['score'] >= $threshold),
        );
    }

    private function normalizeTitle(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $s = str_replace(['-', '_', '|', ':', '[', ']', '(', ')'], ' ', $s);
        $s = preg_replace('/[^a-z0-9\s]+/u', '', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    private function wordOverlapScore(string $query, string $title): float
    {
        $q = array_unique(explode(' ', $query));
        $t = array_unique(explode(' ', $title));
        $match = array_intersect($q, $t);

        return \count($q) === 0 ? 0 : \count($match) / \count($q);
    }

    private function damerauSimilarity(string $a, string $b): float
    {
        $dist = Aseco::safeLevenshtein($a, $b);
        $max = max(\strlen($a), \strlen($b));
        return $max > 0 ? 1 - ($dist / $max) : 0;
    }

    private function detectTrackmaniaTags(string $query, string $title): float
    {
        $tags = [
            'fs',
            'fullspeed',
            'rpg',
            'lol',
            'tech',
            'dirt',
            'pf',
            'pressforward',
            'speedfun',
            'mini-rpg',
            'trial',
            'nascar',
            'lucky jump',
            'kacky',
            'trackmania',
        ];
        $score = 0;

        foreach ($tags as $tag) {
            if (str_contains($query, $tag) && str_contains($title, $tag)) {
                $score += 1;
            }
        }

        return $score;
    }

    private function penaltyIrrelevantWords(string $title): float
    {
        $irrelevant = [
            'funny',
            'compilation',
            'highlight',
            'shorts',
            'meme',
            'reaction',
            'vlog',
            'podcast',
            'interview',
            'review',
            'tutorial',
            'free',
            'episode',
            'full movie',
            'news',
        ];

        $penalty = 0;
        foreach ($irrelevant as $bad) {
            if (str_contains($title, $bad)) {
                $penalty += 0.4;
            }
        }

        return $penalty;
    }

    private function filterPlayableVideos(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $ids = [];
        foreach ($items as $item) {
            $videoId = $item->getId()?->getVideoId();
            if ($videoId) {
                $ids[] = $videoId;
            }
        }

        if (empty($ids)) {
            return [];
        }

        $response = $this->yt->videos->listVideos('status', [
            'id' => implode(',', $ids),
        ]);

        $playableIds = [];
        foreach ($response['items'] as $video) {
            if ($video->getStatus()?->getPrivacyStatus() === 'public') {
                $playableIds[] = $video->getId();
            }
        }

        // Return only playable items
        return array_values(array_filter($items, static function ($item) use ($playableIds) {
            return \in_array($item->getId()->getVideoId(), $playableIds, true);
        }));
    }
}
