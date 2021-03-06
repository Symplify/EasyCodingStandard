<?php

declare (strict_types=1);
namespace ECSPrefix20210715\Symplify\EasyTesting;

use ECSPrefix20210715\Nette\Utils\Strings;
use ECSPrefix20210715\Symplify\EasyTesting\ValueObject\InputAndExpected;
use ECSPrefix20210715\Symplify\EasyTesting\ValueObject\InputFileInfoAndExpected;
use ECSPrefix20210715\Symplify\EasyTesting\ValueObject\InputFileInfoAndExpectedFileInfo;
use ECSPrefix20210715\Symplify\EasyTesting\ValueObject\SplitLine;
use ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo;
use ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileSystem;
final class StaticFixtureSplitter
{
    /**
     * @var string|null
     */
    public static $customTemporaryPath;
    public static function splitFileInfoToInputAndExpected(\ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo $smartFileInfo) : \ECSPrefix20210715\Symplify\EasyTesting\ValueObject\InputAndExpected
    {
        $splitLineCount = \count(\ECSPrefix20210715\Nette\Utils\Strings::matchAll($smartFileInfo->getContents(), \ECSPrefix20210715\Symplify\EasyTesting\ValueObject\SplitLine::SPLIT_LINE_REGEX));
        // if more or less, it could be a test cases for monorepo line in it
        if ($splitLineCount === 1) {
            // input → expected
            list($input, $expected) = \ECSPrefix20210715\Nette\Utils\Strings::split($smartFileInfo->getContents(), \ECSPrefix20210715\Symplify\EasyTesting\ValueObject\SplitLine::SPLIT_LINE_REGEX);
            $expected = self::retypeExpected($expected);
            return new \ECSPrefix20210715\Symplify\EasyTesting\ValueObject\InputAndExpected($input, $expected);
        }
        // no changes
        return new \ECSPrefix20210715\Symplify\EasyTesting\ValueObject\InputAndExpected($smartFileInfo->getContents(), $smartFileInfo->getContents());
    }
    public static function splitFileInfoToLocalInputAndExpectedFileInfos(\ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo $smartFileInfo, bool $autoloadTestFixture = \false) : \ECSPrefix20210715\Symplify\EasyTesting\ValueObject\InputFileInfoAndExpectedFileInfo
    {
        $inputAndExpected = self::splitFileInfoToInputAndExpected($smartFileInfo);
        $inputFileInfo = self::createTemporaryFileInfo($smartFileInfo, 'input', $inputAndExpected->getInput());
        // some files needs to be autoload to enable reflection
        if ($autoloadTestFixture) {
            require_once $inputFileInfo->getRealPath();
        }
        $expectedFileInfo = self::createTemporaryFileInfo($smartFileInfo, 'expected', $inputAndExpected->getExpected());
        return new \ECSPrefix20210715\Symplify\EasyTesting\ValueObject\InputFileInfoAndExpectedFileInfo($inputFileInfo, $expectedFileInfo);
    }
    public static function getTemporaryPath() : string
    {
        if (self::$customTemporaryPath !== null) {
            return self::$customTemporaryPath;
        }
        return \sys_get_temp_dir() . '/_temp_fixture_easy_testing';
    }
    public static function createTemporaryFileInfo(\ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo $fixtureSmartFileInfo, string $prefix, string $fileContent) : \ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo
    {
        $temporaryFilePath = self::createTemporaryPathWithPrefix($fixtureSmartFileInfo, $prefix);
        $smartFileSystem = new \ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileSystem();
        $smartFileSystem->dumpFile($temporaryFilePath, $fileContent);
        return new \ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo($temporaryFilePath);
    }
    public static function splitFileInfoToLocalInputAndExpected(\ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo $smartFileInfo, bool $autoloadTestFixture = \false) : \ECSPrefix20210715\Symplify\EasyTesting\ValueObject\InputFileInfoAndExpected
    {
        $inputAndExpected = self::splitFileInfoToInputAndExpected($smartFileInfo);
        $inputFileInfo = self::createTemporaryFileInfo($smartFileInfo, 'input', $inputAndExpected->getInput());
        // some files needs to be autoload to enable reflection
        if ($autoloadTestFixture) {
            require_once $inputFileInfo->getRealPath();
        }
        return new \ECSPrefix20210715\Symplify\EasyTesting\ValueObject\InputFileInfoAndExpected($inputFileInfo, $inputAndExpected->getExpected());
    }
    private static function createTemporaryPathWithPrefix(\ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo $smartFileInfo, string $prefix) : string
    {
        $hash = \ECSPrefix20210715\Nette\Utils\Strings::substring(\md5($smartFileInfo->getRealPath()), -20);
        $fileBaseName = $smartFileInfo->getBasename('.inc');
        return self::getTemporaryPath() . \sprintf('/%s_%s_%s', $prefix, $hash, $fileBaseName);
    }
    /**
     * @return mixed|int|float
     */
    private static function retypeExpected($expected)
    {
        if (!\is_numeric(\trim($expected))) {
            return $expected;
        }
        // value re-type
        if (\strlen((string) (int) $expected) === \strlen(\trim($expected))) {
            return (int) $expected;
        }
        if (\strlen((string) (float) $expected) === \strlen(\trim($expected))) {
            return (float) $expected;
        }
        return $expected;
    }
}
