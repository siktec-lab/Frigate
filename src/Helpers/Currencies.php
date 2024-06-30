<?php

declare(strict_types=1);

namespace Frigate\Helpers;

use NumberFormatter;

class Currencies 
{
    public const SYMBOLS = [
        'USD' => '$',  // US Dollar
    	'EUR' => '€',  // Euro
    	'GBP' => '£',  // British Pound Sterling
        'AUD' => '$',  // Australian Dollar
        'CAD' => '$',  // Canadian Dollar
        'CHF' => 'Fr.',  // Swiss Franc
    	'ILS' => '₪',  // Israeli New Sheqel
	    'INR' => '₹',  // Indian Rupee
	    'JPY' => '¥',  // Japanese Yen
        'CNY' => '¥',  // Chinese Yuan   
        'HKD' => '$',  // Hong Kong Dollar
        'SGD' => '$',  // Singapore Dollar
        'NZD' => '$',  // New Zealand Dollar
        'SEK' => 'kr',  // Swedish Krona
        'NOK' => 'kr',  // Norwegian Krone
	    'KRW' => '₩',  // South Korean Won
        'TRY' => '₺',  // Turkish Lira
    	'CRC' => '₡',  // Costa Rican Colón
        'MXN' => '$',  // Mexican Peso
        'RUB' => '₽',  // Russian Ruble
	    'NGN' => '₦',  // Nigerian Naira
	    'PHP' => '₱',  // Philippine Peso
	    'PLN' => 'zł', // Polish Zloty
	    'PYG' => '₲',  // Paraguayan Guarani
	    'THB' => '฿',  // Thai Baht
	    'UAH' => '₴',  // Ukrainian Hryvnia
	    'VND' => '₫',  // Vietnamese Dong)
        'BTC' => '₿',  // Bitcoin
        'ETH' => 'Ξ',  // Ethereum
        'USDT'=> '₮',  // Tether
    ];
    
    /**
     * checks if a currency has a symbol
     */
    public static function has_symbol(string $cur_iso) : bool
    {
        return array_key_exists(strtoupper($cur_iso), self::SYMBOLS);
    }

    /**
     * gets the currency symbol
     */
    public static function get_symbol(string $cur_iso) : string
    {
        $cur_iso = strtoupper($cur_iso);
        return self::has_symbol($cur_iso) ? self::SYMBOLS[$cur_iso] : $cur_iso;
	}

    /**
     * formats a currency value to local currency
     */
    public static function format(float $value, string $cur_iso = "USD", string $locale = "en_US") : string
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($value, strtoupper($cur_iso));
    }
}