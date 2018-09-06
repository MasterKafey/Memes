<?php

namespace AppBundle\Command\Production;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    public function configure()
    {
        $this
            ->setName('app:production:install')
            ->setDescription('Install the project on the production server')
            ->addOption('drop-database', null, InputOption::VALUE_NONE, 'If you want to drop the current database to install new schemas')
            ->addOption('skip-composer', null, InputOption::VALUE_NONE, 'If you don\'t want to do the composer install')
            ->addOption('skip-git-pull', null, InputOption::VALUE_NONE, 'If you don\'t want to reset code')
            ->addOption('skip-chmod', null, InputOption::VALUE_NONE, 'If you don\'t want to update folder rights')
            ->addOption('skip-schema-update', null, InputOption::VALUE_NONE, 'If you don\'t want to update database schema');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Update of the project production');
        /* Update project from github */
        if (!$input->getOptions('skip-git-pull')) {
            $fetchAllProcess = new Process('git fetch --all');
            $fetchAllProcess->run();
            $this->forceProcessToBeSync($fetchAllProcess);

            $resetMaster = new Process('git reset --hard origin/master');
            $resetMaster->run();
            $this->forceProcessToBeSync($resetMaster);
        }

        /* Install vendor */
        if (!$input->getOptions('skip-composer')) {
            $composerInstall = new Process('composer install');
            $composerInstall->run();
            $this->forceProcessToBeSync($composerInstall);
        }

        /* Give write authorization in the project */
        if(!$input->getOptions('skip-chmod')) {
            $chmod = new Process('chmod -R 777 *');
            $chmod->run();
            $this->forceProcessToBeSync($chmod);
        }


        $application = new Application($this->container->get('kernel'));
        $application->setAutoExit(false);

        /* Drop database */
        if($input->getOptions('drop-database')) {
            $databaseDrop = new ArrayInput(array(
                'command' => 'doctrine:database:drop',
                '--force' => true,
            ));

            $databaseCreate = new ArrayInput(array(
                'command' => 'doctrine:database:create',
            ));
            $databaseSessionCreationTable = new ArrayInput(array(
                'command' => 'app:session:create_table',
            ));
            $application->run($databaseDrop, $output);
            $application->run($databaseCreate, $output);
            $application->run($databaseSessionCreationTable, $output);
        }

        if(!$input->getOptions('skip-schema-update')) {
            $databaseSchemaUpdate = new ArrayInput(array(
                'command' => 'doctrine:schema:update',
                '--force' => true,
            ));
            $application->run($databaseSchemaUpdate, $output);
        }

    }

    public function forceProcessToBeSync(Process $process)
    {
        while ($process->isRunning()) {
        }
        echo $process->getOutput();
    }
}
