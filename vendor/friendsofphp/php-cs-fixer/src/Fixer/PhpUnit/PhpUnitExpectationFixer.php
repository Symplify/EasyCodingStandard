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
namespace PhpCsFixer\Fixer\PhpUnit;

use PhpCsFixer\Fixer\AbstractPhpUnitFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\WhitespacesAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class PhpUnitExpectationFixer extends \PhpCsFixer\Fixer\AbstractPhpUnitFixer implements \PhpCsFixer\Fixer\ConfigurableFixerInterface, \PhpCsFixer\Fixer\WhitespacesAwareFixerInterface
{
    /**
     * @var array<string, string>
     */
    private $methodMap = [];
    /**
     * {@inheritdoc}
     * @param mixed[] $configuration
     * @return void
     */
    public function configure($configuration)
    {
        parent::configure($configuration);
        $this->methodMap = ['setExpectedException' => 'expectExceptionMessage'];
        if (\PhpCsFixer\Fixer\PhpUnit\PhpUnitTargetVersion::fulfills($this->configuration['target'], \PhpCsFixer\Fixer\PhpUnit\PhpUnitTargetVersion::VERSION_5_6)) {
            $this->methodMap['setExpectedExceptionRegExp'] = 'expectExceptionMessageRegExp';
        }
        if (\PhpCsFixer\Fixer\PhpUnit\PhpUnitTargetVersion::fulfills($this->configuration['target'], \PhpCsFixer\Fixer\PhpUnit\PhpUnitTargetVersion::VERSION_8_4)) {
            $this->methodMap['setExpectedExceptionRegExp'] = 'expectExceptionMessageMatches';
            $this->methodMap['expectExceptionMessageRegExp'] = 'expectExceptionMessageMatches';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function getDefinition() : \PhpCsFixer\FixerDefinition\FixerDefinitionInterface
    {
        return new \PhpCsFixer\FixerDefinition\FixerDefinition('Usages of `->setExpectedException*` methods MUST be replaced by `->expectException*` methods.', [new \PhpCsFixer\FixerDefinition\CodeSample('<?php
final class MyTest extends \\PHPUnit_Framework_TestCase
{
    public function testFoo()
    {
        $this->setExpectedException("RuntimeException", "Msg", 123);
        foo();
    }

    public function testBar()
    {
        $this->setExpectedExceptionRegExp("RuntimeException", "/Msg.*/", 123);
        bar();
    }
}
'), new \PhpCsFixer\FixerDefinition\CodeSample('<?php
final class MyTest extends \\PHPUnit_Framework_TestCase
{
    public function testFoo()
    {
        $this->setExpectedException("RuntimeException", null, 123);
        foo();
    }

    public function testBar()
    {
        $this->setExpectedExceptionRegExp("RuntimeException", "/Msg.*/", 123);
        bar();
    }
}
', ['target' => \PhpCsFixer\Fixer\PhpUnit\PhpUnitTargetVersion::VERSION_8_4]), new \PhpCsFixer\FixerDefinition\CodeSample('<?php
final class MyTest extends \\PHPUnit_Framework_TestCase
{
    public function testFoo()
    {
        $this->setExpectedException("RuntimeException", null, 123);
        foo();
    }

    public function testBar()
    {
        $this->setExpectedExceptionRegExp("RuntimeException", "/Msg.*/", 123);
        bar();
    }
}
', ['target' => \PhpCsFixer\Fixer\PhpUnit\PhpUnitTargetVersion::VERSION_5_6]), new \PhpCsFixer\FixerDefinition\CodeSample('<?php
final class MyTest extends \\PHPUnit_Framework_TestCase
{
    public function testFoo()
    {
        $this->setExpectedException("RuntimeException", "Msg", 123);
        foo();
    }

    public function testBar()
    {
        $this->setExpectedExceptionRegExp("RuntimeException", "/Msg.*/", 123);
        bar();
    }
}
', ['target' => \PhpCsFixer\Fixer\PhpUnit\PhpUnitTargetVersion::VERSION_5_2])], null, 'Risky when PHPUnit classes are overridden or not accessible, or when project has PHPUnit incompatibilities.');
    }
    /**
     * {@inheritdoc}
     *
     * Must run after PhpUnitNoExpectationAnnotationFixer.
     */
    public function getPriority() : int
    {
        return 0;
    }
    /**
     * {@inheritdoc}
     */
    public function isRisky() : bool
    {
        return \true;
    }
    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition() : \PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface
    {
        return new \PhpCsFixer\FixerConfiguration\FixerConfigurationResolver([(new \PhpCsFixer\FixerConfiguration\FixerOptionBuilder('target', 'Target version of PHPUnit.'))->setAllowedTypes(['string'])->setAllowedValues([\PhpCsFixer\Fixer\PhpUnit\PhpUnitTargetVersion::VERSION_5_2, \PhpCsFixer\Fixer\PhpUnit\PhpUnitTargetVersion::VERSION_5_6, \PhpCsFixer\Fixer\PhpUnit\PhpUnitTargetVersion::VERSION_8_4, \PhpCsFixer\Fixer\PhpUnit\PhpUnitTargetVersion::VERSION_NEWEST])->setDefault(\PhpCsFixer\Fixer\PhpUnit\PhpUnitTargetVersion::VERSION_NEWEST)->getOption()]);
    }
    /**
     * {@inheritdoc}
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @param int $startIndex
     * @param int $endIndex
     * @return void
     */
    protected function applyPhpUnitClassFix($tokens, $startIndex, $endIndex)
    {
        foreach (\PhpCsFixer\Tokenizer\Token::getObjectOperatorKinds() as $objectOperator) {
            $this->applyPhpUnitClassFixWithObjectOperator($tokens, $startIndex, $endIndex, $objectOperator);
        }
    }
    /**
     * @param int $startIndex
     * @param int $endIndex
     * @param int $objectOperator
     * @return void
     */
    private function applyPhpUnitClassFixWithObjectOperator(\PhpCsFixer\Tokenizer\Tokens $tokens, $startIndex, $endIndex, $objectOperator)
    {
        $argumentsAnalyzer = new \PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer();
        $oldMethodSequence = [[\T_VARIABLE, '$this'], [$objectOperator], [\T_STRING]];
        for ($index = $startIndex; $startIndex < $endIndex; ++$index) {
            $match = $tokens->findSequence($oldMethodSequence, $index);
            if (null === $match) {
                return;
            }
            list($thisIndex, , $index) = \array_keys($match);
            if (!isset($this->methodMap[$tokens[$index]->getContent()])) {
                continue;
            }
            $openIndex = $tokens->getNextTokenOfKind($index, ['(']);
            $closeIndex = $tokens->findBlockEnd(\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openIndex);
            $commaIndex = $tokens->getPrevMeaningfulToken($closeIndex);
            if ($tokens[$commaIndex]->equals(',')) {
                $tokens->removeTrailingWhitespace($commaIndex);
                $tokens->clearAt($commaIndex);
            }
            $arguments = $argumentsAnalyzer->getArguments($tokens, $openIndex, $closeIndex);
            $argumentsCnt = \count($arguments);
            $argumentsReplacements = ['expectException', $this->methodMap[$tokens[$index]->getContent()], 'expectExceptionCode'];
            $indent = $this->whitespacesConfig->getLineEnding() . \PhpCsFixer\Tokenizer\Analyzer\WhitespacesAnalyzer::detectIndent($tokens, $thisIndex);
            $isMultilineWhitespace = \false;
            for ($cnt = $argumentsCnt - 1; $cnt >= 1; --$cnt) {
                $argStart = \array_keys($arguments)[$cnt];
                $argBefore = $tokens->getPrevMeaningfulToken($argStart);
                if ('expectExceptionMessage' === $argumentsReplacements[$cnt]) {
                    $paramIndicatorIndex = $tokens->getNextMeaningfulToken($argBefore);
                    $afterParamIndicatorIndex = $tokens->getNextMeaningfulToken($paramIndicatorIndex);
                    if ($tokens[$paramIndicatorIndex]->equals([\T_STRING, 'null'], \false) && $tokens[$afterParamIndicatorIndex]->equals(')')) {
                        if ($tokens[$argBefore + 1]->isWhitespace()) {
                            $tokens->clearTokenAndMergeSurroundingWhitespace($argBefore + 1);
                        }
                        $tokens->clearTokenAndMergeSurroundingWhitespace($argBefore);
                        $tokens->clearTokenAndMergeSurroundingWhitespace($paramIndicatorIndex);
                        continue;
                    }
                }
                $isMultilineWhitespace = $isMultilineWhitespace || $tokens[$argStart]->isWhitespace() && !$tokens[$argStart]->isWhitespace(" \t");
                $tokensOverrideArgStart = [new \PhpCsFixer\Tokenizer\Token([\T_WHITESPACE, $indent]), new \PhpCsFixer\Tokenizer\Token([\T_VARIABLE, '$this']), new \PhpCsFixer\Tokenizer\Token([\T_OBJECT_OPERATOR, '->']), new \PhpCsFixer\Tokenizer\Token([\T_STRING, $argumentsReplacements[$cnt]]), new \PhpCsFixer\Tokenizer\Token('(')];
                $tokensOverrideArgBefore = [new \PhpCsFixer\Tokenizer\Token(')'), new \PhpCsFixer\Tokenizer\Token(';')];
                if ($isMultilineWhitespace) {
                    $tokensOverrideArgStart[] = new \PhpCsFixer\Tokenizer\Token([\T_WHITESPACE, $indent . $this->whitespacesConfig->getIndent()]);
                    \array_unshift($tokensOverrideArgBefore, new \PhpCsFixer\Tokenizer\Token([\T_WHITESPACE, $indent]));
                }
                if ($tokens[$argStart]->isWhitespace()) {
                    $tokens->overrideRange($argStart, $argStart, $tokensOverrideArgStart);
                } else {
                    $tokens->insertAt($argStart, $tokensOverrideArgStart);
                }
                $tokens->overrideRange($argBefore, $argBefore, $tokensOverrideArgBefore);
            }
            $methodName = 'expectException';
            if ('expectExceptionMessageRegExp' === $tokens[$index]->getContent()) {
                $methodName = $this->methodMap[$tokens[$index]->getContent()];
            }
            $tokens[$index] = new \PhpCsFixer\Tokenizer\Token([\T_STRING, $methodName]);
        }
    }
}
