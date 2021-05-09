<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix20210509\Symfony\Component\VarDumper\Dumper\ContextProvider;

use ECSPrefix20210509\Symfony\Component\HttpKernel\Debug\FileLinkFormatter;
use ECSPrefix20210509\Symfony\Component\VarDumper\Cloner\VarCloner;
use ECSPrefix20210509\Symfony\Component\VarDumper\Dumper\HtmlDumper;
use ECSPrefix20210509\Symfony\Component\VarDumper\VarDumper;
use ECSPrefix20210509\Twig\Template;
/**
 * Tries to provide context from sources (class name, file, line, code excerpt, ...).
 *
 * @author Nicolas Grekas <p@tchwork.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
final class SourceContextProvider implements \ECSPrefix20210509\Symfony\Component\VarDumper\Dumper\ContextProvider\ContextProviderInterface
{
    private $limit;
    private $charset;
    private $projectDir;
    private $fileLinkFormatter;
    /**
     * @param string $charset
     * @param string $projectDir
     * @param int $limit
     */
    public function __construct($charset = null, $projectDir = null, \ECSPrefix20210509\Symfony\Component\HttpKernel\Debug\FileLinkFormatter $fileLinkFormatter = null, $limit = 9)
    {
        $limit = (int) $limit;
        $this->charset = $charset;
        $this->projectDir = $projectDir;
        $this->fileLinkFormatter = $fileLinkFormatter;
        $this->limit = $limit;
    }
    /**
     * @return mixed[]|null
     */
    public function getContext()
    {
        $trace = \debug_backtrace(\DEBUG_BACKTRACE_PROVIDE_OBJECT | \DEBUG_BACKTRACE_IGNORE_ARGS, $this->limit);
        $file = $trace[1]['file'];
        $line = $trace[1]['line'];
        $name = \false;
        $fileExcerpt = \false;
        for ($i = 2; $i < $this->limit; ++$i) {
            if (isset($trace[$i]['class'], $trace[$i]['function']) && 'dump' === $trace[$i]['function'] && \ECSPrefix20210509\Symfony\Component\VarDumper\VarDumper::class === $trace[$i]['class']) {
                $file = isset($trace[$i]['file']) ? $trace[$i]['file'] : $file;
                $line = isset($trace[$i]['line']) ? $trace[$i]['line'] : $line;
                while (++$i < $this->limit) {
                    if (isset($trace[$i]['function'], $trace[$i]['file']) && empty($trace[$i]['class']) && 0 !== \strpos($trace[$i]['function'], 'call_user_func')) {
                        $file = $trace[$i]['file'];
                        $line = $trace[$i]['line'];
                        break;
                    } elseif (isset($trace[$i]['object']) && $trace[$i]['object'] instanceof \ECSPrefix20210509\Twig\Template) {
                        $template = $trace[$i]['object'];
                        $name = $template->getTemplateName();
                        $src = \method_exists($template, 'getSourceContext') ? $template->getSourceContext()->getCode() : (\method_exists($template, 'getSource') ? $template->getSource() : \false);
                        $info = $template->getDebugInfo();
                        if (isset($info[$trace[$i - 1]['line']])) {
                            $line = $info[$trace[$i - 1]['line']];
                            $file = \method_exists($template, 'getSourceContext') ? $template->getSourceContext()->getPath() : null;
                            if ($src) {
                                $src = \explode("\n", $src);
                                $fileExcerpt = [];
                                for ($i = \max($line - 3, 1), $max = \min($line + 3, \count($src)); $i <= $max; ++$i) {
                                    $fileExcerpt[] = '<li' . ($i === $line ? ' class="selected"' : '') . '><code>' . $this->htmlEncode($src[$i - 1]) . '</code></li>';
                                }
                                $fileExcerpt = '<ol start="' . \max($line - 3, 1) . '">' . \implode("\n", $fileExcerpt) . '</ol>';
                            }
                        }
                        break;
                    }
                }
                break;
            }
        }
        if (\false === $name) {
            $name = \str_replace('\\', '/', $file);
            $name = \substr($name, \strrpos($name, '/') + 1);
        }
        $context = ['name' => $name, 'file' => $file, 'line' => $line];
        $context['file_excerpt'] = $fileExcerpt;
        if (null !== $this->projectDir) {
            $context['project_dir'] = $this->projectDir;
            if (0 === \strpos($file, $this->projectDir)) {
                $context['file_relative'] = \ltrim(\substr($file, \strlen($this->projectDir)), \DIRECTORY_SEPARATOR);
            }
        }
        if ($this->fileLinkFormatter && ($fileLink = $this->fileLinkFormatter->format($context['file'], $context['line']))) {
            $context['file_link'] = $fileLink;
        }
        return $context;
    }
    /**
     * @param string $s
     * @return string
     */
    private function htmlEncode($s)
    {
        $s = (string) $s;
        $html = '';
        $dumper = new \ECSPrefix20210509\Symfony\Component\VarDumper\Dumper\HtmlDumper(function ($line) use(&$html) {
            $html .= $line;
        }, $this->charset);
        $dumper->setDumpHeader('');
        $dumper->setDumpBoundaries('', '');
        $cloner = new \ECSPrefix20210509\Symfony\Component\VarDumper\Cloner\VarCloner();
        $dumper->dump($cloner->cloneVar($s));
        return \substr(\strip_tags($html), 1, -1);
    }
}