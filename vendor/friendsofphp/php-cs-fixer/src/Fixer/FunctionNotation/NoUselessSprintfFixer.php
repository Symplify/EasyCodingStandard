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
namespace PhpCsFixer\Fixer\FunctionNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;
final class NoUselessSprintfFixer extends \PhpCsFixer\AbstractFixer
{
    /**
     * {@inheritdoc}
     * @return \PhpCsFixer\FixerDefinition\FixerDefinitionInterface
     */
    public function getDefinition()
    {
        return new \PhpCsFixer\FixerDefinition\FixerDefinition('There must be no `sprintf` calls with only the first argument.', [new \PhpCsFixer\FixerDefinition\CodeSample("<?php\n\$foo = sprintf('bar');\n")], null, 'Risky when if the `sprintf` function is overridden.');
    }
    /**
     * {@inheritdoc}
     * @return bool
     */
    public function isCandidate(\PhpCsFixer\Tokenizer\Tokens $tokens)
    {
        return $tokens->isTokenKindFound(\T_STRING);
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
     *
     * Must run before MethodArgumentSpaceFixer, NativeFunctionCasingFixer, NoEmptyStatementFixer, NoExtraBlankLinesFixer, NoSpacesInsideParenthesisFixer.
     * @return int
     */
    public function getPriority()
    {
        return 42;
    }
    /**
     * {@inheritdoc}
     * @return void
     */
    protected function applyFix(\SplFileInfo $file, \PhpCsFixer\Tokenizer\Tokens $tokens)
    {
        $functionAnalyzer = new \PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer();
        $argumentsAnalyzer = new \PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer();
        for ($index = \count($tokens) - 1; $index > 0; --$index) {
            if (!$tokens[$index]->isGivenKind(\T_STRING)) {
                continue;
            }
            if ('sprintf' !== \strtolower($tokens[$index]->getContent())) {
                continue;
            }
            if (!$functionAnalyzer->isGlobalFunctionCall($tokens, $index)) {
                continue;
            }
            $openParenthesisIndex = $tokens->getNextTokenOfKind($index, ['(']);
            if ($tokens[$tokens->getNextMeaningfulToken($openParenthesisIndex)]->isGivenKind(\T_ELLIPSIS)) {
                continue;
            }
            $closeParenthesisIndex = $tokens->findBlockEnd(\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesisIndex);
            if (1 !== $argumentsAnalyzer->countArguments($tokens, $openParenthesisIndex, $closeParenthesisIndex)) {
                continue;
            }
            $tokens->clearTokenAndMergeSurroundingWhitespace($closeParenthesisIndex);
            $prevMeaningfulTokenIndex = $tokens->getPrevMeaningfulToken($closeParenthesisIndex);
            if ($tokens[$prevMeaningfulTokenIndex]->equals(',')) {
                $tokens->clearTokenAndMergeSurroundingWhitespace($prevMeaningfulTokenIndex);
            }
            $tokens->clearTokenAndMergeSurroundingWhitespace($openParenthesisIndex);
            $tokens->clearTokenAndMergeSurroundingWhitespace($index);
            $prevMeaningfulTokenIndex = $tokens->getPrevMeaningfulToken($index);
            if ($tokens[$prevMeaningfulTokenIndex]->isGivenKind(\T_NS_SEPARATOR)) {
                $tokens->clearTokenAndMergeSurroundingWhitespace($prevMeaningfulTokenIndex);
            }
        }
    }
}