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
namespace PhpCsFixer\Tokenizer\Analyzer;

use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceAnalysis;
use PhpCsFixer\Tokenizer\Tokens;
/**
 * @internal
 */
final class NamespacesAnalyzer
{
    /**
     * @return mixed[]
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     */
    public function getDeclarations($tokens)
    {
        $namespaces = [];
        for ($index = 1, $count = \count($tokens); $index < $count; ++$index) {
            $token = $tokens[$index];
            if (!$token->isGivenKind(\T_NAMESPACE)) {
                continue;
            }
            $declarationEndIndex = $tokens->getNextTokenOfKind($index, [';', '{']);
            $namespace = \trim($tokens->generatePartialCode($index + 1, $declarationEndIndex - 1));
            $declarationParts = \explode('\\', $namespace);
            $shortName = \end($declarationParts);
            if ($tokens[$declarationEndIndex]->equals('{')) {
                $scopeEndIndex = $tokens->findBlockEnd(\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_CURLY_BRACE, $declarationEndIndex);
            } else {
                $scopeEndIndex = $tokens->getNextTokenOfKind($declarationEndIndex, [[\T_NAMESPACE]]);
                if (null === $scopeEndIndex) {
                    $scopeEndIndex = \count($tokens);
                }
                --$scopeEndIndex;
            }
            $namespaces[] = new \PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceAnalysis($namespace, $shortName, $index, $declarationEndIndex, $index, $scopeEndIndex);
            // Continue the analysis after the end of this namespace to find the next one
            $index = $scopeEndIndex;
        }
        if (0 === \count($namespaces)) {
            $namespaces[] = new \PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceAnalysis('', '', 0, 0, 0, \count($tokens) - 1);
        }
        return $namespaces;
    }
    /**
     * @param int $index
     *
     * @return NamespaceAnalysis
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     */
    public function getNamespaceAt($tokens, $index)
    {
        if (!$tokens->offsetExists($index)) {
            throw new \InvalidArgumentException("Token index {$index} does not exist.");
        }
        foreach ($this->getDeclarations($tokens) as $namespace) {
            if ($namespace->getScopeStartIndex() <= $index && $namespace->getScopeEndIndex() >= $index) {
                return $namespace;
            }
        }
        throw new \LogicException("Unable to get the namespace at index {$index}.");
    }
}
