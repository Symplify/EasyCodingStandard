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
namespace PhpCsFixer\Fixer\Casing;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
/**
 * @author SpacePossum
 */
final class NativeFunctionCasingFixer extends \PhpCsFixer\AbstractFixer
{
    /**
     * {@inheritdoc}
     * @return \PhpCsFixer\FixerDefinition\FixerDefinitionInterface
     */
    public function getDefinition()
    {
        return new \PhpCsFixer\FixerDefinition\FixerDefinition('Function defined by PHP should be called using the correct casing.', [new \PhpCsFixer\FixerDefinition\CodeSample("<?php\nSTRLEN(\$str);\n")]);
    }
    /**
     * {@inheritdoc}
     *
     * Must run after FunctionToConstantFixer, NoUselessSprintfFixer, PowToExponentiationFixer.
     * @return int
     */
    public function getPriority()
    {
        return 0;
    }
    /**
     * {@inheritdoc}
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @return bool
     */
    public function isCandidate($tokens)
    {
        return $tokens->isTokenKindFound(\T_STRING);
    }
    /**
     * {@inheritdoc}
     * @return void
     * @param \SplFileInfo $file
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     */
    protected function applyFix($file, $tokens)
    {
        static $nativeFunctionNames = null;
        if (null === $nativeFunctionNames) {
            $nativeFunctionNames = $this->getNativeFunctionNames();
        }
        for ($index = 0, $count = $tokens->count(); $index < $count; ++$index) {
            // test if we are at a function all
            if (!$tokens[$index]->isGivenKind(\T_STRING)) {
                continue;
            }
            $next = $tokens->getNextMeaningfulToken($index);
            if (!$tokens[$next]->equals('(')) {
                $index = $next;
                continue;
            }
            $functionNamePrefix = $tokens->getPrevMeaningfulToken($index);
            if ($tokens[$functionNamePrefix]->isGivenKind([\T_DOUBLE_COLON, \T_NEW, \T_FUNCTION, \PhpCsFixer\Tokenizer\CT::T_RETURN_REF]) || $tokens[$functionNamePrefix]->isObjectOperator()) {
                continue;
            }
            if ($tokens[$functionNamePrefix]->isGivenKind(\T_NS_SEPARATOR)) {
                // skip if the call is to a constructor or to a function in a namespace other than the default
                $prev = $tokens->getPrevMeaningfulToken($functionNamePrefix);
                if ($tokens[$prev]->isGivenKind([\T_STRING, \T_NEW])) {
                    continue;
                }
            }
            // test if the function call is to a native PHP function
            $lower = \strtolower($tokens[$index]->getContent());
            if (!\array_key_exists($lower, $nativeFunctionNames)) {
                continue;
            }
            $tokens[$index] = new \PhpCsFixer\Tokenizer\Token([\T_STRING, $nativeFunctionNames[$lower]]);
            $index = $next;
        }
    }
    /**
     * @return mixed[]
     */
    private function getNativeFunctionNames()
    {
        $allFunctions = \get_defined_functions();
        $functions = [];
        foreach ($allFunctions['internal'] as $function) {
            $functions[\strtolower($function)] = $function;
        }
        return $functions;
    }
}
