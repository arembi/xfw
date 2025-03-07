<?php

namespace Arembi\Xfw\Core;

abstract class Language {

	private static $model;
	private static $alphabets;


	public static function init()
	{
		self::$model = new LanguageModel();
		self::$alphabets = [];
		self::$alphabets['en'] = range('a','z');
		self::$alphabets['hu'] = [
			'a', 'á', 'b', 'c', 'cs', 'd', 'dz', 'dzs', 'e', 'é', 'f', 'g',
			'gy', 'h', 'i', 'í', 'j', 'k', 'l', 'ly', 'm', 'n', 'ny', 'o', 'ó', 'ö',
			'ő', 'p', 'q', 'r', 's', 'sz', 't', 'ty', 'u', 'ú', 'ü', 'ű', 'v', 'w',
			'x', 'y', 'z', 'zs'
		];
	}


	public static function getAlphabet(string $langCode)
	{
		return self::$alphabets[$langCode] ?? null;
	}


	// Returns the proper language version of $str from the dictionary
	// If lang is null, the current system language will be used
	public static function _($str, $lang = null)
	{
		// TODO
	}


	// Converts a roman number to integer
	public static function romanToInt(string $roman)
	{
		$roman = strtoupper($roman);

		$numberTable = [
			'M' => 1000,
			'D' => 500,
			'C' => 100,
			'L' => 50,
			'X' => 10,
			'V' => 5,
			'I' => 1
		];

		$l = strlen($roman);

		// Validating letters
		for ($i = 0; $i < $l; $i++) {
			if (!isset($numberTable[$roman[$i]])) {
				return false;
			}
		}

		// The last one can be added automatically
		$sum = $numberTable[$roman[$l - 1]];

		for ($i = 0; $i < $l - 1; $i++) {
			if ($numberTable[$roman[$i]] < $numberTable[$roman[$i + 1]]) {
				$sum -= $numberTable[$roman[$i]];
			} else {
				$sum += $numberTable[$roman[$i]];
			}
		}

		return $sum;
	}


	public static function intToRoman(int $number, bool $uppercase = true)
	{
    	$numberTable = [
			'M' => 1000,
			'CM' => 900,
			'D' => 500,
			'CD' => 400,
			'C' => 100,
			'XC' => 90,
			'L' => 50,
			'XL' => 40,
			'X' => 10,
			'IX' => 9,
			'V' => 5,
			'IV' => 4,
			'I' => 1
		];

    	$romanNumber = '';

		while ($number > 0) {
			foreach ($numberTable as $roman => $arabic) {
				if ($number >= $arabic) {
					$number -= $arabic;
					$romanNumber .= $roman;
					break;
				}
			}
		}

		return $uppercase ? $romanNumber : strtolower($romanNumber);
	}
}
