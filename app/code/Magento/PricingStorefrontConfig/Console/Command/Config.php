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
use Magento\PricingStorefrontConfig\Model\Installer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for pricing service minimum config set up
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Config extends Command
{
    /**
     * Command name
     *
     * @var string
     */
    private const COMMAND_NAME = 'storefront:pricing:init';

    /**
     * @var Installer
     */
    private $installer;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param Installer $installer
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Installer $installer,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct();
        $this->installer = $installer;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritDoc
     */
    protected function configure() :void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(
                'Adds minimum required config data to env.php'
            )
            ->setDefinition($this->getOptionsList());

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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->checkOptions($input->getOptions());
        try {
            $this->installer->install(
                $input->getOptions()
            );
        } catch (\Throwable $exception) {
            $output->writeln('Installation failed: ' . $exception->getMessage());
            return Cli::RETURN_FAILURE;
        }
        $output->writeln('Config initialization complete');

        return Cli::RETURN_SUCCESS;
    }

    /**
     * Provides options for command config
     *
     * @return array
     */
    private function getOptionsList() :array
    {
        return [
            new InputOption(
                Installer::DB_HOST,
                null,
                InputOption::VALUE_REQUIRED,
                'Database hostname'
            ),
            new InputOption(
                Installer::DB_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Database name',
            ),
            new InputOption(
                Installer::DB_USER,
                null,
                InputOption::VALUE_REQUIRED,
                'Database user'
            ),
            new InputOption(
                Installer::DB_PASSWORD,
                null,
                InputOption::VALUE_REQUIRED,
                'Database password'
            ),
            new InputOption(
                Installer::DB_TABLE_PREFIX,
                null,
                InputOption::VALUE_OPTIONAL,
                'Database table prefix',
                ''
            )
        ];
    }

    /**
     * Checks if all options are set
     *
     * @param array $options
     * @return void
     * @throws LocalizedException
     */
    private function checkOptions(array $options) :void
    {
        $forgottenOptions = [];
        foreach ($options as $optionKey => $option) {
            if ($option === null) {
                $forgottenOptions[] = $optionKey;
            }
        }
        if (count($forgottenOptions) > 0) {
            throw new LocalizedException(
                __(
                    'Please provide next options: '.PHP_EOL.'%1',
                    implode(',' . PHP_EOL, $forgottenOptions)
                )
            );
        }
    }
}
