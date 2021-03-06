<?php

declare (strict_types=1);
namespace ECSPrefix20210715;

// decoupled in own "*.php" file, so ECS, Rector and PHPStan works out of the box here
use PHP_CodeSniffer\Util\Tokens;
use ECSPrefix20210715\Symfony\Component\Console\Input\ArgvInput;
use Symplify\EasyCodingStandard\Console\EasyCodingStandardConsoleApplication;
use Symplify\EasyCodingStandard\DependencyInjection\EasyCodingStandardContainerFactory;
use ECSPrefix20210715\Symplify\PackageBuilder\Console\ShellCode;
use ECSPrefix20210715\Symplify\PackageBuilder\Console\Style\SymfonyStyleFactory;
// performance boost
\gc_disable();
\define('__ECS_RUNNING__', \true);
# 1. autoload
$autoloadIncluder = new \ECSPrefix20210715\AutoloadIncluder();
if (\file_exists(__DIR__ . '/../preload.php')) {
    require_once __DIR__ . '/../preload.php';
}
$autoloadIncluder->includeCwdVendorAutoloadIfExists();
$autoloadIncluder->loadIfNotLoadedYet(__DIR__ . '/../vendor/scoper-autoload.php');
$autoloadIncluder->autoloadProjectAutoloaderFile('/../../autoload.php');
$autoloadIncluder->includeDependencyOrRepositoryVendorAutoloadIfExists();
$autoloadIncluder->includePhpCodeSnifferAutoloadIfNotInPharAndInitliazeTokens();
try {
    $input = new \ECSPrefix20210715\Symfony\Component\Console\Input\ArgvInput();
    $ecsContainerFactory = new \Symplify\EasyCodingStandard\DependencyInjection\EasyCodingStandardContainerFactory();
    $container = $ecsContainerFactory->createFromFromInput($input);
} catch (\Throwable $throwable) {
    $symfonyStyleFactory = new \ECSPrefix20210715\Symplify\PackageBuilder\Console\Style\SymfonyStyleFactory();
    $symfonyStyle = $symfonyStyleFactory->create();
    $symfonyStyle->error($throwable->getMessage());
    exit(\ECSPrefix20210715\Symplify\PackageBuilder\Console\ShellCode::ERROR);
}
$application = $container->get(\Symplify\EasyCodingStandard\Console\EasyCodingStandardConsoleApplication::class);
exit($application->run());
/**
 * Inspired by https://github.com/rectorphp/rector/pull/2373/files#diff-0fc04a2bb7928cac4ae339d5a8bf67f3
 */
final class AutoloadIncluder
{
    /**
     * @var string[]
     */
    private $alreadyLoadedAutoloadFiles = [];
    /**
     * @return void
     */
    public function includeCwdVendorAutoloadIfExists()
    {
        $cwdVendorAutoload = \getcwd() . '/vendor/autoload.php';
        if (!\is_file($cwdVendorAutoload)) {
            return;
        }
        $this->loadIfNotLoadedYet($cwdVendorAutoload);
    }
    /**
     * @return void
     */
    public function includeDependencyOrRepositoryVendorAutoloadIfExists()
    {
        // ECS' vendor is already loaded
        if (\class_exists('\\Symplify\\EasyCodingStandard\\HttpKernel\\EasyCodingStandardKernel')) {
            return;
        }
        $devVendorAutoload = __DIR__ . '/../vendor/autoload.php';
        if (!\is_file($devVendorAutoload)) {
            return;
        }
        $this->loadIfNotLoadedYet($devVendorAutoload);
    }
    /**
     * @return void
     */
    public function autoloadProjectAutoloaderFile(string $file)
    {
        $path = \dirname(__DIR__) . $file;
        if (!\is_file($path)) {
            return;
        }
        $this->loadIfNotLoadedYet($path);
    }
    /**
     * @return void
     */
    public function includePhpCodeSnifferAutoloadIfNotInPharAndInitliazeTokens()
    {
        // file is autoloaded with classmap in PHAR
        // without phar, we still need to autoload it
        # 1. autoload
        $possibleAutoloadPaths = [
            // after split package
            __DIR__ . '/../vendor',
            // dependency
            __DIR__ . '/../../..',
            // monorepo
            __DIR__ . '/../../../vendor',
        ];
        foreach ($possibleAutoloadPaths as $possibleAutoloadPath) {
            $possiblePhpCodeSnifferAutoloadPath = $possibleAutoloadPath . '/squizlabs/php_codesniffer/autoload.php';
            if (!\is_file($possiblePhpCodeSnifferAutoloadPath)) {
                continue;
            }
            require_once $possiblePhpCodeSnifferAutoloadPath;
        }
        // initalize token with INT type, otherwise php-cs-fixer and php-parser breaks
        if (\defined('T_MATCH') === \false) {
            \define('T_MATCH', 5000);
        }
        new \PHP_CodeSniffer\Util\Tokens();
    }
    /**
     * @return void
     */
    public function loadIfNotLoadedYet(string $file)
    {
        if (!\file_exists($file)) {
            return;
        }
        if (\in_array($file, $this->alreadyLoadedAutoloadFiles, \true)) {
            return;
        }
        $this->alreadyLoadedAutoloadFiles[] = \realpath($file);
        require_once $file;
    }
}
/**
 * Inspired by https://github.com/rectorphp/rector/pull/2373/files#diff-0fc04a2bb7928cac4ae339d5a8bf67f3
 */
\class_alias('ECSPrefix20210715\\AutoloadIncluder', 'AutoloadIncluder', \false);
