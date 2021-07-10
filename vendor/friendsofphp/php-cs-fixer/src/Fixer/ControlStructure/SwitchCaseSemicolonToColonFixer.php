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
namespace PhpCsFixer\Fixer\ControlStructure;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
/**
 * Fixer for rules defined in PSR2 ¶5.2.
 *
 * @author SpacePossum
 */
final class SwitchCaseSemicolonToColonFixer extends \PhpCsFixer\AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition() : \PhpCsFixer\FixerDefinition\FixerDefinitionInterface
    {
        return new \PhpCsFixer\FixerDefinition\FixerDefinition('A case should be followed by a colon and not a semicolon.', [new \PhpCsFixer\FixerDefinition\CodeSample('<?php
    switch ($a) {
        case 1;
            break;
        default;
            break;
    }
')]);
    }
    /**
     * {@inheritdoc}
     *
     * Must run after NoEmptyStatementFixer.
     */
    public function getPriority() : int
    {
        return 0;
    }
    /**
     * {@inheritdoc}
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     */
    public function isCandidate($tokens) : bool
    {
        return $tokens->isAnyTokenKindsFound([\T_CASE, \T_DEFAULT]);
    }
    /**
     * {@inheritdoc}
     * @param \SplFileInfo $file
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @return void
     */
    protected function applyFix($file, $tokens)
    {
        foreach ($tokens as $index => $token) {
            if ($token->isGivenKind(\T_CASE)) {
                $this->fixSwitchCase($tokens, $index);
            }
            if ($token->isGivenKind(\T_DEFAULT)) {
                $this->fixSwitchDefault($tokens, $index);
            }
        }
    }
    /**
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @param int $index
     * @return void
     */
    protected function fixSwitchCase($tokens, $index)
    {
        $ternariesCount = 0;
        do {
            if ($tokens[$index]->equalsAny(['(', '{'])) {
                // skip constructs
                $type = \PhpCsFixer\Tokenizer\Tokens::detectBlockType($tokens[$index]);
                $index = $tokens->findBlockEnd($type['type'], $index);
                continue;
            }
            if ($tokens[$index]->equals('?')) {
                ++$ternariesCount;
                continue;
            }
            if ($tokens[$index]->equalsAny([':', ';'])) {
                if (0 === $ternariesCount) {
                    break;
                }
                --$ternariesCount;
            }
        } while (++$index);
        if ($tokens[$index]->equals(';')) {
            $tokens[$index] = new \PhpCsFixer\Tokenizer\Token(':');
        }
    }
    /**
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @param int $index
     * @return void
     */
    protected function fixSwitchDefault($tokens, $index)
    {
        do {
            if ($tokens[$index]->equalsAny([':', ';', [\T_DOUBLE_ARROW]])) {
                break;
            }
        } while (++$index);
        if ($tokens[$index]->equals(';')) {
            $tokens[$index] = new \PhpCsFixer\Tokenizer\Token(':');
        }
    }
}
