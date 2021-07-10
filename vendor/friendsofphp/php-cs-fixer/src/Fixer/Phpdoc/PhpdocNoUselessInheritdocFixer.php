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
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
/**
 * Remove inheritdoc tags from classy that does not inherit.
 *
 * @author SpacePossum
 */
final class PhpdocNoUselessInheritdocFixer extends \PhpCsFixer\AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition() : \PhpCsFixer\FixerDefinition\FixerDefinitionInterface
    {
        return new \PhpCsFixer\FixerDefinition\FixerDefinition('Classy that does not inherit must not have `@inheritdoc` tags.', [new \PhpCsFixer\FixerDefinition\CodeSample("<?php\n/** {@inheritdoc} */\nclass Sample\n{\n}\n"), new \PhpCsFixer\FixerDefinition\CodeSample("<?php\nclass Sample\n{\n    /**\n     * @inheritdoc\n     */\n    public function Test()\n    {\n    }\n}\n")]);
    }
    /**
     * {@inheritdoc}
     *
     * Must run before NoEmptyPhpdocFixer, NoTrailingWhitespaceInCommentFixer, PhpdocAlignFixer.
     * Must run after AlignMultilineCommentFixer, CommentToPhpdocFixer, PhpdocIndentFixer, PhpdocScalarFixer, PhpdocToCommentFixer, PhpdocToCommentFixer, PhpdocTypesFixer.
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
        return $tokens->isTokenKindFound(\T_DOC_COMMENT) && $tokens->isAnyTokenKindsFound([\T_CLASS, \T_INTERFACE]);
    }
    /**
     * {@inheritdoc}
     * @param \SplFileInfo $file
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @return void
     */
    protected function applyFix($file, $tokens)
    {
        // min. offset 4 as minimal candidate is @: <?php\n/** @inheritdoc */class min{}
        for ($index = 1, $count = \count($tokens) - 4; $index < $count; ++$index) {
            if ($tokens[$index]->isGivenKind([\T_CLASS, \T_INTERFACE])) {
                $index = $this->fixClassy($tokens, $index);
            }
        }
    }
    private function fixClassy(\PhpCsFixer\Tokenizer\Tokens $tokens, int $index) : int
    {
        // figure out where the classy starts
        $classOpenIndex = $tokens->getNextTokenOfKind($index, ['{']);
        // figure out where the classy ends
        $classEndIndex = $tokens->findBlockEnd(\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_CURLY_BRACE, $classOpenIndex);
        // is classy extending or implementing some interface
        $extendingOrImplementing = $this->isExtendingOrImplementing($tokens, $index, $classOpenIndex);
        if (!$extendingOrImplementing) {
            // PHPDoc of classy should not have inherit tag even when using traits as Traits cannot provide this information
            $this->fixClassyOutside($tokens, $index);
        }
        // figure out if the classy uses a trait
        if (!$extendingOrImplementing && $this->isUsingTrait($tokens, $index, $classOpenIndex, $classEndIndex)) {
            $extendingOrImplementing = \true;
        }
        $this->fixClassyInside($tokens, $classOpenIndex, $classEndIndex, !$extendingOrImplementing);
        return $classEndIndex;
    }
    /**
     * @return void
     */
    private function fixClassyInside(\PhpCsFixer\Tokenizer\Tokens $tokens, int $classOpenIndex, int $classEndIndex, bool $fixThisLevel)
    {
        for ($i = $classOpenIndex; $i < $classEndIndex; ++$i) {
            if ($tokens[$i]->isGivenKind(\T_CLASS)) {
                $i = $this->fixClassy($tokens, $i);
            } elseif ($fixThisLevel && $tokens[$i]->isGivenKind(\T_DOC_COMMENT)) {
                $this->fixToken($tokens, $i);
            }
        }
    }
    /**
     * @return void
     */
    private function fixClassyOutside(\PhpCsFixer\Tokenizer\Tokens $tokens, int $classIndex)
    {
        $previousIndex = $tokens->getPrevNonWhitespace($classIndex);
        if ($tokens[$previousIndex]->isGivenKind(\T_DOC_COMMENT)) {
            $this->fixToken($tokens, $previousIndex);
        }
    }
    /**
     * @return void
     */
    private function fixToken(\PhpCsFixer\Tokenizer\Tokens $tokens, int $tokenIndex)
    {
        $count = 0;
        $content = \PhpCsFixer\Preg::replaceCallback('#(\\h*(?:@{*|{*\\h*@)\\h*inheritdoc\\h*)([^}]*)((?:}*)\\h*)#i', static function (array $matches) {
            return ' ' . $matches[2];
        }, $tokens[$tokenIndex]->getContent(), -1, $count);
        if ($count) {
            $tokens[$tokenIndex] = new \PhpCsFixer\Tokenizer\Token([\T_DOC_COMMENT, $content]);
        }
    }
    private function isExtendingOrImplementing(\PhpCsFixer\Tokenizer\Tokens $tokens, int $classIndex, int $classOpenIndex) : bool
    {
        for ($index = $classIndex; $index < $classOpenIndex; ++$index) {
            if ($tokens[$index]->isGivenKind([\T_EXTENDS, \T_IMPLEMENTS])) {
                return \true;
            }
        }
        return \false;
    }
    private function isUsingTrait(\PhpCsFixer\Tokenizer\Tokens $tokens, int $classIndex, int $classOpenIndex, int $classCloseIndex) : bool
    {
        if ($tokens[$classIndex]->isGivenKind(\T_INTERFACE)) {
            // cannot use Trait inside an interface
            return \false;
        }
        $useIndex = $tokens->getNextTokenOfKind($classOpenIndex, [[\PhpCsFixer\Tokenizer\CT::T_USE_TRAIT]]);
        return null !== $useIndex && $useIndex < $classCloseIndex;
    }
}
