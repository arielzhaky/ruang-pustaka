<?php

namespace App\Support;

use App\Models\BookCopy;
use App\Models\Member;
use InvalidArgumentException;

class LibraryBarcode
{
    // Code 128 symbol patterns from JsBarcode 3.11.6 (MIT); see docs/licenses/JsBarcode-MIT.txt.
    private const PATTERNS = [
        '11011001100', '11001101100', '11001100110', '10010011000', '10010001100',
        '10001001100', '10011001000', '10011000100', '10001100100', '11001001000',
        '11001000100', '11000100100', '10110011100', '10011011100', '10011001110',
        '10111001100', '10011101100', '10011100110', '11001110010', '11001011100',
        '11001001110', '11011100100', '11001110100', '11101101110', '11101001100',
        '11100101100', '11100100110', '11101100100', '11100110100', '11100110010',
        '11011011000', '11011000110', '11000110110', '10100011000', '10001011000',
        '10001000110', '10110001000', '10001101000', '10001100010', '11010001000',
        '11000101000', '11000100010', '10110111000', '10110001110', '10001101110',
        '10111011000', '10111000110', '10001110110', '11101110110', '11010001110',
        '11000101110', '11011101000', '11011100010', '11011101110', '11101011000',
        '11101000110', '11100010110', '11101101000', '11101100010', '11100011010',
        '11101111010', '11001000010', '11110001010', '10100110000', '10100001100',
        '10010110000', '10010000110', '10000101100', '10000100110', '10110010000',
        '10110000100', '10011010000', '10011000010', '10000110100', '10000110010',
        '11000010010', '11001010000', '11110111010', '11000010100', '10001111010',
        '10100111100', '10010111100', '10010011110', '10111100100', '10011110100',
        '10011110010', '11110100100', '11110010100', '11110010010', '11011011110',
        '11011110110', '11110110110', '10101111000', '10100011110', '10001011110',
        '10111101000', '10111100010', '11110101000', '11110100010', '10111011110',
        '10111101110', '11101011110', '11110101110', '11010000100', '11010010000',
        '11010011100', '1100011101011',
    ];

    public static function member(Member $member): string
    {
        return self::encode('RPS', (int) $member->getKey());
    }

    public static function copy(BookCopy $copy): string
    {
        return self::encode('RPC', (int) $copy->getKey());
    }

    private static function encode(string $prefix, int $id): string
    {
        if ($id < 1) {
            throw new InvalidArgumentException('Barcode memerlukan data tersimpan.');
        }

        return $prefix.str_pad((string) $id, 8, '0', STR_PAD_LEFT);
    }

    public static function parse(string $value): ?array
    {
        if (! preg_match('/^(RPS|RPC)([0-9]{8,19})$/D', strtoupper($value), $matches)) {
            return null;
        }
        $digits = ltrim($matches[2], '0');
        if ($digits === '' || strlen($digits) > strlen((string) PHP_INT_MAX) || (strlen($digits) === strlen((string) PHP_INT_MAX) && strcmp($digits, (string) PHP_INT_MAX) > 0)) {
            return null;
        }
        $id = (int) $digits;
        if (strtoupper($value) !== self::encode($matches[1], $id)) {
            return null;
        }

        return ['kind' => $matches[1] === 'RPS' ? 'member' : 'copy', 'id' => $id];
    }

    public static function svg(string $code): string
    {
        if (! self::parse($code)) {
            throw new InvalidArgumentException('Kode barcode tidak valid.');
        }
        // Set B handles our fixed ASCII payload; weighted checksum is modulo 103.
        $symbols = [104];
        $checksum = 104;
        foreach (str_split($code) as $position => $character) {
            $symbol = ord($character) - 32;
            $symbols[] = $symbol;
            $checksum += $symbol * ($position + 1);
        }
        $symbols[] = $checksum % 103;
        $symbols[] = 106;
        $bits = implode('', array_map(fn ($symbol) => self::PATTERNS[$symbol], $symbols));
        $width = strlen($bits) + 20; // Ten quiet modules on each side, preserved at every size.
        preg_match_all('/1+/', $bits, $bars, PREG_OFFSET_CAPTURE);
        $rectangles = '';
        foreach ($bars[0] as [$bar, $offset]) {
            $rectangles .= '<rect x="'.($offset + 10).'" y="0" width="'.strlen($bar).'" height="54"/>';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$width.' 54" width="'.$width.'" height="54" preserveAspectRatio="none" aria-hidden="true"><rect width="100%" height="100%" fill="#fff"/><g fill="#000">'.$rectangles.'</g></svg>';
    }
}
