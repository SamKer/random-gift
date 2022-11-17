<?php

namespace RandomGift;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Yaml\Yaml;

class Send extends Command {



    public function configure()
    {
         $this->setName("run")
		->setDescription('envoi un check')
		->addOption("test", "t",  InputOption::VALUE_OPTIONAL, "mode test")
             ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(PROJECT_NAME);
        $io->writeln("Send TODO Gift", SymfonyStyle::OUTPUT_NORMAL);

        $modeTest = $input->getOption("test");

        // processing
        //get conf
        $conf = Yaml::parse(file_get_contents(PROJECT_CONFIG));

        $ids = array_keys($conf['attendees']);
        $idsTaken = [];
        $users = array_map(/**
         * @throws \Exception
         */ function($k, $v) use ($ids, &$idsTaken, $conf) {
            $v['id'] = $k;
            //randomize
            $toId = null;
            while ($toId === null) {
                $rand  = random_int(0, count($ids)-1);
                $id = $ids[$rand];
                if($id !== $k && !in_array($id, $idsTaken)) {
                    $toId = $id;
                    $idsTaken[] = $toId;
                }
            }
            $v['gift_to'] = $conf['attendees'][$toId];
            return $v;
        },
        array_keys($conf['attendees']),
        $conf['attendees']
    );


try {


    foreach ($users as $i => $user) {
        $message = "<div style=''>
<h3>Random Gift:</h3>
    <div>
        <p>Random gift a lancé les dés:</p>
        <p>Le tirage au sort a décidé que vous devez offrir à %s un cadeau d'une valeur n'excédant pas %s.</p>
        <p>Les échanges de cadeaux se dérouleront à la rentrée 2022</p>
        <p>Cordialement, Joyeux Noël</p>
    </div>
</div>";
        $message = sprintf($message, $user['gift_to']['name'], $conf['gift_max']);
        //send
        if($modeTest === null) {
            $transport = Transport::fromDsn($conf['mailer_dsn']);
            $mailer = new Mailer($transport);
            $email = new Email();
            $email->from($conf['mailer_admin']);
            $email->to($user['mail']);
            $email->subject("Random Gift Loto :: LE VRAI TIRAGE");
            $email->html($message);
            $mailer->send($email);
            $io->writeln("mail sending to " . $user['mail']);
            sleep(2);
        }
    }

    //sauvegarde local
    file_put_contents(PROJECT_DIR . "/var/gift_". (new \DateTime())->format("YmdHis").".txt", json_encode($users, JSON_PRETTY_PRINT));

} catch(TransportException $e) {
    $io->error($e->getMessage());
}
        return 0;
    }
}
