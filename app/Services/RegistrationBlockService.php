<?php

namespace App\Services;

use App\Models\RegistrationBlock;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\IpUtils;
use Throwable;

class RegistrationBlockService
{
    public const string CACHE_KEY = 'pf:registration-blocks:rules:v1';

    public const array TYPES = ['ip', 'email', 'email_domain', 'username'];

    public const array MATCH_TYPES = ['exact', 'wildcard', 'regex'];

    public const string IP_MESSAGE = 'Sign ups are not available from your network.';

    public const string EMAIL_MESSAGE = 'This email address can not be used to sign up.';

    public const string USERNAME_MESSAGE = 'This username can not be used.';

    private const int REGEX_BACKTRACK_LIMIT = 20000;

    private const array GMAIL_DOMAINS = ['gmail.com', 'googlemail.com'];

    /**
     * @return array<int, array{id: int, type: string, match_type: string, pattern: string, expires_at: int|null}>
     */
    public static function rules(): array
    {
        return Cache::remember(self::CACHE_KEY, 86400, function () {
            return RegistrationBlock::query()
                ->enforced()
                ->orderBy('id')
                ->get(['id', 'type', 'match_type', 'pattern', 'expires_at'])
                ->map(fn (RegistrationBlock $rule): array => [
                    'id' => (int) $rule->id,
                    'type' => (string) $rule->type,
                    'match_type' => (string) $rule->match_type,
                    'pattern' => (string) $rule->pattern,
                    'expires_at' => $rule->expires_at?->getTimestamp(),
                ])
                ->values()
                ->all();
        });
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function ipBlocked(?string $ip, bool $record = true): bool
    {
        return self::check(ip: $ip, record: $record) !== null;
    }

    public static function emailBlocked(?string $email, bool $record = true): bool
    {
        return self::check(email: $email, record: $record) !== null;
    }

    public static function usernameBlocked(?string $username, bool $record = true): bool
    {
        return self::check(username: $username, record: $record) !== null;
    }

    /**
     * @return array{id: int, type: string, match_type: string, pattern: string, expires_at: int|null}|null
     */
    public static function check(?string $ip = null, ?string $email = null, ?string $username = null, bool $record = true): ?array
    {
        if ($ip === null && $email === null && $username === null) {
            return null;
        }

        $rules = self::rules();

        if (! count($rules)) {
            return null;
        }

        $match = self::find($rules, $ip, $email, $username);

        if ($match && $record) {
            self::recordHit($match['id']);
        }

        return $match;
    }

    /**
     * @param  array<int, array{id: int, type: string, match_type: string, pattern: string, expires_at?: int|null}>  $rules
     * @return array{id: int, type: string, match_type: string, pattern: string, expires_at: int|null}|null
     */
    public static function find(array $rules, ?string $ip = null, ?string $email = null, ?string $username = null): ?array
    {
        $now = time();
        $ip = is_string($ip) && $ip !== '' ? trim($ip) : null;
        $emails = is_string($email) && $email !== '' ? self::emailVariants($email) : [];
        $domain = count($emails) ? Str::afterLast($emails[0], '@') : null;
        $usernames = is_string($username) && $username !== '' ? self::usernameVariants($username) : [];

        foreach ($rules as $rule) {
            $expires = $rule['expires_at'] ?? null;
            if ($expires !== null && $expires <= $now) {
                continue;
            }

            $hit = match ($rule['type']) {
                'ip' => $ip !== null && self::ipMatches($ip, $rule['pattern']),
                'email' => $rule['match_type'] === 'exact'
                    ? count($emails) > 0 && count(array_intersect($emails, self::emailVariants($rule['pattern']))) > 0
                    : self::anyMatches($emails, $rule['match_type'], $rule['pattern']),
                'email_domain' => $domain !== null && self::domainMatches($domain, $rule['match_type'], $rule['pattern']),
                'username' => self::anyMatches($usernames, $rule['match_type'], $rule['pattern']),
                default => false,
            };

            if ($hit) {
                return [
                    'id' => (int) $rule['id'],
                    'type' => $rule['type'],
                    'match_type' => $rule['match_type'],
                    'pattern' => $rule['pattern'],
                    'expires_at' => $expires,
                ];
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    public static function emailVariants(string $email): array
    {
        $email = strtolower(trim($email));

        if (! str_contains($email, '@')) {
            return [$email];
        }

        $local = Str::beforeLast($email, '@');
        $domain = Str::afterLast($email, '@');
        $variants = [$email];

        $untagged = Str::before($local, '+');
        if ($untagged !== '' && $untagged !== $local) {
            $variants[] = $untagged.'@'.$domain;
        }

        if (in_array($domain, self::GMAIL_DOMAINS, true)) {
            $dotless = str_replace('.', '', $untagged !== '' ? $untagged : $local);
            if ($dotless !== '') {
                $variants[] = $dotless.'@gmail.com';
            }
        }

        return array_values(array_unique($variants));
    }

    /**
     * @return string[]
     */
    public static function usernameVariants(string $username): array
    {
        $username = strtolower(trim($username));
        $stripped = str_replace(['.', '_', '-'], '', $username);

        return array_values(array_unique(array_filter([$username, $stripped], fn ($v) => $v !== '')));
    }

    public static function ipMatches(string $ip, string $pattern): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        try {
            return IpUtils::checkIp($ip, trim($pattern));
        } catch (Throwable) {
            return false;
        }
    }

    public static function domainMatches(string $domain, string $matchType, string $pattern): bool
    {
        $domain = strtolower(trim($domain));
        $pattern = strtolower(trim($pattern));

        if ($matchType === 'exact') {
            $pattern = ltrim($pattern, '@');

            return $domain === $pattern || str_ends_with($domain, '.'.$pattern);
        }

        return self::valueMatches($domain, $matchType, $pattern);
    }

    /**
     * @param  string[]  $values
     */
    public static function anyMatches(array $values, string $matchType, string $pattern): bool
    {
        foreach ($values as $value) {
            if (self::valueMatches($value, $matchType, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public static function valueMatches(string $value, string $matchType, string $pattern): bool
    {
        $value = strtolower($value);

        return match ($matchType) {
            'exact' => $value === strtolower(trim($pattern)),
            'wildcard' => self::regexMatches(self::wildcardToRegex($pattern), $value),
            'regex' => self::regexMatches(self::compileRegex($pattern), $value),
            default => false,
        };
    }

    public static function wildcardToRegex(string $pattern): string
    {
        $quoted = preg_quote(strtolower(trim($pattern)), '~');

        return '~^'.str_replace(['\*', '\?'], ['.*', '.'], $quoted).'$~u';
    }

    public static function compileRegex(string $pattern): string
    {
        return '~'.str_replace('~', '\~', trim($pattern)).'~iu';
    }

    protected static function regexMatches(string $regex, string $value): bool
    {
        $previous = ini_get('pcre.backtrack_limit');
        ini_set('pcre.backtrack_limit', (string) self::REGEX_BACKTRACK_LIMIT);

        try {
            return @preg_match($regex, $value) === 1;
        } finally {
            if ($previous !== false) {
                ini_set('pcre.backtrack_limit', $previous);
            }
        }
    }

    public static function validatePattern(string $type, string $matchType, string $pattern): ?string
    {
        $pattern = trim($pattern);

        if (! in_array($type, self::TYPES, true)) {
            return 'Unknown block type.';
        }

        if ($pattern === '') {
            return 'Pattern can not be empty.';
        }

        if (mb_strlen($pattern) > 255) {
            return 'Pattern is too long.';
        }

        if ($type === 'ip') {
            return self::isValidIpOrCidr($pattern) ? null : 'Enter an IP address or CIDR range, like 203.0.113.7 or 203.0.113.0/24.';
        }

        if (! in_array($matchType, self::MATCH_TYPES, true)) {
            return 'Unknown match type.';
        }

        if ($matchType === 'wildcard' && trim($pattern, '*?') === '') {
            return 'A wildcard pattern needs at least one literal character.';
        }

        if ($matchType === 'regex') {
            if (@preg_match(self::compileRegex($pattern), '') === false) {
                return 'Regular expression is invalid.';
            }

            if (self::regexMatches(self::compileRegex($pattern), '')) {
                return 'Regular expression matches an empty value, which would block every signup.';
            }
        }

        if ($type === 'email' && $matchType === 'exact' && filter_var($pattern, FILTER_VALIDATE_EMAIL) === false) {
            return 'Enter a full email address, or use the wildcard match type.';
        }

        if ($type === 'email_domain' && $matchType === 'exact' && ! str_contains(ltrim($pattern, '@'), '.')) {
            return 'Enter a domain like spam.example.';
        }

        return null;
    }

    public static function normalizePattern(string $type, string $matchType, string $pattern): string
    {
        $pattern = trim($pattern);

        if ($type === 'ip' || $matchType === 'regex') {
            return $pattern;
        }

        if ($type === 'email_domain') {
            $pattern = ltrim($pattern, '@');
        }

        return strtolower($pattern);
    }

    /**
     * @throws InvalidArgumentException when the pattern is not usable
     */
    public static function store(
        string $type,
        string $matchType,
        string $pattern,
        ?string $note = null,
        ?CarbonInterface $expiresAt = null,
        ?int $createdBy = null
    ): RegistrationBlock {
        $matchType = $type === 'ip' ? 'exact' : $matchType;

        $error = self::validatePattern($type, $matchType, $pattern);
        if ($error !== null) {
            throw new InvalidArgumentException($error);
        }

        $pattern = self::normalizePattern($type, $matchType, $pattern);
        $note = $note !== null && trim($note) !== '' ? Str::limit(trim($note), 490, '') : null;

        $block = RegistrationBlock::firstOrNew([
            'type' => $type,
            'match_type' => $matchType,
            'pattern' => $pattern,
        ]);

        $block->is_active = true;
        $block->expires_at = $expiresAt;
        $block->note = $note ?? $block->note;
        $block->created_by = $block->created_by ?? $createdBy;
        $block->save();

        return $block;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function parseDuration(?string $value): ?CarbonInterface
    {
        $value = strtolower(trim((string) $value));

        if ($value === '' || $value === 'never' || $value === '0') {
            return null;
        }

        if (! preg_match('/^(\d{1,4})([mhdw])$/', $value, $m)) {
            throw new InvalidArgumentException('Use a duration like 30m, 12h, 7d or 4w.');
        }

        $amount = (int) $m[1];

        if ($amount < 1) {
            throw new InvalidArgumentException('Duration must be at least 1.');
        }

        return match ($m[2]) {
            'm' => now()->addMinutes($amount),
            'h' => now()->addHours($amount),
            'd' => now()->addDays($amount),
            default => now()->addWeeks($amount),
        };
    }

    public static function isValidIpOrCidr(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        if (substr_count($value, '/') !== 1) {
            return false;
        }

        [$address, $bits] = explode('/', $value);

        if (! ctype_digit($bits)) {
            return false;
        }

        $bits = (int) $bits;

        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return $bits >= 8 && $bits <= 32;
        }

        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return $bits >= 16 && $bits <= 128;
        }

        return false;
    }

    protected static function recordHit(int $id): void
    {
        try {
            DB::table('registration_blocks')
                ->where('id', $id)
                ->update([
                    'hits' => DB::raw('hits + 1'),
                    'last_hit_at' => now(),
                ]);
        } catch (Throwable) {
        }
    }
}
