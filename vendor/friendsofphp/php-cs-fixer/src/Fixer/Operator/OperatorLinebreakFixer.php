<?php

declare (strict_types=1);
/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace PhpCsFixer\Fixer\Operator;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\CaseAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\GotoLabelAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\ReferenceAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\SwitchAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
/**
 * @author Kuba Werłos <werlos@gmail.com>
 */
final class OperatorLinebreakFixer extends \PhpCsFixer\AbstractFixer implements \PhpCsFixer\Fixer\ConfigurableFixerInterface
{
    const BOOLEAN_OPERATORS = [[\T_BOOLEAN_AND], [\T_BOOLEAN_OR], [\T_LOGICAL_AND], [\T_LOGICAL_OR], [\T_LOGICAL_XOR]];
    /**
     * @var string
     */
    private $position = 'beginning';
    /**
     * @var array<array<int|string>|string>
     */
    private $operators = [];
    /**
     * {@inheritdoc}
     */
    public function getDefinition() : \PhpCsFixer\FixerDefinition\FixerDefinitionInterface
    {
        return new \PhpCsFixer\FixerDefinition\FixerDefinition('Operators - when multiline - must always be at the beginning or at the end of the line.', [new \PhpCsFixer\FixerDefinition\CodeSample('<?php
function foo() {
    return $bar ||
        $baz;
}
'), new \PhpCsFixer\FixerDefinition\CodeSample('<?php
function foo() {
    return $bar
        || $baz;
}
', ['position' => 'end'])]);
    }
    /**
     * {@inheritdoc}
     * @param mixed[] $configuration
     * @return void
     */
    public function configure($configuration)
    {
        parent::configure($configuration);
        $this->operators = self::BOOLEAN_OPERATORS;
        if (!$this->configuration['only_booleans']) {
            $this->operators = \array_merge($this->operators, self::getNonBooleanOperators());
            if (\PHP_VERSION_ID >= 70000) {
                $this->operators[] = [\T_COALESCE];
                $this->operators[] = [\T_SPACESHIP];
            }
        }
        $this->position = $this->configuration['position'];
    }
    /**
     * {@inheritdoc}
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     */
    public function isCandidate($tokens) : bool
    {
        return \true;
    }
    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition() : \PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface
    {
        return new \PhpCsFixer\FixerConfiguration\FixerConfigurationResolver([(new \PhpCsFixer\FixerConfiguration\FixerOptionBuilder('only_booleans', 'whether to limit operators to only boolean ones'))->setAllowedTypes(['bool'])->setDefault(\false)->getOption(), (new \PhpCsFixer\FixerConfiguration\FixerOptionBuilder('position', 'whether to place operators at the beginning or at the end of the line'))->setAllowedValues(['beginning', 'end'])->setDefault($this->position)->getOption()]);
    }
    /**
     * {@inheritdoc}
     * @param \SplFileInfo $file
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @return void
     */
    protected function applyFix($file, $tokens)
    {
        $referenceAnalyzer = new \PhpCsFixer\Tokenizer\Analyzer\ReferenceAnalyzer();
        $gotoLabelAnalyzer = new \PhpCsFixer\Tokenizer\Analyzer\GotoLabelAnalyzer();
        $excludedIndices = $this->getExcludedIndices($tokens);
        $index = $tokens->count();
        while ($index > 1) {
            --$index;
            if (!$tokens[$index]->equalsAny($this->operators, \false)) {
                continue;
            }
            if ($gotoLabelAnalyzer->belongsToGoToLabel($tokens, $index)) {
                continue;
            }
            if ($referenceAnalyzer->isReference($tokens, $index)) {
                continue;
            }
            if (\in_array($index, $excludedIndices, \true)) {
                continue;
            }
            $operatorIndices = [$index];
            if ($tokens[$index]->equals(':')) {
                /** @var int $prevIndex */
                $prevIndex = $tokens->getPrevMeaningfulToken($index);
                if ($tokens[$prevIndex]->equals('?')) {
                    $operatorIndices = [$prevIndex, $index];
                    $index = $prevIndex;
                }
            }
            $this->fixOperatorLinebreak($tokens, $operatorIndices);
        }
    }
    /**
     * Currently only colons from "switch".
     *
     * @return int[]
     */
    private function getExcludedIndices(\PhpCsFixer\Tokenizer\Tokens $tokens) : array
    {
        $indices = [];
        for ($index = $tokens->count() - 1; $index > 0; --$index) {
            if ($tokens[$index]->isGivenKind(\T_SWITCH)) {
                $indices = \array_merge($indices, $this->getCasesColonsForSwitch($tokens, $index));
            }
        }
        return $indices;
    }
    /**
     * @return int[]
     */
    private function getCasesColonsForSwitch(\PhpCsFixer\Tokenizer\Tokens $tokens, int $switchIndex) : array
    {
        return \array_map(static function (\PhpCsFixer\Tokenizer\Analyzer\Analysis\CaseAnalysis $caseAnalysis) {
            return $caseAnalysis->getColonIndex();
        }, (new \PhpCsFixer\Tokenizer\Analyzer\SwitchAnalyzer())->getSwitchAnalysis($tokens, $switchIndex)->getCases());
    }
    /**
     * @param int[] $operatorIndices
     * @return void
     */
    private function fixOperatorLinebreak(\PhpCsFixer\Tokenizer\Tokens $tokens, array $operatorIndices)
    {
        /** @var int $prevIndex */
        $prevIndex = $tokens->getPrevMeaningfulToken(\min($operatorIndices));
        $indexStart = $prevIndex + 1;
        /** @var int $nextIndex */
        $nextIndex = $tokens->getNextMeaningfulToken(\max($operatorIndices));
        $indexEnd = $nextIndex - 1;
        if (!$this->isMultiline($tokens, $indexStart, $indexEnd)) {
            return;
            // operator is not surrounded by multiline whitespaces, do not touch it
        }
        if ('beginning' === $this->position) {
            if (!$this->isMultiline($tokens, \max($operatorIndices), $indexEnd)) {
                return;
                // operator already is placed correctly
            }
            $this->fixMoveToTheBeginning($tokens, $operatorIndices);
            return;
        }
        if (!$this->isMultiline($tokens, $indexStart, \min($operatorIndices))) {
            return;
            // operator already is placed correctly
        }
        $this->fixMoveToTheEnd($tokens, $operatorIndices);
    }
    /**
     * @param int[] $operatorIndices
     * @return void
     */
    private function fixMoveToTheBeginning(\PhpCsFixer\Tokenizer\Tokens $tokens, array $operatorIndices)
    {
        /** @var int $prevIndex */
        $prevIndex = $tokens->getNonEmptySibling(\min($operatorIndices), -1);
        /** @var int $nextIndex */
        $nextIndex = $tokens->getNextMeaningfulToken(\max($operatorIndices));
        for ($i = $nextIndex - 1; $i > \max($operatorIndices); --$i) {
            if ($tokens[$i]->isWhitespace() && 1 === \PhpCsFixer\Preg::match('/\\R/u', $tokens[$i]->getContent())) {
                $isWhitespaceBefore = $tokens[$prevIndex]->isWhitespace();
                $inserts = $this->getReplacementsAndClear($tokens, $operatorIndices, -1);
                if ($isWhitespaceBefore) {
                    $inserts[] = new \PhpCsFixer\Tokenizer\Token([\T_WHITESPACE, ' ']);
                }
                $tokens->insertAt($nextIndex, $inserts);
                break;
            }
        }
    }
    /**
     * @param int[] $operatorIndices
     * @return void
     */
    private function fixMoveToTheEnd(\PhpCsFixer\Tokenizer\Tokens $tokens, array $operatorIndices)
    {
        /** @var int $prevIndex */
        $prevIndex = $tokens->getPrevMeaningfulToken(\min($operatorIndices));
        /** @var int $nextIndex */
        $nextIndex = $tokens->getNonEmptySibling(\max($operatorIndices), 1);
        for ($i = $prevIndex + 1; $i < \max($operatorIndices); ++$i) {
            if ($tokens[$i]->isWhitespace() && 1 === \PhpCsFixer\Preg::match('/\\R/u', $tokens[$i]->getContent())) {
                $isWhitespaceAfter = $tokens[$nextIndex]->isWhitespace();
                $inserts = $this->getReplacementsAndClear($tokens, $operatorIndices, 1);
                if ($isWhitespaceAfter) {
                    \array_unshift($inserts, new \PhpCsFixer\Tokenizer\Token([\T_WHITESPACE, ' ']));
                }
                $tokens->insertAt($prevIndex + 1, $inserts);
                break;
            }
        }
    }
    /**
     * @param int[] $indices
     *
     * @return Token[]
     */
    private function getReplacementsAndClear(\PhpCsFixer\Tokenizer\Tokens $tokens, array $indices, int $direction) : array
    {
        return \array_map(static function (int $index) use($tokens, $direction) {
            $clone = $tokens[$index];
            if ($tokens[$index + $direction]->isWhitespace()) {
                $tokens->clearAt($index + $direction);
            }
            $tokens->clearAt($index);
            return $clone;
        }, $indices);
    }
    private function isMultiline(\PhpCsFixer\Tokenizer\Tokens $tokens, int $indexStart, int $indexEnd) : bool
    {
        for ($index = $indexStart; $index <= $indexEnd; ++$index) {
            if (\false !== \strpos($tokens[$index]->getContent(), "\n")) {
                return \true;
            }
        }
        return \false;
    }
    private static function getNonBooleanOperators()
    {
        return \array_merge(['%', '&', '*', '+', '-', '.', '/', ':', '<', '=', '>', '?', '^', '|', [\T_AND_EQUAL], [\T_CONCAT_EQUAL], [\T_DIV_EQUAL], [\T_DOUBLE_ARROW], [\T_IS_EQUAL], [\T_IS_GREATER_OR_EQUAL], [\T_IS_IDENTICAL], [\T_IS_NOT_EQUAL], [\T_IS_NOT_IDENTICAL], [\T_IS_SMALLER_OR_EQUAL], [\T_MINUS_EQUAL], [\T_MOD_EQUAL], [\T_MUL_EQUAL], [\T_OR_EQUAL], [\T_PAAMAYIM_NEKUDOTAYIM], [\T_PLUS_EQUAL], [\T_POW], [\T_POW_EQUAL], [\T_SL], [\T_SL_EQUAL], [\T_SR], [\T_SR_EQUAL], [\T_XOR_EQUAL]], \array_map(function ($id) {
            return [$id];
        }, \PhpCsFixer\Tokenizer\Token::getObjectOperatorKinds()));
    }
}
