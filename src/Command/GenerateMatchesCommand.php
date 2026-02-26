<?php

namespace App\Command;

use App\Repository\TournoiRepository;
use App\Service\MatchGeneratorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


#[AsCommand(
    name: 'app:generate-matches',
    description: 'Génère les matchs pour les tournois dont la date limite d\'inscription est passée.',
)]
class GenerateMatchesCommand extends Command
{
    public function __construct(
        private TournoiRepository $tournoiRepository,
        private MatchGeneratorService $matchGenerator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTime();

        // Récupérer les tournois "En Attente" dont la date limite est passée
        $tournois = $this->tournoiRepository->findTournoisReadyForGeneration($now);

        if (empty($tournois)) {
            $io->info('Aucun tournoi prêt pour la génération de matchs.');
            return Command::SUCCESS;
        }

        $totalGenerated = 0;

        foreach ($tournois as $tournoi) {
            $io->section(sprintf('Tournoi #%d : %s', $tournoi->getId(), $tournoi->getNom()));
            $io->text(sprintf(
                '  Date limite inscription : %s',
                $tournoi->getDateInscriptionLimite()?->format('Y-m-d H:i') ?? 'N/A'
            ));

            $nbMatchs = $this->matchGenerator->generateIfReady($tournoi);

            if ($nbMatchs > 0) {
                $io->success(sprintf('  → %d matchs générés !', $nbMatchs));
                $totalGenerated += $nbMatchs;
            } else {
                $io->warning('  → Pas assez d\'équipes inscrites (minimum 2), aucun match généré.');
            }
        }

        $io->newLine();
        $io->success(sprintf(
            'Terminé : %d tournoi(s) traité(s), %d match(s) généré(s) au total.',
            count($tournois),
            $totalGenerated
        ));

        return Command::SUCCESS;
    }
}
