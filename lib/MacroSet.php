<?php
declare(strict_types=1);

namespace PhpYacc;

use PhpYacc\Grammar\Context;
use ArrayIterator;
use Traversable;
use CachingIterator;

class MacroSet {

    protected $macros = [];

    public function __construct(Macro ...$macros)
    {
        $this->addMacro(...$macros);
    }

    public function addMacro(Macro ...$macros)
    {
        foreach ($macros as $macro) {
            $this->macros[] = $macro;
        }
    }

    public function apply(Context $ctx, array $symbols, array $tokens, int $n, array $attribute): array
    {
        $tokens = new ArrayIterator($tokens);
        $macroCount = count($this->macros);
        if ($macroCount === 1) {
            // special case
            return iterator_to_array($this->macros[0]->apply($ctx, $symbols, $tokens, $n, $attribute));
        }
        $next = $this->genId($tokens);
        do {
            $id = $next;
            foreach ($this->macros as $macro) {
                $tokens = $macro->apply($ctx, $symbols, $tokens, $n, $attribute);
            }
            $tokens = self::cache($tokens);
            $next = $this->genId($tokens);
        } while ($id !== $next);
        return iterator_to_array($tokens);
    }

    protected function genId(Traversable $it): string {
        $id = '';
        foreach ($it as $t) {
            $id .= $t->v;
        }
        return $id;
    }

    public static function cache(Traversable $t): Traversable
    {
        return new ArrayIterator(iterator_to_array($t));
    }

}