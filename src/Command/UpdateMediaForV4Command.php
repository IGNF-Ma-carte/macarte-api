<?php

namespace App\Command;

use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateMediaForV4Command extends Command
{
    protected static $defaultName = 'app:media:update-for-v4';
    protected static $defaultDescription = "Ajoute \"Non classé\" si folder non renseigné\n"
        .'Ajoute les composants du filename pour retrouver le media sans l\'extension';

    private $entityManager;
    
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->entityManager = $em;
    }
    protected function configure(): void
    {
        // $this
        //     ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
        //     ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        // ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = 1000;

        $repository = $this->entityManager->getRepository(Media::class);
        /** @var Media $media */

        /********************************/
        /* ajoute le dossier par défaut */
        /********************************/
        $medias = $repository->findBy(['folder' => ''], [], $limit);

        foreach($medias as $media){
            $media->setFolder('Non classé');
            $this->entityManager->persist($media);
        }
        $this->entityManager->flush();

        $io->success('dossier par défaut des medias effectué');
        if(sizeof($medias) === $limit){
            $io->caution('Relancer la commande pour traiter toutes les cartes');
        }

        /*****************************************************************************/
        /* ajoute les composants du filename pour retrouver le media sans l'extension*/
        /*****************************************************************************/
        $medias2 = $repository->findBy(['filename' => ['', null] ], [], $limit);
        
        foreach($medias2 as $media){
            $filenameComponents = explode('.', $media->getFile());
            $media->setFilename($filenameComponents[0]);
            if(isset($filenameComponents[1])){
                $media->setExtension($filenameComponents[1]);
            }
            $this->entityManager->persist($media);
        }
        $this->entityManager->flush();

        $io->success('recherche media sans extension des medias effectuée');
        if(sizeof($medias2) === $limit){
            $io->caution('Relancer la commande pour traiter toutes les cartes');
        }

        return Command::SUCCESS;
    }
}
