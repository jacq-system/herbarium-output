<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:bootstrap',
    description: 'Bootstrap the application database',
)]
class BootstrapCommand extends Command
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'User email adddress', 'test@jacq.org')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'User password', 'password213')
            ->addOption('redirect-uris', null, InputOption::VALUE_REQUIRED, 'Redirect URIs', 'http://localhost:8080/callback')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getOption('email');
        $password = $input->getOption('password');

        $clientName = 'Test Client';
        $clientId = 'testclient';
        $clientSecret = 'testpass';
        $scopes = ['EMAIL'];
        $grantTypes = ['authorization_code', 'refresh_token'];
        $redirectUris = explode(',', $input->getOption('redirect-uris'));

        // Create the user
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles(['ROLE_SUPER_ADMIN']);;

        $this->em->persist($user);
        $this->em->flush();

        // Create the client
        $conn = $this->em->getConnection();
        $conn->insert('oauth2_client', [
            'identifier' => $clientId,
            'secret' => $clientSecret,
            'name' => $clientName,
            'redirect_uris' => implode(' ',$redirectUris),
            'grants' => implode(' ',$grantTypes),
            'scopes' => implode(' ',$scopes),
            'active' => 1,
            'allow_plain_text_pkce' => 0,
        ]);

        $io->success('Bootstrap complete.');

        return Command::SUCCESS;
    }
}
