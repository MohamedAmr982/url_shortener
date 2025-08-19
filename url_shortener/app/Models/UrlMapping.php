<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

class UrlMapping extends Model
{
    /** @use HasFactory<\Database\Factories\UrlMappingFactory> */
    use HasFactory;

    protected $guarded = [];
    protected $primaryKey = "short_url";
    protected $keyType = "string";
    public $incrementing = false;

    private const SHORT_URL_KEY = "short_url";
    private const STATUS_KEY = "status";

    // 256 bits = log_62(2^256) = log_2(2^256)/log_2(62) = 256/log_2(62) ~= 43 base-62 digits
    // just take the last 6/7 digits
    // 6 base-62 digits ~= 36 bits ~= 5 bytes
    // 5 bytes = 40 bits = 40/log_2(62) ~= 7 base-62 digits
    // take the last 5 bytes

    // using base 62 encoding
    // 62^6 = 56 B = 56*10^9
    // 62^7 = 3 T = 3*10^12

    private static function generate_random_bytes($n_bytes) {
        $seed = rand();
        // hashes the random number and returns an array of 256 / 8 = 32 bytes
        $digest = unpack("C*", hash("sha256", $seed, true));
        // returns only the first $n_bytes
        return array_slice($digest, 0, $n_bytes);
    }

    private static function bytes_to_number($bytes) {
        // overflows for $bytes > PHP_INT_SIZE
        $result = 0;
        // big endian
        foreach ($bytes as $byte) {
            $result <<= 8;
            $result |= $byte;
        }
        return $result;
    }
    
    private static function to_base_62(int $n) {
        if ($n === 0) { return "0"; }
        $encoding = "";
        while ($n > 0) {
            $rem = $n % 62;
            if ($rem >= 0 && $rem <= 9) {
                $encoding .= chr($rem + ord("0"));
            } elseif ($rem >= 10 && $rem <= 35) {
                $encoding .= chr($rem - 10 + ord("A"));
            } else {
                $encoding .= chr($rem - 36 + ord("a"));
            }
            $n = intdiv($n, 62);
        }
        return strrev($encoding);
    }

    private static function generate_short_url() {
        // must have PHP_INT_SIZE > 4 !!!!!
        $bytes = static::generate_random_bytes(5);
        return static::to_base_62(static::bytes_to_number($bytes));
    }

    public static function add_new($long_url) {

        /* 
            Returns an array with keys "short_url", "status"
            short_url: returned after being inserted with the long_url
                in the database.
            status: indicates which conditions were triggered for
                debugging and testing purposes
                it's used like a bit array:
                
                starting from the least significant bit:
                bit_0 = 1: another concurrent tx inserted the long_url
                bit_1 = 1: collision occurred (generated an already used short_url)

                status = 0 means that $long_url already exists in the database,
                    no insertions were performed

            
            The function only catches UniqueConstraintViolationException.
            Any other exceptions will be thrown.
        */

        // check if long_url exists in db
        $stored_url_mapping = static::where("long_url", $long_url)->get();
        if (!$stored_url_mapping->isEmpty()) {
            dump("already exists!!");
            // if exists in db: return the short url
            return [
                static::SHORT_URL_KEY => $stored_url_mapping[0]->short_url,
                static::STATUS_KEY => 0
            ];
        }
        // otherwise: generate short url, store in db, return the short url
        $status = 0;
        while (true) {
            // generate a new short url
            $short_url = static::generate_short_url();
            // try to insert
            try {
                static::create([
                    "short_url" => $short_url,
                    "long_url" => $long_url,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // if a unique constraint violation occurred, then
                // either the long_url or the short_url already
                // exists in the database
                
                $long_url_entry = static::where("long_url", $long_url)->get();
                if (!$long_url_entry->isEmpty()) {
                    // long_url was already inserted by a concurrent tx
                    // the violation is caused by re-inserting the same
                    // long_url
                    $status |= 1;
                    return [
                        static::SHORT_URL_KEY => $long_url_entry[0]->short_url,
                        static::STATUS_KEY => $status
                    ];
                }

                // otherwise the violation was due to re-inserting the same
                // short_url that already corresponds to another long_url 
                // (collision) --> retry
                // (this is of low probability - and retrying twice is even less likely)
                $status |= 2;
                continue;
            }
            // any other exception types should be handled by the caller

            // no exceptions were raised --> exit the loop
            break;
        }
        $status |= 4;
        return [
            static::SHORT_URL_KEY => $short_url,
            static::STATUS_KEY => $status
        ];
    }

}
