<?php

declare (strict_types=1);
namespace ECSPrefix20210715\Symplify\EasyTesting\ValueObject;

use ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo;
use ECSPrefix20210715\Symplify\SymplifyKernel\Exception\ShouldNotHappenException;
final class ExpectedAndOutputFileInfoPair
{
    /**
     * @var \Symplify\SmartFileSystem\SmartFileInfo
     */
    private $expectedFileInfo;
    /**
     * @var \Symplify\SmartFileSystem\SmartFileInfo|null
     */
    private $outputFileInfo;
    /**
     * @param \Symplify\SmartFileSystem\SmartFileInfo|null $outputFileInfo
     */
    public function __construct(\ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo $expectedFileInfo, $outputFileInfo)
    {
        $this->expectedFileInfo = $expectedFileInfo;
        $this->outputFileInfo = $outputFileInfo;
    }
    /**
     * @noRector \Rector\Privatization\Rector\ClassMethod\PrivatizeLocalOnlyMethodRector
     */
    public function getExpectedFileContent() : string
    {
        return $this->expectedFileInfo->getContents();
    }
    /**
     * @noRector \Rector\Privatization\Rector\ClassMethod\PrivatizeLocalOnlyMethodRector
     */
    public function getOutputFileContent() : string
    {
        if (!$this->outputFileInfo instanceof \ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo) {
            throw new \ECSPrefix20210715\Symplify\SymplifyKernel\Exception\ShouldNotHappenException();
        }
        return $this->outputFileInfo->getContents();
    }
    /**
     * @noRector \Rector\Privatization\Rector\ClassMethod\PrivatizeLocalOnlyMethodRector
     */
    public function doesOutputFileExist() : bool
    {
        return $this->outputFileInfo !== null;
    }
}
