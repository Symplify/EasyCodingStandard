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
namespace PhpCsFixer\Fixer\Phpdoc;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
final class NoSuperfluousPhpdocTagsFixer extends \PhpCsFixer\AbstractFixer implements \PhpCsFixer\Fixer\ConfigurableFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition() : \PhpCsFixer\FixerDefinition\FixerDefinitionInterface
    {
        return new \PhpCsFixer\FixerDefinition\FixerDefinition('Removes `@param`, `@return` and `@var` tags that don\'t provide any useful information.', [new \PhpCsFixer\FixerDefinition\CodeSample('<?php
class Foo {
    /**
     * @param Bar $bar
     * @param mixed $baz
     */
    public function doFoo(Bar $bar, $baz) {}
}
'), new \PhpCsFixer\FixerDefinition\CodeSample('<?php
class Foo {
    /**
     * @param Bar $bar
     * @param mixed $baz
     */
    public function doFoo(Bar $bar, $baz) {}
}
', ['allow_mixed' => \true]), new \PhpCsFixer\FixerDefinition\VersionSpecificCodeSample('<?php
class Foo {
    /**
     * @param Bar $bar
     * @param mixed $baz
     *
     * @return Baz
     */
    public function doFoo(Bar $bar, $baz): Baz {}
}
', new \PhpCsFixer\FixerDefinition\VersionSpecification(70000)), new \PhpCsFixer\FixerDefinition\CodeSample('<?php
class Foo {
    /**
     * @inheritDoc
     */
    public function doFoo(Bar $bar, $baz) {}
}
', ['remove_inheritdoc' => \true]), new \PhpCsFixer\FixerDefinition\CodeSample('<?php
class Foo {
    /**
     * @param Bar $bar
     * @param mixed $baz
     * @param string|int|null $qux
     */
    public function doFoo(Bar $bar, $baz /*, $qux = null */) {}
}
', ['allow_unused_params' => \true])]);
    }
    /**
     * {@inheritdoc}
     *
     * Must run before NoEmptyPhpdocFixer, PhpdocAlignFixer, VoidReturnFixer.
     * Must run after AlignMultilineCommentFixer, CommentToPhpdocFixer, FullyQualifiedStrictTypesFixer, PhpdocAddMissingParamAnnotationFixer, PhpdocIndentFixer, PhpdocReturnSelfReferenceFixer, PhpdocScalarFixer, PhpdocToCommentFixer, PhpdocToParamTypeFixer, PhpdocToReturnTypeFixer, PhpdocTypesFixer.
     */
    public function getPriority() : int
    {
        return 6;
    }
    /**
     * {@inheritdoc}
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     */
    public function isCandidate($tokens) : bool
    {
        return $tokens->isTokenKindFound(\T_DOC_COMMENT);
    }
    /**
     * {@inheritdoc}
     * @param \SplFileInfo $file
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @return void
     */
    protected function applyFix($file, $tokens)
    {
        $namespaceUseAnalyzer = new \PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer();
        $shortNames = [];
        foreach ($namespaceUseAnalyzer->getDeclarationsFromTokens($tokens) as $namespaceUseAnalysis) {
            $shortNames[\strtolower($namespaceUseAnalysis->getShortName())] = '\\' . \strtolower($namespaceUseAnalysis->getFullName());
        }
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(\T_DOC_COMMENT)) {
                continue;
            }
            $content = $initialContent = $token->getContent();
            $documentedElementIndex = $this->findDocumentedElement($tokens, $index);
            if (null === $documentedElementIndex) {
                continue;
            }
            $token = $tokens[$documentedElementIndex];
            if ($this->configuration['remove_inheritdoc']) {
                $content = $this->removeSuperfluousInheritDoc($content);
            }
            if ($token->isGivenKind(\T_FUNCTION)) {
                $content = $this->fixFunctionDocComment($content, $tokens, $index, $shortNames);
            } elseif ($token->isGivenKind(\T_VARIABLE)) {
                $content = $this->fixPropertyDocComment($content, $tokens, $index, $shortNames);
            }
            if ('' === $content) {
                $content = '/**  */';
            }
            if ($content !== $initialContent) {
                $tokens[$index] = new \PhpCsFixer\Tokenizer\Token([\T_DOC_COMMENT, $content]);
            }
        }
    }
    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition() : \PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface
    {
        return new \PhpCsFixer\FixerConfiguration\FixerConfigurationResolver([(new \PhpCsFixer\FixerConfiguration\FixerOptionBuilder('allow_mixed', 'Whether type `mixed` without description is allowed (`true`) or considered superfluous (`false`)'))->setAllowedTypes(['bool'])->setDefault(\false)->getOption(), (new \PhpCsFixer\FixerConfiguration\FixerOptionBuilder('remove_inheritdoc', 'Remove `@inheritDoc` tags'))->setAllowedTypes(['bool'])->setDefault(\false)->getOption(), (new \PhpCsFixer\FixerConfiguration\FixerOptionBuilder('allow_unused_params', 'Whether `param` annotation without actual signature is allowed (`true`) or considered superfluous (`false`)'))->setAllowedTypes(['bool'])->setDefault(\false)->getOption()]);
    }
    /**
     * @return int|null
     */
    private function findDocumentedElement(\PhpCsFixer\Tokenizer\Tokens $tokens, int $docCommentIndex)
    {
        $index = $docCommentIndex;
        do {
            $index = $tokens->getNextMeaningfulToken($index);
            if (null === $index || $tokens[$index]->isGivenKind([\T_FUNCTION, \T_CLASS, \T_INTERFACE])) {
                return $index;
            }
        } while ($tokens[$index]->isGivenKind([\T_ABSTRACT, \T_FINAL, \T_STATIC, \T_PRIVATE, \T_PROTECTED, \T_PUBLIC]));
        $index = $tokens->getNextMeaningfulToken($docCommentIndex);
        $kindsBeforeProperty = [\T_STATIC, \T_PRIVATE, \T_PROTECTED, \T_PUBLIC, \PhpCsFixer\Tokenizer\CT::T_NULLABLE_TYPE, \PhpCsFixer\Tokenizer\CT::T_ARRAY_TYPEHINT, \T_STRING, \T_NS_SEPARATOR];
        if (!$tokens[$index]->isGivenKind($kindsBeforeProperty)) {
            return null;
        }
        do {
            $index = $tokens->getNextMeaningfulToken($index);
            if ($tokens[$index]->isGivenKind(\T_VARIABLE)) {
                return $index;
            }
        } while ($tokens[$index]->isGivenKind($kindsBeforeProperty));
        return null;
    }
    private function fixFunctionDocComment(string $content, \PhpCsFixer\Tokenizer\Tokens $tokens, int $functionIndex, array $shortNames) : string
    {
        $docBlock = new \PhpCsFixer\DocBlock\DocBlock($content);
        $openingParenthesisIndex = $tokens->getNextTokenOfKind($functionIndex, ['(']);
        $closingParenthesisIndex = $tokens->findBlockEnd(\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openingParenthesisIndex);
        $argumentsInfo = $this->getArgumentsInfo($tokens, $openingParenthesisIndex + 1, $closingParenthesisIndex - 1);
        foreach ($docBlock->getAnnotationsOfType('param') as $annotation) {
            $argumentName = $annotation->getVariableName();
            if (null === $argumentName) {
                continue;
            }
            if (!isset($argumentsInfo[$argumentName]) && $this->configuration['allow_unused_params']) {
                continue;
            }
            if (!isset($argumentsInfo[$argumentName]) || $this->annotationIsSuperfluous($annotation, $argumentsInfo[$argumentName], $shortNames)) {
                $annotation->remove();
            }
        }
        $returnTypeInfo = $this->getReturnTypeInfo($tokens, $closingParenthesisIndex);
        foreach ($docBlock->getAnnotationsOfType('return') as $annotation) {
            if ($this->annotationIsSuperfluous($annotation, $returnTypeInfo, $shortNames)) {
                $annotation->remove();
            }
        }
        return $docBlock->getContent();
    }
    /**
     * @param int $index Index of the DocComment token
     */
    private function fixPropertyDocComment(string $content, \PhpCsFixer\Tokenizer\Tokens $tokens, int $index, array $shortNames) : string
    {
        $docBlock = new \PhpCsFixer\DocBlock\DocBlock($content);
        do {
            $index = $tokens->getNextMeaningfulToken($index);
        } while ($tokens[$index]->isGivenKind([\T_STATIC, \T_PRIVATE, \T_PROTECTED, \T_PUBLIC]));
        $propertyTypeInfo = $this->getPropertyTypeInfo($tokens, $index);
        foreach ($docBlock->getAnnotationsOfType('var') as $annotation) {
            if ($this->annotationIsSuperfluous($annotation, $propertyTypeInfo, $shortNames)) {
                $annotation->remove();
            }
        }
        return $docBlock->getContent();
    }
    /**
     * @return array<string, array>
     */
    private function getArgumentsInfo(\PhpCsFixer\Tokenizer\Tokens $tokens, int $start, int $end) : array
    {
        $argumentsInfo = [];
        for ($index = $start; $index <= $end; ++$index) {
            $token = $tokens[$index];
            if (!$token->isGivenKind(\T_VARIABLE)) {
                continue;
            }
            $beforeArgumentIndex = $tokens->getPrevTokenOfKind($index, ['(', ',']);
            $typeIndex = $tokens->getNextMeaningfulToken($beforeArgumentIndex);
            if ($typeIndex !== $index) {
                $info = $this->parseTypeHint($tokens, $typeIndex);
            } else {
                $info = ['type' => null, 'allows_null' => \true];
            }
            if (!$info['allows_null']) {
                $nextIndex = $tokens->getNextMeaningfulToken($index);
                if ($tokens[$nextIndex]->equals('=') && $tokens[$tokens->getNextMeaningfulToken($nextIndex)]->equals([\T_STRING, 'null'])) {
                    $info['allows_null'] = \true;
                }
            }
            $argumentsInfo[$token->getContent()] = $info;
        }
        return $argumentsInfo;
    }
    private function getReturnTypeInfo(\PhpCsFixer\Tokenizer\Tokens $tokens, int $closingParenthesisIndex) : array
    {
        $colonIndex = $tokens->getNextMeaningfulToken($closingParenthesisIndex);
        if ($tokens[$colonIndex]->isGivenKind(\PhpCsFixer\Tokenizer\CT::T_TYPE_COLON)) {
            return $this->parseTypeHint($tokens, $tokens->getNextMeaningfulToken($colonIndex));
        }
        return ['type' => null, 'allows_null' => \true];
    }
    /**
     * @param int $index The index of the first token of the type hint
     */
    private function getPropertyTypeInfo(\PhpCsFixer\Tokenizer\Tokens $tokens, int $index) : array
    {
        if ($tokens[$index]->isGivenKind(\T_VARIABLE)) {
            return ['type' => null, 'allows_null' => \true];
        }
        return $this->parseTypeHint($tokens, $index);
    }
    /**
     * @param int $index The index of the first token of the type hint
     */
    private function parseTypeHint(\PhpCsFixer\Tokenizer\Tokens $tokens, int $index) : array
    {
        $allowsNull = \false;
        if ($tokens[$index]->isGivenKind(\PhpCsFixer\Tokenizer\CT::T_NULLABLE_TYPE)) {
            $allowsNull = \true;
            $index = $tokens->getNextMeaningfulToken($index);
        }
        $type = '';
        while ($tokens[$index]->isGivenKind([\T_NS_SEPARATOR, \T_STATIC, \T_STRING, \PhpCsFixer\Tokenizer\CT::T_ARRAY_TYPEHINT, \T_CALLABLE])) {
            $type .= $tokens[$index]->getContent();
            $index = $tokens->getNextMeaningfulToken($index);
        }
        return ['type' => '' === $type ? null : $type, 'allows_null' => $allowsNull];
    }
    /**
     * @param array<string, string> $symbolShortNames
     */
    private function annotationIsSuperfluous(\PhpCsFixer\DocBlock\Annotation $annotation, array $info, array $symbolShortNames) : bool
    {
        if ('param' === $annotation->getTag()->getName()) {
            $regex = '/@param\\s+(?:\\S|\\s(?!\\$))++\\s\\$\\S+\\s+\\S/';
        } elseif ('var' === $annotation->getTag()->getName()) {
            $regex = '/@var\\s+\\S+(\\s+\\$\\S+)?(\\s+)(?!\\*+\\/)([^$\\s]+)/';
        } else {
            $regex = '/@return\\s+\\S+\\s+\\S/';
        }
        if (\PhpCsFixer\Preg::match($regex, $annotation->getContent())) {
            return \false;
        }
        $annotationTypes = $this->toComparableNames($annotation->getTypes(), $symbolShortNames);
        if (['null'] === $annotationTypes) {
            return \false;
        }
        if (['mixed'] === $annotationTypes && null === $info['type']) {
            return !$this->configuration['allow_mixed'];
        }
        $actualTypes = null === $info['type'] ? [] : [$info['type']];
        if ($info['allows_null']) {
            $actualTypes[] = 'null';
        }
        return $annotationTypes === $this->toComparableNames($actualTypes, $symbolShortNames);
    }
    /**
     * Normalizes types to make them comparable.
     *
     * Converts given types to lowercase, replaces imports aliases with
     * their matching FQCN, and finally sorts the result.
     *
     * @param string[]              $types            The types to normalize
     * @param array<string, string> $symbolShortNames The imports aliases
     *
     * @return array The normalized types
     */
    private function toComparableNames(array $types, array $symbolShortNames) : array
    {
        $normalized = \array_map(static function (string $type) use($symbolShortNames) {
            $type = \strtolower($type);
            if (isset($symbolShortNames[$type])) {
                return $symbolShortNames[$type];
            }
            return $type;
        }, $types);
        \sort($normalized);
        return $normalized;
    }
    private function removeSuperfluousInheritDoc(string $docComment) : string
    {
        return \PhpCsFixer\Preg::replace('~
            # $1: before @inheritDoc tag
            (
                # beginning of comment or a PHPDoc tag
                (?:
                    ^/\\*\\*
                    (?:
                        \\R
                        [ \\t]*(?:\\*[ \\t]*)?
                    )*?
                    |
                    @\\N+
                )

                # empty comment lines
                (?:
                    \\R
                    [ \\t]*(?:\\*[ \\t]*?)?
                )*
            )

            # spaces before @inheritDoc tag
            [ \\t]*

            # @inheritDoc tag
            (?:@inheritDocs?|\\{@inheritDocs?\\})

            # $2: after @inheritDoc tag
            (
                # empty comment lines
                (?:
                    \\R
                    [ \\t]*(?:\\*[ \\t]*)?
                )*

                # a PHPDoc tag or end of comment
                (?:
                    @\\N+
                    |
                    (?:
                        \\R
                        [ \\t]*(?:\\*[ \\t]*)?
                    )*
                    [ \\t]*\\*/$
                )
            )
        ~ix', '$1$2', $docComment);
    }
}
