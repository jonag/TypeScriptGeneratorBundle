<?php

namespace A5sys\TypeScriptGeneratorBundle\Command;

use A5sys\TypeScriptGeneratorBundle\Generator\TypeScriptGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateTypeScriptCommand
 */
class GenerateTypeScriptCommand extends Command
{
    private $generator;
    private $commandName;

    /**
     * GenerateTypeScriptCommand constructor.
     * @param \A5sys\TypeScriptGeneratorBundle\Generator\TypeScriptGenerator $generator
     * @param string                                                         $commandName
     * @param null                                                           $name
     */
    public function __construct(TypeScriptGenerator $generator, string $commandName, $name = null)
    {
        $this->generator = $generator;
        $this->commandName = $commandName;
        parent::__construct($name);
    }

    /**
     * @{@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('a5sys:ts-generator:'.$this->commandName)
            ->addArgument('input', InputArgument::REQUIRED, 'Input path')
            ->addArgument('output', InputArgument::REQUIRED, 'Output path')
            ->setDescription(sprintf('Generate TypeScript %s from PHP classes', $this->commandName));
        ;
    }

    /**
     * @{@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputPath = $input->getArgument('input');
        $outputPath = $input->getArgument('output');
        $this->generator->generate($inputPath, $outputPath);
    }
}
