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
namespace PhpCsFixer\RuleSet\Sets;

use PhpCsFixer\Fixer\PhpUnit\PhpUnitTargetVersion;
use PhpCsFixer\RuleSet\AbstractRuleSetDescription;
/**
 * @internal
 */
final class PHPUnit50MigrationRiskySet extends \PhpCsFixer\RuleSet\AbstractRuleSetDescription
{
    /**
     * @return mixed[]
     */
    public function getRules()
    {
        return ['@PHPUnit48Migration:risky' => \true, 'php_unit_dedicate_assert' => ['target' => \PhpCsFixer\Fixer\PhpUnit\PhpUnitTargetVersion::VERSION_5_0]];
    }
    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Rules to improve tests code for PHPUnit 5.0 compatibility.';
    }
}
