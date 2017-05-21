<?php

namespace FiiSoft\Tools\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    /** @var InputInterface */
    protected $input;
    
    /** @var OutputInterface */
    protected $output;
    
    /** @var int used to check if pid file exists once per second */
    private $lastCheck = 0;
    
    final protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->handleInput($input, $output);
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    abstract protected function handleInput(InputInterface $input, OutputInterface $output);
    
    /**
     * Create pid file (ind default directory).
     * If pid file cannot be created, stop execution with exit code 1.
     *
     * @param string $pidfilesPath path to directory where pid file will be created
     * @param string $pidfilePrefix prefix for name of pid file
     * @return string path to created pid file
     */
    final protected function createPidFile($pidfilesPath, $pidfilePrefix)
    {
        if (!$this->openPidFile($pidfilesPath, $pidfilePrefix, $pidFile)) {
            $this->output->writeln('Unable to create pid file '.$pidFile);
            $this->output->writeln('Please be sure pid file can be created in this location.');
            $this->output->writeln('Command stopped!');
            exit(1);
        }
        
        return $pidFile;
    }
    
    /**
     * @param string $pidfilesPath path to directory where pid file will be created
     * @param string $pidfilePrefix prefix for name of pid file
     * @param string $pidFile REFERENCE will contain path to created pid file (if succeeded)
     * @return bool true if pid file created (or exists)
     */
    private function openPidFile($pidfilesPath, $pidfilePrefix, &$pidFile)
    {
        $pid = getmypid();
        if (false === $pid) {
            $this->writelnVV('PID is not available, so random number will be use instead of');
            $pid = 'rnd_'.mt_rand(1, PHP_INT_MAX);
        } else {
            $pid = 'pid_'.$pid;
        }
        
        $pidFile = $pidfilesPath.$pidfilePrefix.$pid.'.pid';
        $this->writelnVVV('Pid file for command is '.$pidFile);
    
        if (!is_dir($pidfilesPath)) {
            @mkdir($pidfilesPath, 0666, true);
        }
        
        if (is_dir($pidfilesPath)) {
            $fp = fopen($pidFile, 'c');
            if (is_resource($fp)) {
                fwrite($fp, $pid);
                fclose($fp);
                $this->writelnVV('Pid file '.$pidFile.' created');
                return true;
            }
            $this->writelnVVV('Pid file open error, file not created');
        } else {
            $this->writelnVVV('Directory for pid files does not exist and cannot be created ('
                .$pidfilesPath.')'
            );
        }
        
        return false;
    }
    
    /**
     * @param string $pidFile
     * @param boolean $forceCheck (default false)
     * @return bool
     */
    final protected function isPidFileExists($pidFile, $forceCheck = false)
    {
        $now = time();
        if ($forceCheck || ($now - $this->lastCheck >= 1)) {
            $this->writelnVVV('Checking if pid file exists');
            $this->lastCheck = $now;
            return file_exists($pidFile);
        }
        
        return true;
    }
    
    /**
     * @param string|array $messages
     * @return $this
     */
    final protected function writeln($messages)
    {
        $this->output->writeln($messages);
        return $this;
    }
    
    /**
     * @param string|array $messages
     * @return $this
     */
    final protected function writelnV($messages)
    {
        $this->output->writeln($messages, OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE);
        return $this;
    }
    
    /**
     * @param string|array $messages
     * @return $this
     */
    final protected function writelnVV($messages)
    {
        $this->output->writeln($messages, OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERY_VERBOSE);
        return $this;
    }
    
    /**
     * @param string|array $messages
     * @return $this
     */
    final protected function writelnVVV($messages)
    {
        $this->output->writeln($messages, OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_DEBUG);
        return $this;
    }
    
    /**
     * @param OutputInterface|null $output
     * @return bool
     */
    final protected function isQuiet(OutputInterface $output = null)
    {
        if ($output) {
            return $output->getVerbosity() === OutputInterface::VERBOSITY_QUIET;
        }
    
        if ($this->output) {
            return $this->output->getVerbosity() === OutputInterface::VERBOSITY_QUIET;
        }
        
        return false;
    }
}