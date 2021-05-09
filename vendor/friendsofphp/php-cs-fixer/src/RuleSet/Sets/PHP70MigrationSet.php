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

use PhpCsFixer\RuleSet\AbstractRuleSetDescription;
/**
 * @internal
 */
final class PHP70MigrationSet extends \PhpCsFixer\RuleSet\AbstractRuleSetDescription
{
    /**
     * @return mixed[]
     */
    public function getRules()
    {
        return ['@PHP54Migration' => \true, 'ternary_to_null_coalescing' => \true];
    }
    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Rules to improve code for PHP 7.0 compatibility.';
    }
}