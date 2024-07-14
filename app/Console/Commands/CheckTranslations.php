<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @phpstan-type TranslationKey array{key: string, type: string}
 */
final class CheckTranslations extends Command
{
    /**
     * @var array<string>
     */
    private const array EXCLUDED_DIRECTORIES = ['errors', 'vendor', 'auth', 'mail'];

    /**
     * @var string
     */
    protected $signature = 'translations:check {--show-details : Display detailed information about missing translations}';

    /**
     * @var string
     */
    protected $description = 'Check for missing translations in language files, grouped by type';

    public function handle(): int
    {
        $defaultKeys = $this->scanForTranslationKeys();
        $languageFiles = $this->loadAllLanguageFiles();
        $this->compareTranslations($defaultKeys, $languageFiles);

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, TranslationKey>
     */
    private function scanForTranslationKeys(): Collection
    {
        $keys = $this->extractKeysFromFiles($this->createConfiguredFinder());
        $this->components->info("Found {$keys->count()} unique translatable strings.");

        return $keys;
    }

    private function createConfiguredFinder(): Finder
    {
        return (new Finder)
            ->in([app_path(), resource_path('views')])
            ->name('*.php')
            ->files()
            ->notPath(self::EXCLUDED_DIRECTORIES);
    }

    /**
     * @return Collection<int, TranslationKey>
     */
    private function extractKeysFromFiles(Finder $finder): Collection
    {
        return collect($finder)
            ->reject(fn (SplFileInfo $file): bool => $this->shouldSkipFile($file))
            ->flatMap(fn (SplFileInfo $file): array => $this->getKeysFromFile($file))
            ->unique('key')
            ->values();
    }

    private function shouldSkipFile(SplFileInfo $file): bool
    {
        $relativePath = str_replace(base_path(), '', $file->getPathname());

        return (bool) preg_match('#/(' . implode('|', self::EXCLUDED_DIRECTORIES) . ')/#', $relativePath);
    }

    /**
     * @return array<TranslationKey>
     */
    private function getKeysFromFile(SplFileInfo $file): array
    {
        $type = $this->determineFileType($file);
        $keys = $this->scanFileForKeys($file);

        return array_map(fn (string $key): array => ['key' => $key, 'type' => $type], $keys);
    }

    private function determineFileType(SplFileInfo $file): string
    {
        $path = $file->getPathname();

        return match (true) {
            str_contains($path, 'app/Mail'), str_contains($path, 'resources/views/emails') => 'Mailable',
            str_contains($path, 'app/Http/Controllers') => 'Controller',
            str_contains($path, 'resources/views') => 'View',
            str_contains($path, 'app/Models') => 'Model',
            str_contains($path, 'app/Http/Requests') => 'FormRequest',
            default => 'Other',
        };
    }

    /**
     * @return array<string>
     */
    private function scanFileForKeys(SplFileInfo $file): array
    {
        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            $this->components->error("Failed to read file: {$file->getPathname()}");

            return [];
        }
        preg_match_all('/\b(?:__|trans|trans_choice|@lang)\s*\(\s*[\'"](.+?)[\'"]\s*[\),]/', $content, $matches);

        return $matches[1] ?? [];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function loadAllLanguageFiles(): array
    {
        return collect(File::glob(lang_path('*.json')))
            ->mapWithKeys(fn (string $file) => [
                pathinfo($file, PATHINFO_FILENAME) => json_decode(File::get($file), true, 512, JSON_THROW_ON_ERROR) ?? [],
            ])
            ->all();
    }

    /**
     * @param  Collection<int, TranslationKey>  $defaultKeys
     * @param  array<string, array<string, string>>  $languageFiles
     */
    private function compareTranslations(Collection $defaultKeys, array $languageFiles): void
    {
        $totalMissing = collect($languageFiles)
            ->map(fn (array $translations, string $language): int => $this->processLanguage($language, $translations, $defaultKeys))
            ->sum();

        $this->outputSummary($defaultKeys->count(), count($languageFiles), $totalMissing);
    }

    /**
     * @param  array<string, string>  $translations
     * @param  Collection<int, TranslationKey>  $defaultKeys
     */
    private function processLanguage(string $language, array $translations, Collection $defaultKeys): int
    {
        $missingTranslations = $defaultKeys->whereNotIn('key', array_keys($translations));
        $count = $missingTranslations->count();

        if ($count === 0) {
            return 0;
        }

        $this->components->warn("{$language}: Missing {$count} translation(s)");
        $this->outputMissingByType($missingTranslations);

        return $count;
    }

    /**
     * @param  Collection<int, TranslationKey>  $missingTranslations
     */
    private function outputMissingByType(Collection $missingTranslations): void
    {
        $missingTranslations
            ->groupBy('type')
            ->each(function (Collection $translations, string $type): void {
                $this->components->twoColumnDetail($type, "{$translations->count()} missing");
                if ($this->option('show-details')) {
                    $translations->each(fn (array $item) => $this->components->bulletList([$item['key']]));
                }
            });
    }

    private function outputSummary(int $totalStrings, int $languageCount, int $totalMissing): void
    {
        $this->newLine();
        $this->components->info('Summary:');
        $this->components->twoColumnDetail('Total strings', (string) $totalStrings);
        $this->components->twoColumnDetail('Languages checked', (string) $languageCount);
        $this->components->twoColumnDetail('Total missing', (string) $totalMissing);

        if ($totalMissing === 0) {
            $this->components->info('All translations are up to date.');
        } else {
            $this->components->warn('Some translations are missing. Use --show-details for more information.');
        }
    }
}
