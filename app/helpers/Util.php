<?php

namespace app\helpers;

use yii\helpers\ArrayHelper;

class Util
{
	public static function getRndStr(int $len, string $type = 'letnum', bool $lowerUpper = true): string
	{
		$letters = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
		$numbers = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
		$symbols = array('-', '_');

		$return_str = '';
		for ($i = 0; $i < $len; $i++) {
			$source_arr = self::getSourceForRnd($type);

			$source_arr = $$source_arr;

			$symbol = $source_arr[rand(0, (sizeof($source_arr) - 1))];

			if ($lowerUpper) {
				$upper = rand(0, 1);

				if ($upper) {
					$symbol = strtoupper($symbol);
				}
			}

			$return_str .= $symbol;
		}

		return $return_str;
	}

	public static function getSourceForRnd(string $type): string
	{
		switch ($type) {
			case 'common':
				switch (rand(0, 2)) {
					case 0:
						return 'letters';
					case 1:
						return 'numbers';
					case 2:
						return 'symbols';
				}
				break;
			case 'letnum':
				switch (rand(0, 1)) {
					case 0:
						return 'letters';
					case 1:
						return 'numbers';
				}
				break;
		}

		return $type;
	}

	public static function sqlAggArr2Objects(array|null $value, $defaultValue = []): array
	{
		if (!$value) {
			return $defaultValue;
		}

		$out = [];
		$keys = array_keys($value);
		foreach ($keys as $key) {
			foreach ($value[$key] as $rowIndex => $rowValue) {
				$out[$rowIndex][$key] = $rowValue;
			}
		}

		return $out;
	}
}
