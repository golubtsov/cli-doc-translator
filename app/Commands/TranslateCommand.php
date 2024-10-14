<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use Nigo\Doc\DocParallelDoc;
use Nigo\Doc\ParallelDocInterface;
use Nigo\Doc\TxtParallelDoc;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

/**
 * @method notify(string $string, string $string1)
 * @method menu(string $string, array|false $folders)
 */
class TranslateCommand extends Command
{
    protected $signature = 'translate {pathToFile?}';

    protected $description = 'Translate text';

    private string $path;

    private string $parallelDocType;

    protected function configure(): void
    {
        $this->addOption(
            'hidden',
            "H",
            InputOption::VALUE_NONE,
            'Show hidden folders'
        );
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function handle(): void
    {
        $this->path = env('DEFAULT_FOLDER', '/');

        $type = $this->menu(
            'How save file',
            [
                TxtParallelDoc::class => 'txt',
                DocParallelDoc::class => 'doc',
            ]
        )->open();

        if (is_null($type)) exit();

        $this->parallelDocType = $type;


        if (is_null($this->argument('pathToFile'))) {
            $this->getFile();
        }

        $path = $this->argument('pathToFile') ?? $this->path;

        $result = $this->translate($path);

        if (FALSE === $result) {
            $this->error('Some error');

            $this->notify(
                'Error',
                'Some error'
            );
            exit();
        }

        $this->info('Path to file - ' . $result);

        $this->notify(
            'Translator',
            'The file translated!' . PHP_EOL . $result
        );
    }

    private function getFile(): void
    {
        $folders = scandir($this->path);

        if (!$this->option('hidden')) {
            $folders = $this->getOnlyPublicFolders($folders);
        }

        $option = $this->menu('Folders', $folders)->open();

        if (is_null($option)) exit();

        $this->path .= '/' . $folders[$option];

        if (is_file($this->path)) {
            $folders = explode('/', $this->path);

            $file = $folders[array_key_last($folders)];

            $explodeFile = explode('.', $file);

            $format = $explodeFile[array_key_last($explodeFile)];

            if ($format !== 'txt') {
                $this->warn('File must be a TXT format');
                exit();
            }
        }

        if (is_dir($this->path)) {
            $this->getFile();
        }
    }

    private function getOnlyPublicFolders(array $folders): array
    {
        return array_merge(
            [
                '.',
                '..',
            ],
            array_values(
                array_filter(
                    $folders,
                    function (string $folder) {
                        return $folder[0] !== '.';
                    }
                )
            )
        );
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     */
    private function translate(string $path): string|false
    {
        /** @var ParallelDocInterface $txtParallelDoc */
        $txtParallelDoc = new $this->parallelDocType('ru');
        return $txtParallelDoc->generate($path);
    }
}
