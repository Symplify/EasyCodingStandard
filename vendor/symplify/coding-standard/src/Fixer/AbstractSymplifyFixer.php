<?php

declare (strict_types=1);
namespace Symplify\CodingStandard\Fixer;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;
abstract class AbstractSymplifyFixer implements \PhpCsFixer\Fixer\FixerInterface
{
    public function getPriority() : int
    {
        return 0;
    }
    public function getName() : string
    {
        return self::class;
    }
    public function isRisky() : bool
    {
        return \false;
    }
    public function supports(\SplFileInfo $file) : bool
    {
        return \true;
    }
    /**
     * @return Token[]
     * @param Tokens<Token> $tokens
     */
    protected function reverseTokens(\PhpCsFixer\Tokenizer\Tokens $tokens) : array
    {
        return \array_reverse($tokens->toArray(), \true);
    }
    /**
     * @param Tokens<Token> $tokens
     * @return \PhpCsFixer\Tokenizer\Token|null
     */
    protected function getNextMeaningfulToken(\PhpCsFixer\Tokenizer\Tokens $tokens, int $index)
    {
        $nextMeaninfulTokenPosition = $tokens->getNextMeaningfulToken($index);
        if ($nextMeaninfulTokenPosition === null) {
            return null;
        }
        return $tokens[$nextMeaninfulTokenPosition];
    }
    /**
     * @param Tokens<Token> $tokens
     * @return \PhpCsFixer\Tokenizer\Token|null
     */
    protected function getPreviousToken(\PhpCsFixer\Tokenizer\Tokens $tokens, int $index)
    {
        $previousIndex = $index - 1;
        if (!isset($tokens[$previousIndex])) {
            return null;
        }
        return $tokens[$previousIndex];
    }
}
