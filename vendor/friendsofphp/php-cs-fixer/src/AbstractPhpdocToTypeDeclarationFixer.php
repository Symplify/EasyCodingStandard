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
namespace PhpCsFixer;

use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
/**
 * @internal
 */
abstract class AbstractPhpdocToTypeDeclarationFixer extends \PhpCsFixer\AbstractFixer implements \PhpCsFixer\Fixer\ConfigurableFixerInterface
{
    /**
     * @var string
     */
    private $classRegex = '/^\\\\?[a-zA-Z_\\x7f-\\xff](?:\\\\?[a-zA-Z0-9_\\x7f-\\xff]+)*$/';
    /**
     * @var array<string, int>
     */
    private $versionSpecificTypes = ['void' => 70100, 'iterable' => 70100, 'object' => 70200, 'mixed' => 80000];
    /**
     * @var array<string, bool>
     */
    private $scalarTypes = ['bool' => \true, 'float' => \true, 'int' => \true, 'string' => \true];
    /**
     * @var array<string, bool>
     */
    private static $syntaxValidationCache = [];
    /**
     * {@inheritdoc}
     * @return bool
     */
    public function isRisky()
    {
        return \true;
    }
    /**
     * @param string $type
     * @return bool
     */
    protected abstract function isSkippedType($type);
    /**
     * {@inheritdoc}
     * @return \PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface
     */
    protected function createConfigurationDefinition()
    {
        return new \PhpCsFixer\FixerConfiguration\FixerConfigurationResolver([(new \PhpCsFixer\FixerConfiguration\FixerOptionBuilder('scalar_types', 'Fix also scalar types; may have unexpected behaviour due to PHP bad type coercion system.'))->setAllowedTypes(['bool'])->setDefault(\true)->getOption()]);
    }
    /**
     * @param int $index The index of the function token
     * @return int|null
     */
    protected function findFunctionDocComment(\PhpCsFixer\Tokenizer\Tokens $tokens, $index)
    {
        do {
            $index = $tokens->getPrevNonWhitespace($index);
        } while ($tokens[$index]->isGivenKind([\T_COMMENT, \T_ABSTRACT, \T_FINAL, \T_PRIVATE, \T_PROTECTED, \T_PUBLIC, \T_STATIC]));
        if ($tokens[$index]->isGivenKind(\T_DOC_COMMENT)) {
            return $index;
        }
        return null;
    }
    /**
     * @return mixed[]
     * @param string $name
     * @param int $docCommentIndex
     */
    protected function getAnnotationsFromDocComment($name, \PhpCsFixer\Tokenizer\Tokens $tokens, $docCommentIndex)
    {
        $name = (string) $name;
        $docCommentIndex = (int) $docCommentIndex;
        $namespacesAnalyzer = new \PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer();
        $namespace = $namespacesAnalyzer->getNamespaceAt($tokens, $docCommentIndex);
        $namespaceUsesAnalyzer = new \PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer();
        $namespaceUses = $namespaceUsesAnalyzer->getDeclarationsInNamespace($tokens, $namespace);
        $doc = new \PhpCsFixer\DocBlock\DocBlock($tokens[$docCommentIndex]->getContent(), $namespace, $namespaceUses);
        return $doc->getAnnotationsOfType($name);
    }
    /**
     * @return mixed[]
     * @param string $type
     * @param bool $isNullable
     */
    protected function createTypeDeclarationTokens($type, $isNullable)
    {
        $type = (string) $type;
        $isNullable = (bool) $isNullable;
        static $specialTypes = ['array' => [\PhpCsFixer\Tokenizer\CT::T_ARRAY_TYPEHINT, 'array'], 'callable' => [\T_CALLABLE, 'callable'], 'static' => [\T_STATIC, 'static']];
        $newTokens = [];
        if (\true === $isNullable && 'mixed' !== $type) {
            $newTokens[] = new \PhpCsFixer\Tokenizer\Token([\PhpCsFixer\Tokenizer\CT::T_NULLABLE_TYPE, '?']);
        }
        if (isset($specialTypes[$type])) {
            $newTokens[] = new \PhpCsFixer\Tokenizer\Token($specialTypes[$type]);
        } else {
            $typeUnqualified = \ltrim($type, '\\');
            if (isset($this->scalarTypes[$typeUnqualified]) || isset($this->versionSpecificTypes[$typeUnqualified])) {
                // 'scalar's, 'void', 'iterable' and 'object' must be unqualified
                $newTokens[] = new \PhpCsFixer\Tokenizer\Token([\T_STRING, $typeUnqualified]);
            } else {
                foreach (\explode('\\', $type) as $nsIndex => $value) {
                    if (0 === $nsIndex && '' === $value) {
                        continue;
                    }
                    if (0 < $nsIndex) {
                        $newTokens[] = new \PhpCsFixer\Tokenizer\Token([\T_NS_SEPARATOR, '\\']);
                    }
                    $newTokens[] = new \PhpCsFixer\Tokenizer\Token([\T_STRING, $value]);
                }
            }
        }
        return $newTokens;
    }
    /**
     * @return mixed[]|null
     * @param bool $isReturnType
     */
    protected function getCommonTypeFromAnnotation(\PhpCsFixer\DocBlock\Annotation $annotation, $isReturnType)
    {
        $isReturnType = (bool) $isReturnType;
        $typesExpression = $annotation->getTypeExpression();
        $commonType = $typesExpression->getCommonType();
        $isNullable = $typesExpression->allowsNull();
        if (null === $commonType) {
            return null;
        }
        if ($isNullable && (\PHP_VERSION_ID < 70100 || 'void' === $commonType)) {
            return null;
        }
        if ('static' === $commonType && (!$isReturnType || \PHP_VERSION_ID < 80000)) {
            $commonType = 'self';
        }
        if ($this->isSkippedType($commonType)) {
            return null;
        }
        if (isset($this->versionSpecificTypes[$commonType]) && \PHP_VERSION_ID < $this->versionSpecificTypes[$commonType]) {
            return null;
        }
        if (isset($this->scalarTypes[$commonType])) {
            if (\false === $this->configuration['scalar_types']) {
                return null;
            }
        } elseif (1 !== \PhpCsFixer\Preg::match($this->classRegex, $commonType)) {
            return null;
        }
        return [$commonType, $isNullable];
    }
    /**
     * @param string $code
     * @return bool
     */
    protected final function isValidSyntax($code)
    {
        $code = (string) $code;
        if (!isset(self::$syntaxValidationCache[$code])) {
            try {
                \PhpCsFixer\Tokenizer\Tokens::fromCode($code);
                self::$syntaxValidationCache[$code] = \true;
            } catch (\ParseError $e) {
                self::$syntaxValidationCache[$code] = \false;
            }
        }
        return self::$syntaxValidationCache[$code];
    }
}