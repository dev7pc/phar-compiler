<?php //declare(strict_types = 1);

namespace SevenPercent;

use FilesystemIterator;
use Phar;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PharCompiler {

	public static function compile(/*string */$executable, /*string */$buildDirectory, array $directories) {
		$basename = basename($executable);
		$phar = new Phar("$buildDirectory/$basename.phar", 0, "$basename.phar");
		$phar->setSignatureAlgorithm(Phar::SHA1);
		$phar->startBuffering();
		foreach ($directories as $directory) {
			$pathOffset = strlen(dirname(realpath($directory))) + 1;
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
				if ($file->getExtension() === 'php' && ($fileRealPath = $file->getRealPath()) !== __FILE__) {
					$phar->addFile($fileRealPath, substr($fileRealPath, $pathOffset));
				}
			}
		}
		$phar->addFromString("bin/$basename", preg_replace('{^#!/usr/bin/env php\s*}', '', file_get_contents($executable)));
		$phar->setStub("#!/usr/bin/env php\n<?php Phar::mapPhar('$basename.phar');require'phar://$basename.phar/bin/$basename';__HALT_COMPILER();");
		$phar->stopBuffering();
		rename("$buildDirectory/$basename.phar", "$buildDirectory/$basename");
		chmod("$buildDirectory/$basename", 0755);
	}
}
