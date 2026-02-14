<?php

declare(strict_types=1);

namespace Yuha\Trna\Service;

use Yuha\Trna\Core\Server;

final class Aseco
{
    /**
     * Checks if a given string is valid Base64-encoded data.
     *
     * @param  string $str The string to check.
     * @return bool   True if the string is valid Base64, false otherwise.
     */
    public static function isBase64(string $str): bool
    {
        if (!preg_match('/^(?:[A-Za-z0-9+\/]{4})*(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=)?$/', $str)) {
            return false;
        }
        $decoded = base64_decode($str, true);

        return $decoded !== false && base64_encode($decoded) === $str;
    }

    /**
     * Validates and cleans a UTF-8 string, replacing invalid input if necessary.
     *
     * @param  string $input       Input string to validate.
     * @param  string $invalidRepl Replacement for invalid input (default '?').
     * @return string Cleaned UTF-8 string or replacement.
     */
    public static function validateUTF8(string $input, string $invalidRepl = '?'): string
    {
        $clean = iconv('UTF-8', 'UTF-8//IGNORE', $input);
        return ($clean !== false && $clean !== '') ? $clean : $invalidRepl;
    }

    /**
     * Outputs a formatted message to the console with a timestamp.
     *
     * @param string $text The message text to be displayed in the console.
     */
    public static function console(string $text): void
    {
        $timestamp = date('m/d,H:i:s');
        $message = "[{$timestamp}] {$text}" . PHP_EOL;

        echo $message;
        flush();
    }

    /**
     * Outputs plain text to the console without additional formatting.
     *
     * @param string $text The text to be displayed in the console.
     */
    public static function consoleText(string $text): void
    {
        $formattedText = $text . PHP_EOL;
        echo $formattedText;
        flush();
    }

    /**
     * Safely retrieves the contents of a file.
     *
     * @param  string      $filename The path to the file to read.
     * @return string|null The contents of the file, or null on failure.
     */
    public static function safeFileGetContents(string $filename): ?string
    {
        if (!is_readable($filename) && !is_file($filename)) {
            return null;
        }

        $content = @file_get_contents($filename);

        return $content !== false ? $content : null;
    }

    /**
     * Safely decodes a JSON string into an array.
     *
     * @param ?string $json  The JSON string to decode.
     * @param bool    $assoc Whether to return an associative array (default: true).
     */
    public static function safeJsonDecode(?string $json, bool $assoc = true): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        try {
            $decoded = json_decode($json, $assoc, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return [];
        }

        return \is_array($decoded) ? $decoded : [];
    }

    /**
     * Updates value inside .env root file
     *
     * @param string $key   Must already exist
     * @param string $value new value to be set
     */
    public static function updateEnvFile(string $key, string $value): void
    {
        $envPath = Server::$rootDir . '.env';

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $found = false;

        foreach ($lines as &$line) {
            if (str_starts_with(trim($line), "$key=")) {
                $line = "$key=\"$value\"";
                $found = true;
                break;
            }
        }

        if (!$found) {
            $lines[] = "$key=\"$value\"";
        }

        file_put_contents($envPath, implode(PHP_EOL, $lines) . PHP_EOL);

        $_ENV[$key] = $value;
    }

    /**
     * Converst county name to its game code XXX
     *
     * @param  string $country name of country
     * @return string OTH if not found in list
     */
    public static function mapCountry(string $country): string
    {
        $nations = [
            'Afghanistan' => 'AFG',
            'Albania' => 'ALB',
            'Algeria' => 'ALG',
            'Andorra' => 'AND',
            'Angola' => 'ANG',
            'Argentina' => 'ARG',
            'Armenia' => 'ARM',
            'Aruba' => 'ARU',
            'Australia' => 'AUS',
            'Austria' => 'AUT',
            'Azerbaijan' => 'AZE',
            'Bahamas' => 'BAH',
            'Bahrain' => 'BRN',
            'Bangladesh' => 'BAN',
            'Barbados' => 'BAR',
            'Belarus' => 'BLR',
            'Belgium' => 'BEL',
            'Belize' => 'BIZ',
            'Benin' => 'BEN',
            'Bermuda' => 'BER',
            'Bhutan' => 'BHU',
            'Bolivia' => 'BOL',
            'Bosnia&Herzegovina' => 'BIH',
            'Botswana' => 'BOT',
            'Brazil' => 'BRA',
            'Brunei' => 'BRU',
            'Bulgaria' => 'BUL',
            'Burkina Faso' => 'BUR',
            'Burundi' => 'BDI',
            'Cambodia' => 'CAM',
            'Cameroon' => 'CMR',
            'Canada' => 'CAN',
            'Cape Verde' => 'CPV',
            'Central African Republic' => 'CAF',
            'Chad' => 'CHA',
            'Chile' => 'CHI',
            'China' => 'CHN',
            'Chinese Taipei' => 'TPE',
            'Colombia' => 'COL',
            'Congo' => 'CGO',
            'Costa Rica' => 'CRC',
            'Croatia' => 'CRO',
            'Cuba' => 'CUB',
            'Cyprus' => 'CYP',
            'Czech Republic' => 'CZE',
            'Czech republic' => 'CZE',
            'DR Congo' => 'COD',
            'Denmark' => 'DEN',
            'Djibouti' => 'DJI',
            'Dominica' => 'DMA',
            'Dominican Republic' => 'DOM',
            'Ecuador' => 'ECU',
            'Egypt' => 'EGY',
            'El Salvador' => 'ESA',
            'Eritrea' => 'ERI',
            'Estonia' => 'EST',
            'Ethiopia' => 'ETH',
            'Fiji' => 'FIJ',
            'Finland' => 'FIN',
            'France' => 'FRA',
            'Gabon' => 'GAB',
            'Gambia' => 'GAM',
            'Georgia' => 'GEO',
            'Germany' => 'GER',
            'Ghana' => 'GHA',
            'Greece' => 'GRE',
            'Grenada' => 'GRN',
            'Guam' => 'GUM',
            'Guatemala' => 'GUA',
            'Guinea' => 'GUI',
            'Guinea-Bissau' => 'GBS',
            'Guyana' => 'GUY',
            'Haiti' => 'HAI',
            'Honduras' => 'HON',
            'Hong Kong' => 'HKG',
            'Hungary' => 'HUN',
            'Iceland' => 'ISL',
            'India' => 'IND',
            'Indonesia' => 'INA',
            'Iran' => 'IRI',
            'Iraq' => 'IRQ',
            'Ireland' => 'IRL',
            'Israel' => 'ISR',
            'Italy' => 'ITA',
            'Ivory Coast' => 'CIV',
            'Jamaica' => 'JAM',
            'Japan' => 'JPN',
            'Jordan' => 'JOR',
            'Kazakhstan' => 'KAZ',
            'Kenya' => 'KEN',
            'Kiribati' => 'KIR',
            'Korea' => 'KOR',
            'Kuwait' => 'KUW',
            'Kyrgyzstan' => 'KGZ',
            'Laos' => 'LAO',
            'Latvia' => 'LAT',
            'Lebanon' => 'LIB',
            'Lesotho' => 'LES',
            'Liberia' => 'LBR',
            'Libya' => 'LBA',
            'Liechtenstein' => 'LIE',
            'Lithuania' => 'LTU',
            'Luxembourg' => 'LUX',
            'Macedonia' => 'MKD',
            'Malawi' => 'MAW',
            'Malaysia' => 'MAS',
            'Mali' => 'MLI',
            'Malta' => 'MLT',
            'Mauritania' => 'MTN',
            'Mauritius' => 'MRI',
            'Mexico' => 'MEX',
            'Moldova' => 'MDA',
            'Monaco' => 'MON',
            'Mongolia' => 'MGL',
            'Montenegro' => 'MNE',
            'Morocco' => 'MAR',
            'Mozambique' => 'MOZ',
            'Myanmar' => 'MYA',
            'Namibia' => 'NAM',
            'Nauru' => 'NRU',
            'Nepal' => 'NEP',
            'Netherlands' => 'NED',
            'New Zealand' => 'NZL',
            'Nicaragua' => 'NCA',
            'Niger' => 'NIG',
            'Nigeria' => 'NGR',
            'Norway' => 'NOR',
            'Oman' => 'OMA',
            'Other Countries' => 'OTH',
            'Pakistan' => 'PAK',
            'Palau' => 'PLW',
            'Palestine' => 'PLE',
            'Panama' => 'PAN',
            'Paraguay' => 'PAR',
            'Peru' => 'PER',
            'Philippines' => 'PHI',
            'Poland' => 'POL',
            'Portugal' => 'POR',
            'Puerto Rico' => 'PUR',
            'Qatar' => 'QAT',
            'Romania' => 'ROU',
            'Russia' => 'RUS',
            'Rwanda' => 'RWA',
            'Samoa' => 'SAM',
            'San Marino' => 'SMR',
            'Saudi Arabia' => 'KSA',
            'Senegal' => 'SEN',
            'Serbia' => 'SRB',
            'Sierra Leone' => 'SLE',
            'Singapore' => 'SIN',
            'Slovakia' => 'SVK',
            'Slovenia' => 'SLO',
            'Somalia' => 'SOM',
            'South Africa' => 'RSA',
            'Spain' => 'ESP',
            'Sri Lanka' => 'SRI',
            'Sudan' => 'SUD',
            'Suriname' => 'SUR',
            'Swaziland' => 'SWZ',
            'Sweden' => 'SWE',
            'Switzerland' => 'SUI',
            'Syria' => 'SYR',
            'Taiwan' => 'TWN',
            'Tajikistan' => 'TJK',
            'Tanzania' => 'TAN',
            'Thailand' => 'THA',
            'Togo' => 'TOG',
            'Tonga' => 'TGA',
            'Trinidad and Tobago' => 'TRI',
            'Tunisia' => 'TUN',
            'Turkey' => 'TUR',
            'Turkmenistan' => 'TKM',
            'Tuvalu' => 'TUV',
            'Uganda' => 'UGA',
            'Ukraine' => 'UKR',
            'United Arab Emirates' => 'UAE',
            'United Kingdom' => 'GBR',
            'United States of America' => 'USA',
            'Uruguay' => 'URU',
            'Uzbekistan' => 'UZB',
            'Vanuatu' => 'VAN',
            'Venezuela' => 'VEN',
            'Vietnam' => 'VIE',
            'Yemen' => 'YEM',
            'Zambia' => 'ZAM',
            'Zimbabwe' => 'ZIM',
        ];

        $parts = explode('|', $country);

        if (!isset($parts[1]) || trim($parts[1]) === '') {
            return 'OTH';
        }

        $countryPart = trim($parts[1]);

        if (!\array_key_exists($countryPart, $nations)) {
            return 'OTH';
        }

        return $nations[$countryPart];
    }

    /**
     * Format int time into readable time string
     *
     * @param integer $time
     */
    public static function getFormattedTime(int $time): string
    {
        if ($time === -1) {
            return '???';
        }

        $minutes = intdiv($time, 60000);
        $seconds = intdiv($time % 60000, 1000);
        $centis  = intdiv($time % 1000, 10);

        return \sprintf('%02d:%02d.%02d', $minutes, $seconds, $centis);
    }

    /**
     * Readable time difrence between current time and best time
     *
     * @param integer $time
     * @param integer $bestTime
     */
    public static function getDifference(int $time, int $bestTime): string
    {
        $diff = $time - $bestTime;
        if ($diff <= 0) {
            return '0';
        }

        $seconds = intdiv($diff, 1000);
        $centis  = intdiv($diff % 1000, 10);

        return \sprintf('+%d.%02d', $seconds, $centis);
    }

    /**
     * Strips trackmania colors from strings
     *
     * @param boolean $for_tm
     */
    public static function stripColors(string $input, bool $for_tm = true): string
    {
        // Replace all occurrences of double dollar signs with a null character
        $input = str_replace('$$', "\0", $input);

        // Strip TMF H, L, & P links, keeping the first and second capture groups if present
        $input = preg_replace(
            '/
            # Match and strip H, L, and P links with square brackets
            \$[hlp]         # Match a $ followed by h, l, or p (link markers)
            (.*?)           # Non-greedy capture of any content after the link marker
            (?:             # Start non-capturing group for possible brackets content
                \[.*?\]     # Match any content inside square brackets
                (.*?)       # Non-greedy capture of any content after the square brackets
            )*              # Zero or more occurrences of the bracketed content
            (?:\$[hlp]|$)   # Match another $ with h, l, p or end of string
            /ixu',
            '$1$2',  // Replace with the content of the first and second capture groups
            $input,
        );

        // Strip various patterns beginning with an unescaped dollar sign
        $input = preg_replace(
            '/
            # Match a single unescaped dollar sign and one of the following:
            \$
            (?:
                [0-9a-f][^$][^$]  # Match color codes: hexadecimal + 2 more chars
                | [0-9a-f][^$]    # Match incomplete color codes
                | [^][hlp]        # Match any style code that isn’t H, L, or P
                | (?=[][])        # Match $ followed by [ or ], but keep the brackets
                | $               # Match $ at the end of the string
            )
            /ixu',
            '',  // Remove the dollar sign and matched sequence
            $input,
        );

        // Restore null characters to dollar signs if needed for displaying in TM or logs
        return str_replace("\0", $for_tm ? '$$' : '$', $input);
    }

    /**
     * Levenshtein distance needs iconv extension to be enabled
     *
     * @return integer
     */
    public static function safeLevenshtein(string $a, string $b): int
    {
        $a = mb_strtolower($a, 'UTF-8');
        $b = mb_strtolower($b, 'UTF-8');
        $aConv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $a);
        $bConv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $b);

        if ($aConv !== false && $bConv !== false) {
            return levenshtein($aConv, $bConv);
        }

        return self::mb_levenshtein($a, $b);
    }

    /**
     * Multibyte-safe Levenshtein distance
     */
    private static function mb_levenshtein(string $str1, string $str2): int
    {
        $str1 = preg_split('//u', $str1, -1, PREG_SPLIT_NO_EMPTY);
        $str2 = preg_split('//u', $str2, -1, PREG_SPLIT_NO_EMPTY);
        $len1 = \count($str1);
        $len2 = \count($str2);

        $matrix = [];

        for ($i = 0; $i <= $len1; $i++) {
            $matrix[$i][0] = $i;
        }

        for ($j = 0; $j <= $len2; $j++) {
            $matrix[0][$j] = $j;
        }

        for ($i = 1; $i <= $len1; $i++) {
            for ($j = 1; $j <= $len2; $j++) {
                $cost = ($str1[$i - 1] === $str2[$j - 1]) ? 0 : 1;
                $matrix[$i][$j] = min(
                    $matrix[$i - 1][$j] + 1,        // deletion
                    $matrix[$i][$j - 1] + 1,        // insertion
                    $matrix[$i - 1][$j - 1] + $cost, // substitution
                );
            }
        }

        return $matrix[$len1][$len2];
    }
}
