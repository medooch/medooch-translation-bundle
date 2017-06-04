<?php
/**
 * Copyright (c) 2017.
 */
namespace Medooch\Bundles\MedoochTranslationBundle\Command;

use Medooch\Components\Lib\Google\Translator\GoogleTranslator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
/**
 * Class TranslationsGeneratorCommand
 * @package Medooch\Bundles\MedoochTranslationBundle\Command
 */
class TranslationsGeneratorCommand extends I18nCommand
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var string
     */
    private $source;

    /**
     * @var array
     */
    private $targets = [];

    /**
     * @var array
     */
    private $output = [];

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('medooch:i18n:translations')
            ->setDescription('Generate all translations files from the default local in parameters.yml to the configured locales');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();
        if ($input->isInteractive()) {
            $question = new ConfirmationQuestion($questionHelper->getQuestion('Do you want to check spelling', 'yes', '?'), true);
            if ($questionHelper->ask($input, $output, $question)) {
                $command = $this->getApplication()->find('medooch:i18n:spelling');

                $arguments = array(
                    'command' => 'medooch:i18n:spelling',
                );

                $greetInput = new ArrayInput($arguments);
                $command->run($greetInput, $output);
            }
        }

        $this->kernel = $this->getContainer()->get('kernel');
        $this->checkConfiguration();

        /**
         * Get the source and targets locales
         */
        $this->source = $this->getContainer()->getParameter('locale');
        $this->targets = array_diff($this->getContainer()->getParameter('locales'), [$this->source]);

        $bundles = $this->getContainer()->getParameter('generator.translator')['bundles'];
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('Start Generating Translations by Medooch');
        $this->output = [];

        foreach ($bundles as $bundle) {
            $this->bundleDetectTranslations($bundle, 'generateTranslation');
        }

        if (count($this->output)) {
            $this->io->warning('Dumping new files');
            foreach ($this->output as $file => $results) {
                foreach ($results as $locale => $content) {
                    $targetFile = str_replace($this->source . '.yml', $locale . '.yml', $file);
                    $this->io->success('Dumping new file ' . $targetFile);
                    $this->setFileContents($targetFile, $content);
                }
            }
        }
    }

    /**
     * ---------------------------------------
     * @author: Trimech Mehdi <trimechmehdi11@gmail.com> // url : http://trimech-mahdi.fr/
     * ---------------------------------------
     * **************** Function input: ****************
     * @param string $path
     * @param array $files
     * ---------------------------------------
     */
    protected function generateTranslation(string $path, array $files)
    {
        foreach ($files as $file) {
            if (is_numeric(strpos($file, $this->source . '.yml'))) {
                foreach ($this->targets as $locale) {
                    $this->io->comment('Generating translation file ' . $file . ' : locale ' . $locale);
                    $this->io->progressStart();
                    $content = $this->getFileContents($path . '/' . $file);
                    foreach ($content as $key => $values) {
                        $this->output[$path . '/' . $file][$locale][$key] = $this->trans($values, $locale, $file);
                    }
                    $this->io->progressFinish();
                }
            }
        }
    }

    /**
     * ---------------------------------------
     * @author: Trimech Mehdi <trimechmehdi11@gmail.com> // url : http://trimech-mahdi.fr/
     * ---------------------------------------
     * **************** Function input: ****************
     * @param $values
     * @param string $locale
     * @param string $file
     * ---------------------------------------
     * **************** Function output: ****************
     * @return mixed|string
     * ---------------------------------------
     */
    private function trans($values, string $locale, string $file)
    {
        if (is_array($values)) {
            $output = [];
            foreach ($values as $keyChild => $value) {
                $this->io->progressAdvance(1);
                $output[$keyChild] = $this->trans($value, $locale, $file);
            }
            return $output;
        } else {
            $result = GoogleTranslator::translate($values, $this->source, $locale);
            if ($result) {
                $this->io->progressAdvance(1);
                $result = str_replace('</ Br>', '</br>', $result);
                $result = str_replace('% ', ' %', $result);
                $result = str_replace(' %', '% ', $result);
                return ucfirst($result);
            }
        }
    }
}
