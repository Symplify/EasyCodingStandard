<?php

namespace Symplify\CodingStandard\TokenRunner\DocBlock\MalformWorker;

use ECSPrefix20210509\Nette\Utils\Strings;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use Symplify\CodingStandard\TokenRunner\Contract\DocBlock\MalformWorkerInterface;
final class SuperfluousVarNameMalformWorker implements \Symplify\CodingStandard\TokenRunner\Contract\DocBlock\MalformWorkerInterface
{
    /**
     * @var string
     * @see https://regex101.com/r/euhrn8/1
     */
    const THIS_VARIABLE_REGEX = '#\\$this$#';
    /**
     * @var string
     * @see https://regex101.com/r/8LCnOl/1
     */
    const VAR_VARIABLE_NAME_REGEX = '#(?<tag>@var)(?<type>\\s+[|\\\\\\w]+)?(\\s+)(?<propertyName>\\$[\\w]+)#';
    /**
     * @param Tokens<Token> $tokens
     * @param string $docContent
     * @param int $position
     * @return string
     */
    public function work($docContent, \PhpCsFixer\Tokenizer\Tokens $tokens, $position)
    {
        $docContent = (string) $docContent;
        $position = (int) $position;
        if ($this->shouldSkip($tokens, $position)) {
            return $docContent;
        }
        $docBlock = new \PhpCsFixer\DocBlock\DocBlock($docContent);
        $lines = $docBlock->getLines();
        foreach ($lines as $line) {
            $match = \ECSPrefix20210509\Nette\Utils\Strings::match($line->getContent(), self::VAR_VARIABLE_NAME_REGEX);
            if ($match === null) {
                continue;
            }
            $newLineContent = \ECSPrefix20210509\Nette\Utils\Strings::replace($line->getContent(), self::VAR_VARIABLE_NAME_REGEX, function (array $match) : string {
                $replacement = $match['tag'];
                if ($match['type'] !== []) {
                    $replacement .= $match['type'];
                }
                if (\ECSPrefix20210509\Nette\Utils\Strings::match($match['propertyName'], self::THIS_VARIABLE_REGEX)) {
                    return $match['tag'] . ' self';
                }
                return $replacement;
            });
            $line->setContent($newLineContent);
        }
        return $docBlock->getContent();
    }
    /**
     * Is property doc block?
     *
     * @param Tokens<Token> $tokens
     * @param int $position
     * @return bool
     */
    private function shouldSkip(\PhpCsFixer\Tokenizer\Tokens $tokens, $position)
    {
        $position = (int) $position;
        $nextMeaningfulTokenPosition = $tokens->getNextMeaningfulToken($position);
        // nothing to change
        if ($nextMeaningfulTokenPosition === null) {
            return \true;
        }
        /** @var Token $nextMeaningfulToken */
        $nextMeaningfulToken = $tokens[$nextMeaningfulTokenPosition];
        // should be protected/private/public/static, to know we're property
        return !$nextMeaningfulToken->isGivenKind([\T_PUBLIC, \T_PROTECTED, \T_PRIVATE, \T_STATIC]);
    }
}