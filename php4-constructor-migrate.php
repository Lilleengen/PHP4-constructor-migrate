<?php
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;

require __DIR__ . '/vendor/autoload.php';

ini_set('memory_limit', '-1');

$prettyPrinter = new PrettyPrinter\Standard;
$di = new RecursiveDirectoryIterator('.');

foreach (new RecursiveIteratorIterator($di) as $filename => $file) {
	if (strpos($filename, '.svn') === false && !is_dir($file)) {
		$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);

		$stmts = "";
		try {
			$stmts = $parser->parse(file_get_contents($filename));
		} catch (PhpParser\Error $e) {
			echo 'Parse Error: ' . $e->getMessage() . "\n";
		}

		$matches = 0;
		if (is_array($stmts)) {
			foreach ($stmts as $class) {
				if($class instanceof PhpParser\Node\Stmt\Class_) {
					$hasConstruct = false;
					foreach ($class->stmts as $function) {
						if($function instanceof PhpParser\Node\Stmt\ClassMethod) {
							if ($class->name === '__construct') {
								$hasConstruct = true;
							}
						}
					}
					if(!$hasConstruct) {
						foreach ($class->stmts as $function) {
							if($function instanceof PhpParser\Node\Stmt\ClassMethod) {
								if (strtolower($class->name) === strtolower($function->name)) {
									$newFunction = clone $function;
									$newFunction->name = '__construct';

									$class->stmts[] = $newFunction;

									$matches++;
								}
							}
						}
					}
				}
			}
		}

		if ($matches > 0) {
			file_put_contents($filename, $prettyPrinter->prettyPrintFile($stmts));
			echo "=== Updated " . $matches . " constructors in " . $filename . ".\n";
		}
	}
}
