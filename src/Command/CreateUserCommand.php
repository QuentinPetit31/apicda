<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\Account;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


#[AsCommand(
    name: 'app:create-user',
    description: 'Commande pour ajouter un utilisateur en BDD',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            //configure an argument
            ->addArgument('firstname', InputArgument::REQUIRED, 'Prénon de l\'utilisateur')
            ->addArgument('lastname', InputArgument::REQUIRED, 'Nom de l\'utilisateur')
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur')
            ->addArgument('password', InputArgument::REQUIRED, 'Password de l\'utilisateur')
            ->addOption('adm')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            if($this->accountRepository->findOneBy(["email" => $input->getArgument('email')])){
                
                $io->error("Le compte existe deja");
                
                return Command::FAILURE;
            }
            //Objet account + valeurs set
            $account = new Account();
            $account
                ->setFirstname($input->getArgument('firstname'))
                ->setLastname($input->getArgument('lastname'))
                ->setEmail($input->getArgument('email'))
                ->setPassword($this->hasher->hashPassword($account, $input->getArgument('password')))
                ->setRoles(["ROLE_USER"]);
                $msg = "Le compte à été ajouté en BDD";
                
            if($input->getOption('adm')) {

                $account->setRoles(["ROLE_USER","ROLE_ADMIN"]);
                
                $msg = "Le compte Admin à été ajouté en BDD";
            }
            
            $this->em->persist($account);
            $this->em->flush();
        } catch (\Exception $e) {

            $io->error($e->getMessage());
            
            return Command::FAILURE;
        }
        
        $io->success($msg);

        return Command::SUCCESS;
    }
}