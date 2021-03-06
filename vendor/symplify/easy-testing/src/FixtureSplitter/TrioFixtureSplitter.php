<?php

declare (strict_types=1);
namespace ECSPrefix20210715\Symplify\EasyTesting\FixtureSplitter;

use ECSPrefix20210715\Nette\Utils\Strings;
use ECSPrefix20210715\Symplify\EasyTesting\ValueObject\FixtureSplit\TrioContent;
use ECSPrefix20210715\Symplify\EasyTesting\ValueObject\SplitLine;
use ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo;
use ECSPrefix20210715\Symplify\SymplifyKernel\Exception\ShouldNotHappenException;
final class TrioFixtureSplitter
{
    public function splitFileInfo(\ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo $smartFileInfo) : \ECSPrefix20210715\Symplify\EasyTesting\ValueObject\FixtureSplit\TrioContent
    {
        $parts = \ECSPrefix20210715\Nette\Utils\Strings::split($smartFileInfo->getContents(), \ECSPrefix20210715\Symplify\EasyTesting\ValueObject\SplitLine::SPLIT_LINE_REGEX);
        $this->ensureHasThreeParts($parts, $smartFileInfo);
        return new \ECSPrefix20210715\Symplify\EasyTesting\ValueObject\FixtureSplit\TrioContent($parts[0], $parts[1], $parts[2]);
    }
    /**
     * @param mixed[] $parts
     * @return void
     */
    private function ensureHasThreeParts(array $parts, \ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo $smartFileInfo)
    {
        if (\count($parts) === 3) {
            return;
        }
        $message = \sprintf('The fixture "%s" should have 3 parts. %d found', $smartFileInfo->getRelativeFilePathFromCwd(), \count($parts));
        throw new \ECSPrefix20210715\Symplify\SymplifyKernel\Exception\ShouldNotHappenException($message);
    }
}
