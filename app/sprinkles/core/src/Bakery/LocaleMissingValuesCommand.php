<?php

/*
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @copyright Copyright (c) 2019 Alexander Weissman
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\Core\Bakery;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UserFrosting\I18n\LocalePathBuilder;
use UserFrosting\I18n\MessageTranslator;
use UserFrosting\Support\Repository\Loader\ArrayFileLoader;

/**
 * locale:missing-values command.
 * Find missing values in locale translation files.
 *
 * @author Amos Folz
 */
class LocaleMissingValuesCommand extends LocaleMissingKeysCommand
{
    /**
     * @var string
     */
    protected $localesToCheck;

    /**
     * @var array
     */
    protected static $table = [];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('locale:missing-values')
        ->setHelp("This command generates missing locale file keys through comparison. E.g. running 'locale:missing-values -b en_US -f es_ES' will compare all es_ES and en_US locale files and populate es_ES with any missing keys from en_US.")
        ->addOption('base', 'b', InputOption::VALUE_REQUIRED, 'The base locale used for comparison and translation preview.', 'en_US')
        ->addOption('check', 'c', InputOption::VALUE_REQUIRED, 'One or more specific locales to check. E.g. "fr_FR,es_ES"', null)
        ->addOption('length', 'l', InputOption::VALUE_REQUIRED, 'Set max length for preview column text.', 255)
        ->addOption('empty', 'e', InputOption::VALUE_NONE, 'Setting this will skip check for empty strings.')
        ->addOption('duplicates', 'd', InputOption::VALUE_NONE, 'Setting this will skip comparison check.');

        $this->setDescription('Generate a table of keys with missing values.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title('Missing/Duplicate Locale Values');

        $this->table = new Table($output);
        $this->table->setStyle('borderless');

        // Option -c. The locales to be checked.
        $this->localesToCheck = $input->getOption('check');

        $this->maxLength = $input->getOption('length');

        // The locale for the 'preview' column. Defaults to en_US if not set.
        $baseLocale = $input->getOption('base');

        $this->setTranslation($baseLocale);

        $locales = $this->getLocales($baseLocale);

        $files = $this->getFilePaths($locales);

        $baseLocaleFileNames = $this->getFilenames($baseLocale);

        if ($input->getOption('empty') != true) {
            $missing[] = $this->searchFilesForNull($files);
            $this->table->setHeaders([
              [new TableCell('LOCALES SEARCHED: |' . implode('|', $locales) . '|', ['colspan' => 3])],
              [new TableCell("USING | $baseLocale | FOR TRANSLATION PREVIEW AND COMPARISON", ['colspan' => 3])],

            ]);
            $this->table->setColumnWidth(2, 50);

            $this->table->addRows([
              [new TableCell('EMPTY VALUES', ['colspan' => 3])],
              new TableSeparator(),
              ['FILE PATH', 'KEY', 'TRANSLATION PREVIEW'],
              new TableSeparator(),
            ]);
            // Build the table.
            $this->buildTable($missing);
            $this->table->addRows([
              new TableSeparator(),
            ]);
        }

        if ($input->getOption('duplicates') != true) {
            foreach ($locales as $key => $altLocale) {
                $duplicates[] = $this->compareFiles($baseLocale, $altLocale, $baseLocaleFileNames);
            }
            $this->table->addRows([
              [new TableCell('DUPLICATE VALUES', ['colspan' => 3])],
              new TableSeparator(),
              ['FILE PATH', 'KEY', 'DUPLICATE VALUE'],
              new TableSeparator(),
            ]);
            $this->buildTable($duplicates);
        }

        return $this->table->render();
    }

    /**
     * Intersect two arrays with considertaion of both keys and values.
     *
     * @param array $primary_array
     * @param array $secondary_array
     *
     * @return array Matching keys and values that are found in both arrays.
     */
    protected function arrayIntersect($primary_array, $secondary_array)
    {
        if (!is_array($primary_array) || !is_array($secondary_array)) {
            return false;
        }

        if (!empty($primary_array)) {
            foreach ($primary_array as $key => $value) {
                if (!isset($secondary_array[$key])) {
                    unset($primary_array[$key]);
                } else {
                    if (serialize($secondary_array[$key]) != serialize($value)) {
                        unset($primary_array[$key]);
                    }
                }
            }

            return $primary_array;
        } else {
            return [];
        }
    }

    /**
     * Populate table with file paths, keys of missing/duplicate values, and a preview in a specific locale.
     *
     * @param array $array File paths and missing keys.
     * @param int   $level Nested array depth.
     */
    protected function buildTable(array $array, $level = 1)
    {
        foreach ($array as $key => $value) {
            //Level 2 has the filepath.
            if ($level == 2) {
                // Make path easier to read by removing anything before 'sprinkles'
                $this->path = strstr($key, 'sprinkles');
            }
            if (is_array($value)) {
                //We need to loop through it.
                $this->buildTable($value, ($level + 1));
            } else {
                $this->table->addRow([$this->path, $key, substr($this->translator->translate($key), 0, $this->maxLength)]);
            }
        }
    }

    /**
     * Iterate over sprinkle locale files and find duplicates.
     *
     * @param string $baseLocale Locale being compared against.
     * @param string $altLocale  Locale to find missing values for.
     * @param array  $filenames  Sprinkle locale files that will be compared.
     *
     * @return array Intersect of keys with identical values.
     */
    protected function compareFiles($baseLocale, $altLocale, $filenames)
    {
        foreach ($filenames as $sprinklePath => $files) {
            foreach ($files as $key => $file) {
                $base = $this->arrayFlatten($this->parseFile("$sprinklePath/locale/{$baseLocale}/{$file}"));
                $alt = $this->arrayFlatten($this->parseFile("$sprinklePath/locale/{$altLocale}/{$file}"));

                $missing[$sprinklePath . '/locale' . '/' . $altLocale . '/' . $file] = $this->arrayIntersect($base, $alt);
            }
        }

        return $missing;
    }

    /**
     * Find keys with missing values. Collapses keys into array dot syntax.
     *
     * @param array $array Locale translation file.
     *
     * @return array Keys with missing values.
     */
    protected function findMissing($array, $prefix = '')
    {
        $result = [];
        foreach ($array as $key=>$value) {
            if (is_array($value)) {
                $result = $result + $this->findMissing($value, $prefix . $key . '.');
            } else {
                $result[$prefix . $key] = $value;
            }
        }

        // We only want empty values.
        return array_filter($result, function ($key) {
            return empty($key);
        });
    }

    /**
     * Get a list of locale file paths.
     *
     * @param array $locale Array of locale(s) to get files for.
     *
     * @return array
     */
    protected function getFilePaths($locale)
    {
        // Set up a locator class
        $locator = $this->ci->locator;
        $builder = new LocalePathBuilder($locator, 'locale://', $locale);
        $loader = new ArrayFileLoader($builder->buildPaths());

        // Get nested array [0].
        return array_values((array) $loader)[0];
    }

    /**
     * @return array Locales to check for misisng values.
     */
    protected function getLocales($baseLocale)
    {
        // If set, use the locale from the -c option.
        if ($this->localesToCheck) {
            return explode(',', $this->localesToCheck);
        } else {
            //Need to filter the base locale to prevent false positive.
            return array_diff(array_keys($this->ci->config['site']['locales']['available']), [$baseLocale]);
        }
    }

    /**
     * Search through locale files and find empty values.
     *
     * @param array $files Filenames to search.
     *
     * @return array
     */
    protected function searchFilesForNull($files)
    {
        foreach ($files as $key => $file) {
            $missing[$file] = $this->findMissing($this->parseFile($file));
        }

        return $missing;
    }

    /**
     * Sets up translator for a specific locale.
     *
     * @param string $locale Locale to be used for translation.
     */
    protected function setTranslation(string $locale)
    {
        // Setup the translator. Set with -t or defaults to en_US
        $locator = $this->ci->locator;
        $builder = new LocalePathBuilder($locator, 'locale://', [$locale]);
        $loader = new ArrayFileLoader($builder->buildPaths());
        $this->translator = new MessageTranslator($loader->load());
    }
}
