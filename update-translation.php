<?php
class UpgradeTranslation
{

	const SRC_LNG = 'mk-mk'; // source of old translation
	const ORG_LNG = 'en-gb'; // orignal english translation
	const OUT_LNG = 'xx-yy'; // copy of original english translation to be updated with translation from the source folder
	private string $sourceFolder;
	private string $originFolder;

	private string $outputFolder;

	private $missingFiles = [];
	public function __construct()
	{
		$this->sourceFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, self::SRC_LNG]);
		$this->originFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, self::ORG_LNG]);
		$this->outputFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, self::OUT_LNG]);
	}

	public function run()
	{

		$it = new RecursiveDirectoryIterator($this->originFolder);

		foreach (new RecursiveIteratorIterator($it) as $file) {
			if ($file->isFile()) {
				$this->update($file);
			}
		}

		file_put_contents('missing.txt', implode(PHP_EOL, $this->missingFiles));
	}

	private function update($file)
	{

		$srcFile = str_replace(self::ORG_LNG, self::SRC_LNG, $file);
		$outFile = str_replace(self::ORG_LNG, self::OUT_LNG, $file);
		if (is_file($srcFile)) {
			$originVars = $this->loadVariables($file);
			$sourceVars = $this->loadVariables($srcFile);

			$newVars = array_merge($originVars, $sourceVars);
			$this->replaceFile($newVars, $outFile, $this->getLonegestKey($newVars));
		} else {
			$this->missingFiles[] = $srcFile;
			out::error('No File: ' . $srcFile);
		}
	}

	public function replaceFile($newVars, $outFile, $padding)
	{
		$buffer = '';

		$handle = fopen($outFile, "r");
		if ($handle) {
			while (($line = fgets($handle)) !== false) {
				if (preg_match("/\[\'([^\]]*)\'\]/sui", $line, $matches)) {
					if (array_key_exists($matches[1], $newVars)) {

						$keyString = sprintf("\$_['%s']", $matches[1]);
						$keyString = $keyString . str_repeat(' ', $padding - strlen($keyString));

						$buffer = $buffer .
							sprintf(
								"%s = '%s';",
								$keyString,
								$newVars[$matches[1]]

							) .
							PHP_EOL;
					}
				} else {
					$buffer = $buffer . $line;
				}
			}
			if (substr($buffer, -1) == PHP_EOL) {
				$buffer = substr($buffer, 0, -1);
			}
			file_put_contents($outFile, $buffer);
			fclose($handle);
		}
	}

	public function getLonegestKey($array)
	{
		return max(array_map('strlen', array_keys($array))) + 6;
	}
	private function loadVariables($file)
	{
		$_ = [];
		include $file;
		return $_;
	}
}

class out
{
	public static function info($text)
	{
		echo "\e[0;32;40m$text\e[0m\n";
	}

	public static function error($text)
	{
		echo "\e[0;31;40m$text\e[0m\n";
	}
}


(new UpgradeTranslation())->run();
