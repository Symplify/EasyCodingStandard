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

namespace PhpCsFixer\Differ;

use PhpCsFixer\Diff\Differ;
use PhpCsFixer\Diff\Output\StrictUnifiedDiffOutputBuilder;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class FullDiffer implements DifferInterface
{
    /**
     * @var Differ
     */
    private $differ;

    public function __construct()
    {
        $this->differ = new Differ(new StrictUnifiedDiffOutputBuilder([
            'collapseRanges' => false,
            'commonLineThreshold' => 100,
            'contextLines' => 100,
            'fromFile' => 'Original',
            'toFile' => 'New',
        ]));
    }

    /**
     * {@inheritdoc}
     * @param \SplFileInfo|null $file
     * @param string $old
     * @param string $new
     * @return string
     */
    public function diff($old, $new, $file = null)
    {
        $old = (string) $old;
        $new = (string) $new;
        return $this->differ->diff($old, $new);
    }
}
