<?php

namespace RandomGift;

use Exception;
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

class Send extends Command
{


    public function configure()
    {
        $this->setName("run")
            ->setDescription('envoi un check')
            ->addOption("test", "t", InputOption::VALUE_OPTIONAL, "mode test", false)
            ->addOption("prepare", "p", InputOption::VALUE_OPTIONAL, "envoi un mail de preparation", false)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(PROJECT_NAME);
        $io->writeln("Send TODO Gift");

        $mode = "go";
        if($input->getOption("prepare") !== false) {$mode = "prepare";};
        if($input->getOption("test") !== false ) {$mode = "test";};
        // processing
        //get conf
        $conf = Yaml::parse(file_get_contents(PROJECT_CONFIG));


        $conf['text_test'] = $conf['text_prepare'] . $conf['text_go'];

        $ids = array_keys($conf['attendees']);
        if(count($conf['attendees']) < 2) {
            $io->error("there is only one mail the list, useless?", SymfonyStyle::OUTPUT_NORMAL);
            return 0;
        }

        $idsTaken = [];
        $users = array_map(
        /**
         * @throws Exception
         */
            function ($k, $v) use ($ids, &$idsTaken, $conf) {
                $v['id'] = $k;
                //randomize
                $toId = null;
                while ($toId === null) {
                    $rand = random_int(0, count($ids) - 1);
                    $id = $ids[$rand];
                    if ($id !== $k && !in_array($id, $idsTaken)) {
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

        $io->writeln("calcul done");
        $io->writeln("sending mail (mode Test= $mode)");
        try {
            $msg = $conf["text_$mode"];
            $transport = Transport::fromDsn($conf['mailer_dsn']);
            $mailer = new Mailer($transport);
            $email = new Email();
            $email->from($conf['mailer_admin']);
            $email->to($conf['mailer_admin']);
            $email->replyTo($conf['mailer_admin']);
            switch ($mode) {
                case "prepare":
                    //mail global
                    $message = sprintf($msg, $conf['gift_max']);
                    $email->subject("Random Gift Loto :: LE VRAI TIRAGE (se prépare)");
                    $email->html($message);
                    foreach ($users as $i => $user) {
                        $email->addTo($user['mail']);
                        $io->writeln("mail sending to " . $user['mail']);
                    }
                    $mailer->send($email);
                    break;
                case "test":
                    //mail global
                    $io->writeln("Test Case");
                    $message = sprintf($msg, $conf['gift_max'], "test Admin", $conf['gift_max']);
                    $email->subject("Random Gift Loto :: LE VRAI TIRAGE (test mode)");
                    $email->html($message);
                    foreach ($users as $i => $user) {
                        if (isset($user['test']) && $user['test'] === true) {
                            $email->addTo($user['mail']);
                            $io->writeln("mail sending to " . $user['mail']);
                        }
                    }
                    $mailer->send($email);
                    //sauvegarde local uniquement dans cas go ? et test
                    file_put_contents(PROJECT_DIR . "/var/gift_" . (new \DateTime())->format("YmdHis") . ".txt", json_encode($users, JSON_PRETTY_PRINT));
                    break;
                case "go":
                default:
                    // 1 mail /personne
                    foreach ($users as $user) {
                        $message = sprintf($msg, $user['gift_to']['name'], $conf['gift_max']);
                        $email->to($user['mail']);
                        $email->subject("Random Gift Loto :: LE VRAI TIRAGE");
                        $email->html($message);
                        $mailer->send($email);
                        $io->writeln("mail sending to " . $user['mail']);
                        sleep(2);
                    }
                    //sauvegarde local uniquement dans cas go ? et test
                    file_put_contents(PROJECT_DIR . "/var/gift_" . (new \DateTime())->format("YmdHis") . ".txt", json_encode($users, JSON_PRETTY_PRINT));
                    break;
            }

        } catch (TransportException $e) {
            $io->error($e->getMessage());
        }
        return 0;
    }
}
