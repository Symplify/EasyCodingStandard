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
namespace PhpCsFixer\Fixer\Import;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\TypeAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Generator\NamespacedStringTokenGenerator;
use PhpCsFixer\Tokenizer\Resolver\TypeShortNameResolver;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
/**
 * @author VeeWee <toonverwerft@gmail.com>
 */
final class FullyQualifiedStrictTypesFixer extends \PhpCsFixer\AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition() : \PhpCsFixer\FixerDefinition\FixerDefinitionInterface
    {
        return new \PhpCsFixer\FixerDefinition\FixerDefinition('Transforms imported FQCN parameters and return types in function arguments to short version.', [new \PhpCsFixer\FixerDefinition\CodeSample('<?php

use Foo\\Bar;

class SomeClass
{
    public function doSomething(\\Foo\\Bar $foo)
    {
    }
}
'), new \PhpCsFixer\FixerDefinition\VersionSpecificCodeSample('<?php

use Foo\\Bar;
use Foo\\Bar\\Baz;

class SomeClass
{
    public function doSomething(\\Foo\\Bar $foo): \\Foo\\Bar\\Baz
    {
    }
}
', new \PhpCsFixer\FixerDefinition\VersionSpecification(70000))]);
    }
    /**
     * {@inheritdoc}
     *
     * Must run before NoSuperfluousPhpdocTagsFixer.
     * Must run after PhpdocToReturnTypeFixer.
     */
    public function getPriority() : int
    {
        return 7;
    }
    /**
     * {@inheritdoc}
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     */
    public function isCandidate($tokens) : bool
    {
        return $tokens->isTokenKindFound(\T_FUNCTION) && (\count((new \PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer())->getDeclarations($tokens)) || \count((new \PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer())->getDeclarationsFromTokens($tokens)));
    }
    /**
     * {@inheritdoc}
     * @param \SplFileInfo $file
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @return void
     */
    protected function applyFix($file, $tokens)
    {
        $lastIndex = $tokens->count() - 1;
        for ($index = $lastIndex; $index >= 0; --$index) {
            if (!$tokens[$index]->isGivenKind(\T_FUNCTION)) {
                continue;
            }
            // Return types are only available since PHP 7.0
            $this->fixFunctionReturnType($tokens, $index);
            $this->fixFunctionArguments($tokens, $index);
        }
    }
    /**
     * @return void
     */
    private function fixFunctionArguments(\PhpCsFixer\Tokenizer\Tokens $tokens, int $index)
    {
        $arguments = (new \PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer())->getFunctionArguments($tokens, $index);
        foreach ($arguments as $argument) {
            if (!$argument->hasTypeAnalysis()) {
                continue;
            }
            $this->detectAndReplaceTypeWithShortType($tokens, $argument->getTypeAnalysis());
        }
    }
    /**
     * @return void
     */
    private function fixFunctionReturnType(\PhpCsFixer\Tokenizer\Tokens $tokens, int $index)
    {
        if (\PHP_VERSION_ID < 70000) {
            return;
        }
        $returnType = (new \PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer())->getFunctionReturnType($tokens, $index);
        if (!$returnType) {
            return;
        }
        $this->detectAndReplaceTypeWithShortType($tokens, $returnType);
    }
    /**
     * @return void
     */
    private function detectAndReplaceTypeWithShortType(\PhpCsFixer\Tokenizer\Tokens $tokens, \PhpCsFixer\Tokenizer\Analyzer\Analysis\TypeAnalysis $type)
    {
        if ($type->isReservedType()) {
            return;
        }
        $typeName = $type->getName();
        if (0 !== \strpos($typeName, '\\')) {
            return;
        }
        $shortType = (new \PhpCsFixer\Tokenizer\Resolver\TypeShortNameResolver())->resolve($tokens, $typeName);
        if ($shortType === $typeName) {
            return;
        }
        $shortType = (new \PhpCsFixer\Tokenizer\Generator\NamespacedStringTokenGenerator())->generate($shortType);
        if (\true === $type->isNullable()) {
            \array_unshift($shortType, new \PhpCsFixer\Tokenizer\Token([\PhpCsFixer\Tokenizer\CT::T_NULLABLE_TYPE, '?']));
        }
        $tokens->overrideRange($type->getStartIndex(), $type->getEndIndex(), $shortType);
    }
}
