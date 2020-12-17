<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);
namespace Magento\PricingStorefrontConfig\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Setup\Declaration\Schema\Diff\SchemaDiff;
use Magento\Framework\Setup\Declaration\Schema\OperationsExecutor;
use Magento\Framework\Setup\Declaration\Schema\SchemaConfigInterface;


/**
 * Command for pricing service minimum config set up
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DbUpgrade extends Command
{
    /**
     * Command name
     *
     * @var string
     */
    private const COMMAND_NAME = 'storefront:pricing:db-upgrade';

    /**
     * @var SchemaConfigInterface
     */
    private $schemaConfig;

    /**
     * @var SchemaDiff
     */
    private $schemaDiff;

    /**
     * @var OperationsExecutor
     */
    private $operationsExecutor;

    /**
     * Installer constructor.
     * @param SchemaConfigInterface $schemaConfig
     * @param SchemaDiff            $schemaDiff
     * @param OperationsExecutor    $operationsExecutor
     */
    public function __construct(
        SchemaConfigInterface $schemaConfig,
        SchemaDiff $schemaDiff,
        OperationsExecutor $operationsExecutor
    ) {
        parent::__construct();
        $this->schemaConfig = $schemaConfig;
        $this->schemaDiff = $schemaDiff;
        $this->operationsExecutor = $operationsExecutor;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(
                'Upgrades db to'
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {
            $this->installSchema();
        } catch (\Throwable $exception) {
            $output->writeln('Installation failed: ' . $exception->getMessage());
            return Cli::RETURN_FAILURE;
        }
        $output->writeln('Installation complete');

        return Cli::RETURN_SUCCESS;
    }

    /**
     * Install Schema in declarative way.
     *
     * @param array $requestData -> Data params which comes from UI or from CLI.
     * @return void
     */
    public function installSchema(array $requestData = []): void
    {
        $declarativeSchema = $this->schemaConfig->getDeclarationConfig();
        $dbSchema = $this->schemaConfig->getDbConfig();
        $diff = $this->schemaDiff->diff($declarativeSchema, $dbSchema);
        $this->operationsExecutor->execute($diff, $requestData);
    }
}
