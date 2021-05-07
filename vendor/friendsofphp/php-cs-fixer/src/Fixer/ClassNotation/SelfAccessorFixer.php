<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace PhpCsFixer\Fixer\ClassNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
/**
 * @author Gregor Harlan <gharlan@web.de>
 */
final class SelfAccessorFixer extends \PhpCsFixer\AbstractFixer
{
    /**
     * {@inheritdoc}
     * @return \PhpCsFixer\FixerDefinition\FixerDefinitionInterface
     */
    public function getDefinition()
    {
        return new \PhpCsFixer\FixerDefinition\FixerDefinition('Inside class or interface element `self` should be preferred to the class name itself.', [new \PhpCsFixer\FixerDefinition\CodeSample('<?php
class Sample
{
    const BAZ = 1;
    const BAR = Sample::BAZ;

    public function getBar()
    {
        return Sample::BAR;
    }
}
')], null, 'Risky when using dynamic calls like get_called_class() or late static binding.');
    }
    /**
     * {@inheritdoc}
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @return bool
     */
    public function isCandidate($tokens)
    {
        return $tokens->isAnyTokenKindsFound([\T_CLASS, \T_INTERFACE]);
    }
    /**
     * {@inheritdoc}
     * @return bool
     */
    public function isRisky()
    {
        return \true;
    }
    /**
     * {@inheritdoc}
     * @return void
     * @param \SplFileInfo $file
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     */
    protected function applyFix($file, $tokens)
    {
        $tokensAnalyzer = new \PhpCsFixer\Tokenizer\TokensAnalyzer($tokens);
        foreach ((new \PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer())->getDeclarations($tokens) as $namespace) {
            for ($index = $namespace->getScopeStartIndex(); $index < $namespace->getScopeEndIndex(); ++$index) {
                if (!$tokens[$index]->isGivenKind([\T_CLASS, \T_INTERFACE]) || $tokensAnalyzer->isAnonymousClass($index)) {
                    continue;
                }
                $nameIndex = $tokens->getNextTokenOfKind($index, [[\T_STRING]]);
                $startIndex = $tokens->getNextTokenOfKind($nameIndex, ['{']);
                $endIndex = $tokens->findBlockEnd(\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_CURLY_BRACE, $startIndex);
                $name = $tokens[$nameIndex]->getContent();
                $this->replaceNameOccurrences($tokens, $namespace->getFullName(), $name, $startIndex, $endIndex);
                $index = $endIndex;
            }
        }
    }
    /**
     * Replace occurrences of the name of the classy element by "self" (if possible).
     * @return void
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @param string $namespace
     * @param string $name
     * @param int $startIndex
     * @param int $endIndex
     */
    private function replaceNameOccurrences($tokens, $namespace, $name, $startIndex, $endIndex)
    {
        $tokensAnalyzer = new \PhpCsFixer\Tokenizer\TokensAnalyzer($tokens);
        $insideMethodSignatureUntil = null;
        for ($i = $startIndex; $i < $endIndex; ++$i) {
            if ($i === $insideMethodSignatureUntil) {
                $insideMethodSignatureUntil = null;
            }
            $token = $tokens[$i];
            // skip anonymous classes
            if ($token->isGivenKind(\T_CLASS) && $tokensAnalyzer->isAnonymousClass($i)) {
                $i = $tokens->getNextTokenOfKind($i, ['{']);
                $i = $tokens->findBlockEnd(\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_CURLY_BRACE, $i);
                continue;
            }
            if ($token->isGivenKind(\T_FUNCTION)) {
                $i = $tokens->getNextTokenOfKind($i, ['(']);
                $insideMethodSignatureUntil = $tokens->getNextTokenOfKind($i, ['{', ';']);
                continue;
            }
            if (!$token->equals([\T_STRING, $name], \false)) {
                continue;
            }
            $nextToken = $tokens[$tokens->getNextMeaningfulToken($i)];
            if ($nextToken->isGivenKind(\T_NS_SEPARATOR)) {
                continue;
            }
            $classStartIndex = $i;
            $prevToken = $tokens[$tokens->getPrevMeaningfulToken($i)];
            if ($prevToken->isGivenKind(\T_NS_SEPARATOR)) {
                $classStartIndex = $this->getClassStart($tokens, $i, $namespace);
                if (null === $classStartIndex) {
                    continue;
                }
                $prevToken = $tokens[$tokens->getPrevMeaningfulToken($classStartIndex)];
            }
            if ($prevToken->isGivenKind(\T_STRING) || $prevToken->isObjectOperator()) {
                continue;
            }
            if ($prevToken->isGivenKind([\T_INSTANCEOF, \T_NEW]) || $nextToken->isGivenKind(\T_PAAMAYIM_NEKUDOTAYIM) || null !== $insideMethodSignatureUntil && $i < $insideMethodSignatureUntil && $prevToken->equalsAny(['(', ',', [\PhpCsFixer\Tokenizer\CT::T_TYPE_COLON], [\PhpCsFixer\Tokenizer\CT::T_NULLABLE_TYPE]])) {
                for ($j = $classStartIndex; $j < $i; ++$j) {
                    $tokens->clearTokenAndMergeSurroundingWhitespace($j);
                }
                $tokens[$i] = new \PhpCsFixer\Tokenizer\Token([\T_STRING, 'self']);
            }
        }
    }
    /**
     * @return int|null
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @param int $index
     * @param string $namespace
     */
    private function getClassStart($tokens, $index, $namespace)
    {
        $namespace = ('' !== $namespace ? '\\' . $namespace : '') . '\\';
        foreach (\array_reverse(\PhpCsFixer\Preg::split('/(\\\\)/', $namespace, -1, \PREG_SPLIT_NO_EMPTY | \PREG_SPLIT_DELIM_CAPTURE)) as $piece) {
            $index = $tokens->getPrevMeaningfulToken($index);
            if ('\\' === $piece) {
                if (!$tokens[$index]->isGivenKind(\T_NS_SEPARATOR)) {
                    return null;
                }
            } elseif (!$tokens[$index]->equals([\T_STRING, $piece], \false)) {
                return null;
            }
        }
        return $index;
    }
}
