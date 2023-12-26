<?php

namespace app\helpers;

class RandomFilePath
{
	protected $_path;

	protected $_localPath = '';

	protected $_randomStr;

	protected $_level = 2;

	protected $_accessRights = 0777;

	protected $_ext;

	protected $_maxLength;

	public function __construct($path, $ext = null, $level = null, $maxLength = null)
	{
		$this->setPath($path);

		if (!is_null($ext)) {
			$this->setExt($ext);
		}

		if (!is_null($level)) {
			$this->setLevel($level);
		}

		if (!is_null($maxLength)) {
			$this->setMaxLength($maxLength);
		}
	}

	public function obtainPath()
	{
		while (true) {
			$this->setupRandomStr();

			if ($this->initFolders()) {
				break;
			}
		}

		return $this->_path . DIRECTORY_SEPARATOR . $this->_localPath . '.' . $this->_ext;
	}

	/**
	 * @return string
	 */
	public function getLocalPath()
	{
		return $this->_localPath;
	}

	protected function initFolders()
	{
		for ($i = 0; $i < $this->_level; $i++) {
			$dirName = substr($this->_randomStr, 0, 2);
			$this->_randomStr = substr($this->_randomStr, 2);

			if ($this->_localPath != '') {
				$this->_localPath .= DIRECTORY_SEPARATOR;
			}

			$this->_localPath .= $dirName;
		}

		if (file_exists($this->_localPath . DIRECTORY_SEPARATOR . $this->_randomStr . '.' . $this->_ext)) {
			return false;
		} else {
			if (!is_dir($this->_path . DIRECTORY_SEPARATOR . $this->_localPath)) {
				$pathForCreating = $this->_path . DIRECTORY_SEPARATOR . $this->_localPath;
				$result = mkdir($pathForCreating, $this->_accessRights, true);

				if (!$result) {
					throw new Exception('Cannot create directory "' . $pathForCreating . '".');
				}

				$this->affectAccessRights($this->_localPath);
			}

			$this->_localPath .= DIRECTORY_SEPARATOR . $this->_randomStr;

			return true;
		}
	}

	protected function affectAccessRights($localPath)
	{
		$parts = explode(DIRECTORY_SEPARATOR, $localPath);

		$subPath = $this->_path . DIRECTORY_SEPARATOR;
		foreach ($parts as $dir) {
			$subPath .= $dir;
			@chmod($subPath, $this->_accessRights);

			$subPath .= DIRECTORY_SEPARATOR;
		}
	}

	protected function setupRandomStr()
	{
		$this->_randomStr = md5(rand());

		if ($this->_maxLength && strlen($this->_randomStr) > $this->_maxLength) {
			$this->_randomStr = substr($this->_randomStr, 0, $this->_maxLength);
		}
	}

	public function setPath($path)
	{
		$this->_path = $path;

		return $this;
	}

	public function getPath()
	{
		return $this->_path;
	}

	public function setLevel($level)
	{
		$this->_level = $level;

		return $this;
	}

	public function getLevel()
	{
		return $this->_level;
	}

	public function setExt($ext)
	{
		$this->_ext = $ext;

		return $this;
	}

	public function setMaxLength($length)
	{
		$this->_maxLength = intval($length);

		return $this;
	}

	public function getExt()
	{
		return $this->_ext;
	}

	public function setAccessRights($mode)
	{
		$this->_accessRights = $mode;

		return $this;
	}
}
