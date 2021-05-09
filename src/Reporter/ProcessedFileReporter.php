<?php

namespace Symplify\EasyCodingStandard\Reporter;

use Symplify\EasyCodingStandard\Configuration\Configuration;
use Symplify\EasyCodingStandard\Console\Output\OutputFormatterCollector;
use Symplify\EasyCodingStandard\Error\ErrorAndDiffResultFactory;

final class ProcessedFileReporter
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var OutputFormatterCollector
     */
    private $outputFormatterCollector;

    /**
     * @var ErrorAndDiffResultFactory
     */
    private $errorAndDiffResultFactory;

    public function __construct(
        Configuration $configuration,
        OutputFormatterCollector $outputFormatterCollector,
        ErrorAndDiffResultFactory $errorAndDiffResultFactory
    ) {
        $this->configuration = $configuration;
        $this->outputFormatterCollector = $outputFormatterCollector;
        $this->errorAndDiffResultFactory = $errorAndDiffResultFactory;
    }

    /**
     * @param int $processedFileCount
     * @return int
     */
    public function report($processedFileCount)
    {
        $processedFileCount = (int) $processedFileCount;
        $outputFormat = $this->configuration->getOutputFormat();
        $outputFormatter = $this->outputFormatterCollector->getByName($outputFormat);

        $errorAndDiffResult = $this->errorAndDiffResultFactory->create();
        return $outputFormatter->report($errorAndDiffResult, $processedFileCount);
    }
}
