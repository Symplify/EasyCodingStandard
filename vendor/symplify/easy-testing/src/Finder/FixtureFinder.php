<?php

declare (strict_types=1);
namespace ECSPrefix20210715\Symplify\EasyTesting\Finder;

use ECSPrefix20210715\Symfony\Component\Finder\Finder;
use ECSPrefix20210715\Symplify\SmartFileSystem\Finder\FinderSanitizer;
use ECSPrefix20210715\Symplify\SmartFileSystem\SmartFileInfo;
final class FixtureFinder
{
    /**
     * @var \Symplify\SmartFileSystem\Finder\FinderSanitizer
     */
    private $finderSanitizer;
    public function __construct(\ECSPrefix20210715\Symplify\SmartFileSystem\Finder\FinderSanitizer $finderSanitizer)
    {
        $this->finderSanitizer = $finderSanitizer;
    }
    /**
     * @return SmartFileInfo[]
     */
    public function find(array $sources) : array
    {
        $finder = new \ECSPrefix20210715\Symfony\Component\Finder\Finder();
        $finder->files()->in($sources)->name('*.php.inc')->path('Fixture')->sortByName();
        return $this->finderSanitizer->sanitize($finder);
    }
}
