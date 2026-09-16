<?php

namespace Mohammadhprp\IPToCountryFlagColumn\Resolution;

/**
 * Identifies one asynchronous cell: which column, which record.
 *
 * The token travels to the browser and comes back on every batch request, so both
 * segments are base64url-encoded before being joined with ":". Record keys may
 * legitimately contain ":" (composite or string keys), which is exactly why the
 * encoder is applied per segment rather than joining raw values.
 *
 * Decoding is strict: anything that is not exactly two decodable, non-empty
 * segments is rejected rather than guessed at.
 */
final readonly class CellToken
{
    public function __construct(
        public string $columnName,
        public string $recordKey,
    ) {}

    public function encode(): string
    {
        return self::encodeSegment($this->columnName).':'.self::encodeSegment($this->recordKey);
    }

    public static function decode(string $token): ?self
    {
        $segments = explode(':', $token);

        if (count($segments) !== 2) {
            return null;
        }

        $columnName = self::decodeSegment($segments[0]);
        $recordKey = self::decodeSegment($segments[1]);

        if ($columnName === null || $recordKey === null) {
            return null;
        }

        if ($columnName === '' || $recordKey === '') {
            return null;
        }

        return new self($columnName, $recordKey);
    }

    private static function encodeSegment(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function decodeSegment(string $value): ?string
    {
        if ($value === '' || preg_match('/[^A-Za-z0-9\-_]/', $value) === 1) {
            return null;
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
