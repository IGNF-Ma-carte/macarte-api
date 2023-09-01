<?php

namespace App\Command;

use DateTime;
use App\Entity\User;
use App\Service\RandomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateUserV4Command extends Command
{
    protected static $defaultName = 'app:user:update-for-v4';
    protected static $defaultDescription = "Ajoute un identifiant permanent autre que id aux utilisateurs";

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
        $randomService = new RandomService();
        $limit = 5000;

        $io = new SymfonyStyle($input, $output);

        $repository = $this->entityManager->getRepository(User::class);

        /***********************/
        /* ajoute le public_id */
        /***********************/

        $users = $repository->findBy(['publicId' => [null, '']], [], $limit);
        /** @var User $user */
        foreach($users as $user){
            $string = '';
            do{
                $string = $randomService->getRandomString(4);
            } while( $repository->findOneBy(['publicId' => $string]) );
            $user->setPublicId($string);
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();

        $io->success('Ajout public_id des utilisateurs effectué');
        if(sizeof($users) === $limit){
            $io->caution('Relancer la commande pour traiter tous les utilisateurs');
        }

        return Command::SUCCESS;
    }
}
