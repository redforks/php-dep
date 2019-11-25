<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\String\ByteString;

function get_composer_file(string $module): string {
  if ($module == '.') {
    return 'composer.json';
  }

  $r = './vendor/' . $module . '/composer.json';
  if (file_exists($r)) {
    return $r;
  }

  $r = './app/code/' . $module . '/composer.json';
  if (file_exists($r)) {
    return $r;
  }
  throw new Exception("No module file for $module");
}

const ignored_modules = ['php', 'ext-curl', 'ext-dom', 'ext-gd',
  'ext-hash', 'ext-iconv', 'ext-openssl', 'ext-simplexml', 'ext-spl',
  'ext-xsl', 'ext-bcmath', 'PHP', 'magento/product-community-edition'];

function get_depends(string $module_name): array {
  $s = file_get_contents(get_composer_file($module_name));
  $rec = json_decode($s, true);
  if (!array_key_exists('require', $rec)) {
    return [];
  }

  $r = array_keys($rec['require']);
  $r = array_diff($r, ignored_modules);
  $r = array_filter($r, function($v) {
    $s = new ByteString($v);
    return !($s->startsWith('ext-')) && !($s->startsWith('lib-'));
  });
  return $r;
}

function is_module_dumped(string $module_name): bool {
  static $dumpd = [];
  if (in_array($module_name, $dumpd)) {
    return true;
  }

  $dumpd[] = $module_name;
  return false;
}

function dump_depends(string $module_name, $output, bool $reverse): void {
  if (is_module_dumped($module_name)) {
    return;
  }

  $requires = get_depends($module_name);

  foreach ($requires as $dep_module) {
    fwrite($output, "\t");
    fwrite($output, $reverse 
      ? "\"$dep_module\" -> \"$module_name\";\n"
      : "\"$module_name\" -> \"$dep_module\";\n");
    dump_depends($dep_module, $output, $reverse);
  }
}

function dump(bool $reverse): string {
  $output = fopen('php://memory', 'r+');
  fwrite($output, "digraph {\n");
  dump_depends('.', $output, $reverse);
  fwrite($output, "}\n");
  rewind($output);
  return stream_get_contents($output);
}

