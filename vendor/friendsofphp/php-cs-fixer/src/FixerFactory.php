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
namespace PhpCsFixer;

use PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\RuleSet\RuleSetInterface;
use ECSPrefix20210509\Symfony\Component\Finder\Finder as SymfonyFinder;
use ECSPrefix20210509\Symfony\Component\Finder\SplFileInfo;
/**
 * Class provides a way to create a group of fixers.
 *
 * Fixers may be registered (made the factory aware of them) by
 * registering a custom fixer and default, built in fixers.
 * Then, one can attach Config instance to fixer instances.
 *
 * Finally factory creates a ready to use group of fixers.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class FixerFactory
{
    /**
     * @var FixerNameValidator
     */
    private $nameValidator;
    /**
     * @var FixerInterface[]
     */
    private $fixers = [];
    /**
     * @var FixerInterface[] Associative array of fixers with names as keys
     */
    private $fixersByName = [];
    public function __construct()
    {
        $this->nameValidator = new \PhpCsFixer\FixerNameValidator();
    }
    /**
     * @return $this
     */
    public function setWhitespacesConfig(\PhpCsFixer\WhitespacesFixerConfig $config)
    {
        foreach ($this->fixers as $fixer) {
            if ($fixer instanceof \PhpCsFixer\Fixer\WhitespacesAwareFixerInterface) {
                $fixer->setWhitespacesConfig($config);
            }
        }
        return $this;
    }
    /**
     * @return mixed[]
     */
    public function getFixers()
    {
        $this->fixers = \PhpCsFixer\Utils::sortFixers($this->fixers);
        return $this->fixers;
    }
    /**
     * @return $this
     */
    public function registerBuiltInFixers()
    {
        static $builtInFixers = null;
        if (null === $builtInFixers) {
            $builtInFixers = [];
            /** @var SplFileInfo $file */
            foreach (\ECSPrefix20210509\Symfony\Component\Finder\Finder::create()->files()->in(__DIR__ . '/Fixer')->depth(1) as $file) {
                $relativeNamespace = $file->getRelativePath();
                $fixerClass = 'PhpCsFixer\\Fixer\\' . ($relativeNamespace ? $relativeNamespace . '\\' : '') . $file->getBasename('.php');
                if ('Fixer' === \substr($fixerClass, -5)) {
                    $builtInFixers[] = $fixerClass;
                }
            }
        }
        foreach ($builtInFixers as $class) {
            $this->registerFixer(new $class(), \false);
        }
        return $this;
    }
    /**
     * @param FixerInterface[] $fixers
     *
     * @return $this
     */
    public function registerCustomFixers($fixers)
    {
        foreach ($fixers as $fixer) {
            $this->registerFixer($fixer, \true);
        }
        return $this;
    }
    /**
     * @return $this
     * @param bool $isCustom
     */
    public function registerFixer(\PhpCsFixer\Fixer\FixerInterface $fixer, $isCustom)
    {
        $isCustom = (bool) $isCustom;
        $name = $fixer->getName();
        if (isset($this->fixersByName[$name])) {
            throw new \UnexpectedValueException(\sprintf('Fixer named "%s" is already registered.', $name));
        }
        if (!$this->nameValidator->isValid($name, $isCustom)) {
            throw new \UnexpectedValueException(\sprintf('Fixer named "%s" has invalid name.', $name));
        }
        $this->fixers[] = $fixer;
        $this->fixersByName[$name] = $fixer;
        return $this;
    }
    /**
     * Apply RuleSet on fixers to filter out all unwanted fixers.
     *
     * @return $this
     */
    public function useRuleSet(\PhpCsFixer\RuleSet\RuleSetInterface $ruleSet)
    {
        $fixers = [];
        $fixersByName = [];
        $fixerConflicts = [];
        $fixerNames = \array_keys($ruleSet->getRules());
        foreach ($fixerNames as $name) {
            if (!\array_key_exists($name, $this->fixersByName)) {
                throw new \UnexpectedValueException(\sprintf('Rule "%s" does not exist.', $name));
            }
            $fixer = $this->fixersByName[$name];
            $config = $ruleSet->getRuleConfiguration($name);
            if (null !== $config) {
                if ($fixer instanceof \PhpCsFixer\Fixer\ConfigurableFixerInterface) {
                    if (!\is_array($config) || !\count($config)) {
                        throw new \PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException($fixer->getName(), 'Configuration must be an array and may not be empty.');
                    }
                    $fixer->configure($config);
                } else {
                    throw new \PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException($fixer->getName(), 'Is not configurable.');
                }
            }
            $fixers[] = $fixer;
            $fixersByName[$name] = $fixer;
            $conflicts = \array_intersect($this->getFixersConflicts($fixer), $fixerNames);
            if (\count($conflicts) > 0) {
                $fixerConflicts[$name] = $conflicts;
            }
        }
        if (\count($fixerConflicts) > 0) {
            throw new \UnexpectedValueException($this->generateConflictMessage($fixerConflicts));
        }
        $this->fixers = $fixers;
        $this->fixersByName = $fixersByName;
        return $this;
    }
    /**
     * Check if fixer exists.
     * @param string $name
     * @return bool
     */
    public function hasRule($name)
    {
        $name = (string) $name;
        return isset($this->fixersByName[$name]);
    }
    /**
     * @return mixed[]|null
     */
    private function getFixersConflicts(\PhpCsFixer\Fixer\FixerInterface $fixer)
    {
        static $conflictMap = ['no_blank_lines_before_namespace' => ['single_blank_line_before_namespace'], 'single_import_per_statement' => ['group_import']];
        $fixerName = $fixer->getName();
        return \array_key_exists($fixerName, $conflictMap) ? $conflictMap[$fixerName] : [];
    }
    /**
     * @param array<string, string[]> $fixerConflicts
     * @return string
     */
    private function generateConflictMessage(array $fixerConflicts)
    {
        $message = 'Rule contains conflicting fixers:';
        $report = [];
        foreach ($fixerConflicts as $fixer => $fixers) {
            // filter mutual conflicts
            $report[$fixer] = \array_filter($fixers, static function (string $candidate) use($report, $fixer) {
                return !\array_key_exists($candidate, $report) || !\in_array($fixer, $report[$candidate], \true);
            });
            if (\count($report[$fixer]) > 0) {
                $message .= \sprintf("\n- \"%s\" with \"%s\"", $fixer, \implode('", "', $report[$fixer]));
            }
        }
        return $message;
    }
}