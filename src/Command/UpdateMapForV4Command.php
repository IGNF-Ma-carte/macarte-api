<?php

namespace App\Command;

use App\Entity\Map;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateMapForV4Command extends Command
{
    protected static $defaultName = 'app:map:update-for-v4';
    protected static $defaultDescription = "Ajoute la date de maj si vide";

    protected function configure(): void
    {
        // $this
        //     ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
        //     ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        // ;
    }

    private $entityManager;
    
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->entityManager = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = 5000;

        $repository = $this->entityManager->getRepository(Map::class);

        /*************************/
        /* ajoute la date de maj */
        /*************************/
        $maps = $repository->findBy(['updatedAt' => null], [], $limit);

        /** @var Map $map */
        foreach($maps as $map){
            $map->setUpdatedAt($map->getCreatedAt());
            $this->entityManager->persist($map);
        }
        $this->entityManager->flush();

        $io->success('Ajout date de maj des maps effectué');
        if(sizeof($maps) === $limit){
            $io-> caution('Relancer la commande pour traiter toutes les utilisateurs');
        }


        return Command::SUCCESS;
    }
}
