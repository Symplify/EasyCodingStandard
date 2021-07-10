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
namespace PhpCsFixer\Fixer\LanguageConstruct;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author SpacePossum
 */
final class DeclareEqualNormalizeFixer extends \PhpCsFixer\AbstractFixer implements \PhpCsFixer\Fixer\ConfigurableFixerInterface
{
    /**
     * @var string
     */
    private $callback;
    /**
     * {@inheritdoc}
     * @param mixed[] $configuration
     * @return void
     */
    public function configure($configuration)
    {
        parent::configure($configuration);
        $this->callback = 'none' === $this->configuration['space'] ? 'removeWhitespaceAroundToken' : 'ensureWhitespaceAroundToken';
    }
    /**
     * {@inheritdoc}
     */
    public function getDefinition() : \PhpCsFixer\FixerDefinition\FixerDefinitionInterface
    {
        return new \PhpCsFixer\FixerDefinition\FixerDefinition('Equal sign in declare statement should be surrounded by spaces or not following configuration.', [new \PhpCsFixer\FixerDefinition\CodeSample("<?php\ndeclare(ticks =  1);\n"), new \PhpCsFixer\FixerDefinition\CodeSample("<?php\ndeclare(ticks=1);\n", ['space' => 'single'])]);
    }
    /**
     * {@inheritdoc}
     *
     * Must run after DeclareStrictTypesFixer.
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
        return $tokens->isTokenKindFound(\T_DECLARE);
    }
    /**
     * {@inheritdoc}
     * @param \SplFileInfo $file
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @return void
     */
    protected function applyFix($file, $tokens)
    {
        $callback = $this->callback;
        for ($index = 0, $count = $tokens->count(); $index < $count - 6; ++$index) {
            if (!$tokens[$index]->isGivenKind(\T_DECLARE)) {
                continue;
            }
            while (!$tokens[++$index]->equals('=')) {
            }
            $this->{$callback}($tokens, $index);
        }
    }
    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition() : \PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface
    {
        return new \PhpCsFixer\FixerConfiguration\FixerConfigurationResolver([(new \PhpCsFixer\FixerConfiguration\FixerOptionBuilder('space', 'Spacing to apply around the equal sign.'))->setAllowedValues(['single', 'none'])->setDefault('none')->getOption()]);
    }
    /**
     * @param int $index of `=` token
     * @return void
     */
    private function ensureWhitespaceAroundToken(\PhpCsFixer\Tokenizer\Tokens $tokens, int $index)
    {
        if ($tokens[$index + 1]->isWhitespace()) {
            if (' ' !== $tokens[$index + 1]->getContent()) {
                $tokens[$index + 1] = new \PhpCsFixer\Tokenizer\Token([\T_WHITESPACE, ' ']);
            }
        } else {
            $tokens->insertAt($index + 1, new \PhpCsFixer\Tokenizer\Token([\T_WHITESPACE, ' ']));
        }
        if ($tokens[$index - 1]->isWhitespace()) {
            if (' ' !== $tokens[$index - 1]->getContent() && !$tokens[$tokens->getPrevNonWhitespace($index - 1)]->isComment()) {
                $tokens[$index - 1] = new \PhpCsFixer\Tokenizer\Token([\T_WHITESPACE, ' ']);
            }
        } else {
            $tokens->insertAt($index, new \PhpCsFixer\Tokenizer\Token([\T_WHITESPACE, ' ']));
        }
    }
    /**
     * @param int $index of `=` token
     * @return void
     */
    private function removeWhitespaceAroundToken(\PhpCsFixer\Tokenizer\Tokens $tokens, int $index)
    {
        if (!$tokens[$tokens->getPrevNonWhitespace($index)]->isComment()) {
            $tokens->removeLeadingWhitespace($index);
        }
        $tokens->removeTrailingWhitespace($index);
    }
}
