<?php

namespace App\Command;

use App\Entity\Artist;
use App\Entity\Card;
use App\Repository\ArtistRepository;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'import:card',
    description: 'Add a short description for your command',
)]
class ImportCardCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private array $csvHeader = []
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Limite le nombre de cartes à importer',
                null
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->entityManager->getConfiguration()->setMiddlewares([]);
            ini_set('memory_limit', '2G');

            $io = new SymfonyStyle($input, $output);
            $limit = $input->getOption('limit');
            $batchSize = 1000;
            $filepath = __DIR__ . '/../../data/cards.csv';
            $start = microtime(true);

            if (!file_exists($filepath)) {
                $io->error('File not found: ' . $filepath);
                return Command::FAILURE;
            }

            $handle = fopen($filepath, 'r');
            $this->logger->info('Starting import from {filepath}', [
                'filepath' => $filepath,
                'limit' => $limit,
                'batchSize' => $batchSize
            ]);

            $this->csvHeader = fgetcsv($handle);
            $uuidInDatabase = array_flip($this->entityManager->getRepository(Card::class)->getAllUuids());

            $progressIndicator = new ProgressIndicator($output);
            $progressIndicator->start('Importing cards...');

            $this->entityManager->getConnection()->beginTransaction();

            try {
                $i = 0;
                $importCount = 0;

                while (($row = $this->readCSV($handle)) !== false) {
                    $i++;

                    if (!isset($uuidInDatabase[$row['uuid']])) {
                        $this->addCard($row);
                        $importCount++;

                        if ($importCount % $batchSize === 0) {
                            $this->entityManager->flush();
                            $this->entityManager->clear();
                            gc_collect_cycles();
                            $progressIndicator->advance();
                        }
                    }

                    if ($limit !== null && $i >= (int) $limit) {
                        break;
                    }
                }

                $this->entityManager->flush();
                $this->entityManager->getConnection()->commit();

                fclose($handle);
                $progressIndicator->finish('Import completed successfully.');

                $end = microtime(true);
                $timeElapsed = $end - $start;

                $io->success(sprintf('Imported %d cards (processed %d) in %.2f seconds.', $importCount, $i, $timeElapsed));
                $this->logger->info('Imported {imported} cards in {timeElapsed} seconds.', [
                    'processed' => $i,
                    'imported' => $importCount,
                    'timeElapsed' => round($timeElapsed,2),
                ]);

                return Command::SUCCESS;

            } catch (\Exception $e) {
                $this->entityManager->getConnection()->rollBack();
                $this->logger->error('Import failed: ' . $e->getMessage());
                $io->error($e->getMessage());

                return Command::FAILURE;
            }
        } finally {
            $this->entityManager->clear();
            gc_collect_cycles();
        }
    }

    private function readCSV(mixed $handle): array|false
    {
        $row = fgetcsv($handle);
        if ($row === false) {
            return false;
        }
        return array_combine($this->csvHeader, $row);
    }

    private function addCard(array $row)
    {
        $uuid = $row['uuid'];

        $card = new Card();
        $card->setUuid($uuid);
        $card->setManaValue($row['manaValue']);
        $card->setManaCost($row['manaCost']);
        $card->setName($row['name']);
        $card->setRarity($row['rarity']);
        $card->setSetCode($row['setCode']);
        $card->setSubtype($row['subtypes']);
        $card->setText($row['text']);
        $card->setType($row['type']);
        $this->entityManager->persist($card);

    }
}
