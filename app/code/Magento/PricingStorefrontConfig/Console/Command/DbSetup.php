<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);
namespace Magento\PricingStorefrontConfig\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\PricingStorefront\Model\PriceBookRepository;
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
class DbSetup extends Command
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
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param SchemaConfigInterface $schemaConfig
     * @param SchemaDiff $schemaDiff
     * @param OperationsExecutor $operationsExecutor
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        SchemaConfigInterface $schemaConfig,
        SchemaDiff $schemaDiff,
        OperationsExecutor $operationsExecutor,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct();
        $this->schemaConfig = $schemaConfig;
        $this->schemaDiff = $schemaDiff;
        $this->operationsExecutor = $operationsExecutor;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(
                'Set up DB tables for Pricing Service'
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->installSchema();
            $this->createDefaultPriceBook();
        } catch (\Throwable $exception) {
            $output->writeln('Installation failed: ' . $exception->getMessage());
            return Cli::RETURN_FAILURE;
        }
        $output->writeln('DB Setup Complete');

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


    /**
     * Save default price book to database if not exists
     */
    private function createDefaultPriceBook() :void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName(PriceBookRepository::PRICES_BOOK_TABLE_NAME);

        $select = $connection->select()
            ->from($table)
            ->where('id = ?', PriceBookRepository::DEFAULT_PRICE_BOOK_ID);
        $result = $connection->fetchRow($select);
        if (!$result) {
            $connection->insert(
                $table,
                [
                    'id' => PriceBookRepository::DEFAULT_PRICE_BOOK_ID,
                    'name' => 'Default Price Book'
                ]
            );
        }
    }
}
