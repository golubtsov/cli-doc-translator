<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use Nigo\Doc\ParallelDoc;
use Nigo\Doc\TxtMarkupFormatter;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @method notify(string $string, string $string1)
 * @method menu(string $string, array|false $folders)
 */
class TranslateCommand extends Command
{
    protected $signature = 'translate {pathToFile?}';
    protected $description = 'Translate text';

    private string $path;
    private string $markup;

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
     * @throws ServerExceptionInterface|TransportExceptionInterface
     */
    public function handle(): void
    {
        $this->path = env('DEFAULT_FOLDER', '/');
        $this->markup = $this->selectMarkupFormat();

        if (is_null($this->argument('pathToFile'))) {
            $this->path = $this->selectFile();
        } else {
            $this->path = $this->argument('pathToFile');
        }

        $result = $this->translate($this->path);

        if ($result === false) {
            $this->error('Some error');
            $this->notify('Error', 'Some error');
            exit();
        }

        $this->info('Path to file - ' . $result);
        $this->notify('Translator', 'The file translated!' . PHP_EOL . $result);
    }

    private function selectMarkupFormat(): string
    {
        $markup = $this->menu(
            'How save file',
            [
                'txt' => 'txt',
                'doc' => 'doc',
            ]
        )->open();

        if (is_null($markup)) {
            exit();
        }

        return $markup;
    }

    private function selectFile(): string
    {
        while (true) {
            $folders = scandir($this->path);
            $folders = $this->filterFolders($folders);

            $option = $this->menu('Folders', $folders)->open();
            if (is_null($option)) {
                exit();
            }

            $this->path .= '/' . $folders[$option];

            if (is_file($this->path)) {
                $this->validateFileFormat($this->path);
                break;
            }

            if (!is_dir($this->path)) {
                $this->warn('Selected path is not a directory or file.');
                exit();
            }
        }

        return $this->path;
    }

    private function filterFolders(array $folders): array
    {
        if (!$this->option('hidden')) {
            $folders = array_filter($folders, fn($folder) => $folder[0] !== '.');
        }

        return array_values($folders);
    }

    private function validateFileFormat(string $path): void
    {
        $format = pathinfo($path, PATHINFO_EXTENSION);
        if ($format !== 'txt') {
            $this->warn('File must be a TXT format');
            exit();
        }
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface|TransportExceptionInterface
     */
    private function translate(string $path): string|false
    {
        $parallelDoc = new ParallelDoc(
            'ru',
            $this->markup,
            app(TxtMarkupFormatter::class)
        );

        return $parallelDoc->generate($path);
    }
}
