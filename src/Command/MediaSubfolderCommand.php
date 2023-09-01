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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MediaSubfolderCommand extends Command
{
    protected static $defaultName = 'app:media:implement-subfolders';
    protected static $defaultDescription = "Déplace les fichiers images dans des sous-dossiers et met à jour le media";

    private $entityManager;
    private $params;

    public function __construct(EntityManagerInterface $em, ParameterBagInterface $params)
    {
        parent::__construct();
        $this->entityManager = $em;
        $this->params = $params;
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

        $dir = $this->params->get('img_dir');

        $repository = $this->entityManager->getRepository(Media::class);
        // $medias = $repository->findAll();
        $medias = $repository->findBy(array('id' => 973));

        foreach($medias as $media){
            $io->comment('updates media '.$media->getId());
            // par défaut, le media est dans le folder "non classé"
            if(!$media->getFolder()){
                // $media->setFolder('Non classé');
            }

            /**********************************/
            /* déplacer les images du fichier */
            /**********************************/
            $id = $media->getId();
            $subDir = floor($id / 500);

            $newDir = $dir.DIRECTORY_SEPARATOR.$subDir;

            // créer le sous-dossier
            if(!is_dir($newDir)){
                mkdir($newDir);
            }

            //deplacer l'image
            rename(
                $dir.DIRECTORY_SEPARATOR.$media->getFile(), 
                $newDir.DIRECTORY_SEPARATOR.$media->getFile()
            );

            //deplacer la vignette
            rename(
                $dir.DIRECTORY_SEPARATOR.'thumb_'.$media->getFile(), 
                $newDir.DIRECTORY_SEPARATOR.'thumb_'.$media->getFile()
            );

            $media->setThumb($subDir.DIRECTORY_SEPARATOR.'thumb_'.$media->getFile());
            $media->setFile($subDir.DIRECTORY_SEPARATOR.$media->getFile());
            
            $this->entityManager->persist($media);
        }
        $this->entityManager->flush();

        $io->success('Medias were upated');

        return Command::SUCCESS;
    }
}
