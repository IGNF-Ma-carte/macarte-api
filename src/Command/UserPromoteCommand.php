<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserPromoteCommand extends Command
{
    protected static $defaultName = 'app:user:promote';
    protected static $defaultDescription = 'adds a role to user';

    private $repository;

    public function __construct(UserRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'add username')
            ->addArgument('role', InputArgument::REQUIRED, 'add the role to user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $role = $input->getArgument('role');

        /** @var User $user */
        $user = $this->repository->findOneByUsername($username);
        if(!$user){
            $io->error("user not found");
            return Command::FAILURE;
        }

        $roles = $user->getRoles();
        $roles[] = $role;

        $user->setRoles($roles);
        $this->repository->persist($user, true);

        $io->success($username . ' granted ' . $role);

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

        if (!$input->getArgument('role')) {
            $question = new Question('Please choose a role (ROLE_XXX) : ');
            $question->setValidator(function ($email) {
                if (empty($email)) {
                    throw new \Exception('Role can not be empty');
                }

                return $email;
            });
            $questions['role'] = $question;
        }

        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument($name, $answer);
        }

    }
}
