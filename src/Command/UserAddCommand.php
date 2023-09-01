<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\RandomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserAddCommand extends Command
{
    protected static $defaultName = 'app:user:add';
    protected static $defaultDescription = 'creates a new user';

    private $passwordHasher;
    private $repository;

    public function __construct(UserPasswordHasherInterface  $passwordHasher, UserRepository $repository)
    {
        parent::__construct();
        $this->passwordHasher = $passwordHasher;
        $this->repository = $repository;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'add username')
            ->addArgument('email', InputArgument::REQUIRED, 'add email')
            ->addArgument('password', InputArgument::REQUIRED, 'add password')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = new User();
        $user->setUsername($input->getArgument('username'));
        $user->setPublicName($input->getArgument('username'));
        $user->setEmail($input->getArgument('email'));
        $user->setSalt(rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '='));
        $user->setPassword($this->passwordHasher->hashPassword($user,$input->getArgument('password')));
        $user->setEnabled(true);

        $randomService = new RandomService();
        $string = '';
        do{
            $string = $randomService->getRandomString(4);
        } while( $this->repository->findOneBy(['publicId' => $string]) );
        $user->setPublicId($string);

        $this->repository->persist($user, true);
        
        $io->success("User is created, id = ".$user->getId().", public_id = " .$user->getPublicId());

        return Command::SUCCESS;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = array();
        
        if (!$input->getArgument('username')) {
            $question = new Question('Please choose a username : ');
            $question->setValidator(function ($username) {
                if (empty($username)) {
                    throw new \Exception('Username can not be empty');
                }

                return $username;
            });
            $questions['username'] = $question;
        }

        if (!$input->getArgument('email')) {
            $question = new Question('Please choose a email : ');
            $question->setValidator(function ($email) {
                if (empty($email)) {
                    throw new \Exception('Email can not be empty');
                }

                return $email;
            });
            $questions['email'] = $question;
        }

        if (!$input->getArgument('password')) {
            $question = new Question('Please choose a password : ');
            $question->setValidator(function ($password) {
                if (empty($password)) {
                    throw new \Exception('Password can not be empty');
                }

                return $password;
            });
            $question->setHidden(true);
            $questions['password'] = $question;
        }

        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument($name, $answer);
        }

    }
}
