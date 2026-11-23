<?php
declare(strict_types=1);

function spotify_json(string $url, array $headers, ?string $body = null): ?array
{
    $request = curl_init($url);
    if ($request === false) return null;

    curl_setopt_array($request, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($body !== null) {
        curl_setopt($request, CURLOPT_POSTFIELDS, $body);
    }

    $response = curl_exec($request);
    if (curl_getinfo($request, CURLINFO_RESPONSE_CODE) !== 200 || !is_string($response)) {
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

function spotify_match_key(string $name): string
{
    return mb_strtolower(trim((string) preg_replace('/[^\p{L}\p{N}\p{M}]+/u', ' ', $name)), 'UTF-8');
}

function spotify_matching_album_url(array $items, string $title, string $artist, ?string $releaseDate): ?string
{
    $titleKey = spotify_match_key($title);
    $artistKey = spotify_match_key($artist);
    $year = substr((string) $releaseDate, 0, 4);
    $firstMatch = null;

    foreach ($items as $item) {
        if (spotify_match_key($item['name'] ?? '') !== $titleKey) continue;
        $artists = array_map('spotify_match_key', array_column($item['artists'] ?? [], 'name'));
        if (!in_array($artistKey, $artists, true)) continue;

        $url = $item['external_urls']['spotify'] ?? '';
        if (!preg_match('~\Ahttps://open\.spotify\.com/album/[a-zA-Z0-9]{22}\z~', $url)) continue;

        if ($year !== '' && substr($item['release_date'] ?? '', 0, 4) === $year) return $url;
        $firstMatch ??= $url;
    }

    return $firstMatch;
}

function spotify_album(string $title, string $artist, ?string $releaseDate, bool $includeTracks = true): array
{
    $result = ['url' => 'https://open.spotify.com/search/' . rawurlencode($title . ' ' . $artist), 'tracks' => []];
    $clientId = trim((string) getenv('SPOTIFY_CLIENT_ID'));
    $clientSecret = trim((string) getenv('SPOTIFY_CLIENT_SECRET'));
    if ($clientId === '' || $clientSecret === '' || !function_exists('curl_init')
        || !function_exists('mb_strtolower')) return $result;

    $token = spotify_json('https://accounts.spotify.com/api/token', [
        'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
        'Content-Type: application/x-www-form-urlencoded',
    ], 'grant_type=client_credentials')['access_token'] ?? null;
    if (!is_string($token) || $token === '') return $result;

    $market = strtoupper(trim(getenv('SPOTIFY_MARKET') ?: 'MX'));
    $market = preg_match('/^[A-Z]{2}$/D', $market) ? $market : 'MX';
    $query = http_build_query([
        'q' => 'album:' . $title . ' artist:' . $artist,
        'type' => 'album',
        'market' => $market,
        'limit' => 10,
    ], '', '&', PHP_QUERY_RFC3986);
    $headers = ['Authorization: Bearer ' . $token];
    $data = spotify_json('https://api.spotify.com/v1/search?' . $query, $headers);
    $url = spotify_matching_album_url($data['albums']['items'] ?? [], $title, $artist, $releaseDate);
    if ($url === null) return $result;

    $result['url'] = $url;
    if (!$includeTracks) return $result;

    $albumId = basename($url);
    $tracks = [];
    $offset = 0;
    do {
        $page = spotify_json(
            "https://api.spotify.com/v1/albums/{$albumId}/tracks?market={$market}&limit=50&offset={$offset}",
            $headers
        );
        $items = $page['items'] ?? [];
        if ($items === []) return $result;
        foreach ($items as $track) {
            $trackUrl = $track['external_urls']['spotify'] ?? '';
            $tracks[] = [
                'name' => $track['name'],
                'disc' => $track['disc_number'],
                'number' => $track['track_number'],
                'seconds' => intdiv($track['duration_ms'], 1000),
                'url' => preg_match('~^https://open\.spotify\.com/track/[a-zA-Z0-9]{22}$~D', $trackUrl) ? $trackUrl : null,
            ];
        }
        $offset += count($items);
    } while (!empty($page['next']) && $offset < $page['total']);

    $result['tracks'] = $tracks;
    return $result;
}
