<?php

namespace app\helpers;

class File
{
	const EXTS_IMG = ['jpg', 'jpeg', 'png', 'gif', 'jfif'];
	const EXTS_VIDEO = ['mp4', 'mov', 'wmv'];
	const EXTS_AUDIO = ['mp3', 'wav', 'm4a', 'aac'];

	public static function getTypeByExt(string $ext): string|null
	{
		$ext = strtolower($ext);

		if (in_array($ext, self::EXTS_IMG)) {
			return 'images';
		} elseif (in_array($ext, self::EXTS_VIDEO)) {
			return 'videos';
		} elseif (in_array($ext, self::EXTS_AUDIO)) {
			return 'audios';
		}

		return null;
	}

	public static function formatSize($size): string
	{
		$sizes = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");

		if ($size == 0) {
			return('n/a');
		} else {
			return (round($size/pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0) . $sizes[$i]);
		}
	}
}
