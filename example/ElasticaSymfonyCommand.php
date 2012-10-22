<?php
namespace Example;

use Symfony\Component\Console\Helper\DialogHelper;
use Elastica_Client;
use FF\ElasticaManager\ElasticaIndexManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class ElasticaCommand extends Command
{
	const CONFIG_ITEMS = 'shop';

	const ACTION_CREATE   = 'create';
	const ACTION_RECREATE = 'recreate';
	const ACTION_POPULATE = 'populate';
	const ACTION_ROTATE   = 'rotate';
	const ACTION_COPY     = 'copy';

	private $action;
	private $configName;
	private $indexName;
	private $actions = array(
		self::ACTION_CREATE,
		self::ACTION_RECREATE,
		self::ACTION_POPULATE,
		self::ACTION_ROTATE,
		self::ACTION_COPY
	);
	private $configs = array(self::CONFIG_ITEMS, self::CONFIG_TEST);


	/** @var ElasticaIndexManager */
	private $indexManager;

	/** @var InputInterface */
	protected $input;

	/** @var OutputInterface */
	protected $output;

	public function configure()
	{
		$this
			->setName('entora:elastica')
			->setDescription('Elastica index manager')
			->addArgument('action', InputArgument::REQUIRED, 'Index action: ['.implode(', ', $this->actions).']')
			->addArgument('configuration', InputArgument::OPTIONAL, 'Index configuration name: ['.implode(', ', $this->configs).']', self::CONFIG_ITEMS)
			->addArgument('index', InputArgument::OPTIONAL, 'Specify index name. By default uses index name specified in configuration')
			->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit for action=copy');
	}

	protected function validateArgument($argumentName, array $availableValues)
	{
		$argumentValue = $this->getInput()->getArgument($argumentName);
		if (!in_array($argumentValue, $availableValues)) {
			throw new \InvalidArgumentException("Argument $argumentName=$argumentValue not supported. Available values: [".implode(', ', $availableValues)."]");
		}
		return $argumentValue;
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		$this->input  = $input;
		$this->output = $output;

		$this->action     = $this->validateArgument('action', $this->actions);
		$this->configName = $this->validateArgument('configuration', $this->configs);
		$this->indexName  = $input->getArgument('index');

		$this->elasticaManager = $this->getElasticaIndexManager($this->configName);

		$output->writeln("<info>Starting elasticsearch <comment>{$this->action}</comment> action for <comment>{$this->configName}</comment> configuration".($this->indexName ? ' using <comment>'.$this->indexName.'</comment> index name' : '')."...</info>");

		$execMethod = 'execute'.ucfirst($this->action);
		$this->{$execMethod}();
	}

	protected function getElasticaIndexManager($configurationName)
	{
		$client        = new Elastica_Client(array(
			'servers' => array(
				array(
					'host' => '192.168.0.223',
					'port' => 9200
				)
			)
		));
		$configuration = new ShopIndexConfiguration();
		$provider      = new ShopIndexDataProvider();
		return new ElasticaIndexManager($client, $configuration, $provider);
	}

	public function executeCreate()
	{
		$this->elasticaManager->createIndex($this->configName, $this->indexName);
		/*$this->index->create();
		$this->index->populate(null, function ($n, $total) {
			if ($n % 100 == 0) {
				$this->writeAverageTimer($n, $total);
			}
		});
		$this->index->addAlias();*/
	}

	public function executeRecreate()
	{
		$this->elasticaManager->createIndex($this->configName, $this->indexName, true);
	}

	public function executePopulate()
	{
		$closure = function ($n, $total) {
			if ($n % 100 == 0) {
				$this->writeAverageTimer($n, $total);
			}
		};

		$this->elasticaManager->populate($this->configName, $this->indexName, $closure);
		/*$this->index->create();
		$this->index->populate(null, );
		$this->index->addAlias();*/
	}

	public function executeRotate()
	{
		/** @var $dialog DialogHelper */
		$dialog  = $this->getHelper('dialog');
		$confirm = $dialog->askConfirmation($this->getOutput(), '<question>All existing data will be replaced in live manner without index rotation. Are you sure?</question> ', false);
		if (!$confirm) {
			$this->getOutput()->writeln('<comment>Exiting</comment>');
			exit;
		}

		$this->index->create();
		$this->index->populate();
		$this->index->addAlias();
	}

	public function executeUpdate()
	{
		$timeStart  = $this->benchmarkStart();
		$optionIdBg = $this->getInput()->getOption('idbg');
		$id         = (int)$this->getInput()->getOption('id');
		if (!$id) {
			throw new \Exception("For action=update --id option must be specified");
		}

		$this->writeln("<info>Adding <comment>".($optionIdBg ? 'background' : 'live')."</comment> elasticsearch update job for id: <comment>$id</comment></info>");

		/** @var $app Application */
		$app = $this->getSilexApplication();
		$gm  = $app->getGearmanManager();

		if (!$optionIdBg) {
			$this->writeln("<info>Waiting for job response...");
		}

		$gearmanResponse = $gm->doElasticaUpdateOne($id, (bool)$optionIdBg, GearmanClient::PRIORITY_HIGH);

		$time = $this->benchmark($timeStart);
		if ($optionIdBg) {
			if (strpos($gearmanResponse, 'H:') === 0) {
				$this->writeln("Gearman job added successfully: <comment>$gearmanResponse</comment>  $time  Exiting");
			} else {
				$this->writeln("<info>Gearman job error: <error>$gearmanResponse</error> Exiting</info>");
			}
			return;
		}

		$this->writeln("Gearman response received: <info>$gearmanResponse</info> $time Exiting");
	}

	public function executeCopy()
	{
		$this->index->copy(function ($n, $total) {
			if ($n % 1000 == 0) {
				$this->writeAverageTimer($n, $total);
			}
		}, $this->getInput()->getOption('limit'));
		//$this->index->populate();
		$this->index->addAlias('items_temp');
	}
}
