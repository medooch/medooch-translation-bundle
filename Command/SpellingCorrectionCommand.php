<?php
/**
 * Copyright (c) 2017.
 */
namespace Medooch\Bundles\MedoochTranslationBundle\Command;

use Medooch\Components\Lib\Reverso\Spelling;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
/**
 * Class SpellingCorrectionCommand
 * @package Medooch\Bundles\MedoochTranslationBundle\Command
 */
class SpellingCorrectionCommand extends I18nCommand
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
            ->setName('medooch:i18n:spelling')
            ->setDescription('Spelling correction in i18n files');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();
        if ($input->isInteractive()) {
            $question = new ConfirmationQuestion($questionHelper->getQuestion('Do you want to check spelling', 'yes', '?'), true);
            if (!$questionHelper->ask($input, $output, $question)) {
                return 1;
            }
        }
        $this->kernel = $this->getContainer()->get('kernel');
        $this->checkConfiguration();

        /**
         * Get the source and targets locales
         */
        $this->source = $this->getContainer()->getParameter('locale');

        if ($this->source != 'fr') {
            throw new \Exception('The spelling correction language is not equal to "fr" => "frensh". Change your locale parameter to "fr".');
        }

        $bundles = $this->getContainer()->getParameter('generator.translator')['bundles'];
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('Start Spelling Correction by Medooch');
        $this->output = [];

        foreach ($bundles as $bundle) {
            $this->bundleDetectTranslations($bundle, 'spellingCorrection');
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
    protected function spellingCorrection(string $path, array $files)
    {
        foreach ($files as $file) {
            if (is_numeric(strpos($file, $this->source . '.yml'))) {
                $this->io->comment('Spelling correction file ' . $file . ' : locale ' . $this->source);
                $this->io->progressStart();
                $content = $this->getFileContents($path . '/' . $file);
                foreach ($content as $key => $values) {
                    $this->output[$path . '/' . $file][$this->source][$key] = $this->spellingDetect($values, $this->source, $file);
                }
                $this->io->progressFinish();
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
    private function spellingDetect($values, string $locale, string $file)
    {
        if (is_array($values)) {
            $output = [];
            foreach ($values as $keyChild => $value) {
                $this->io->progressAdvance(1);
                $output[$keyChild] = $this->spellingDetect($value, $locale, $file);
            }
            return $output;
        } else {
            $result = Spelling::correctionText($values);
            if (is_array($result) && isset($result['AutoCorrectedText'])) {
                $this->io->progressAdvance(1);
                return $result['AutoCorrectedText'];
            }
        }
    }
}
