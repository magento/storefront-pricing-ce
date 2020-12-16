<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);
namespace Magento\PricingStorefront\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use Magento\PricingStorefront\Model\PriceBookRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for service minimum config set up
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Init extends Command
{
    /**
     * Command name
     *
     * @var string
     */
    private const COMMAND_NAME = 'storefront:pricing:init';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * Installer constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        parent::__construct();
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(
                'Adds default pricebook to db'
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
            $this->createDefaultPriceBook();
        } catch (\Throwable $exception) {
            $output->writeln('Installation failed: ' . $exception->getMessage());
            return Cli::RETURN_FAILURE;
        }
        $output->writeln('Installation complete');
        return Cli::RETURN_SUCCESS;
    }

    /**
     * Save default price book to database if not exists
     */
    private function createDefaultPriceBook()
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
