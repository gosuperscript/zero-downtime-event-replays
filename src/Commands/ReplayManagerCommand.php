<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Commands;

use Illuminate\Console\Command;
use Gosuperscript\ZeroDowntimeEventReplays\ReplayManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ReplayManagerCommand extends Command
{
    protected $signature = 'replay-manager';

    protected $description = 'Replay manager console';
    private ReplayManager $replayManager;
    private OutputInterface $symfonyOutput;

    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->symfonyOutput = $output;

        return parent::run(
            $input,
            $output
        );
    }

    public function handle(ReplayManager $replayManager): void
    {
        $this->replayManager = $replayManager;

        /** @var ConsoleSectionOutput $section1 */
        $section1 = $this->symfonyOutput->section();
        $section2 = $this->symfonyOutput->section();

        $section1->overwrite();
        $section1->writeln('Hello');
        $section2->writeln('World!');
        // Output displays "Hello\nWorld!\n"
        sleep(2);
        // overwrite() replaces all the existing section contents with the given content
        $section1->overwrite('Goodbye');
        // Output now displays "Goodbye\nWorld!\n"
        sleep(2);
        // clear() deletes all the section contents...
        $section2->clear();
        // Output now displays "Goodbye\n"
        sleep(2);
        // ...but you can also delete a given number of lines
        // (this example deletes the last two lines of the section)
        $section1->clear(2);
        // Output is now completely empty!

//        $this->showMenu();
    }

    private function showMenu()
    {
//        $name = $this->choice(
//            'Select what you want to do?',
//        );
    }
}
