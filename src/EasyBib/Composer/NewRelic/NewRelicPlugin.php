<?php
namespace EasyBib\Composer\NewRelic;

use Composer\Composer;
use Composer\Console\Application;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\CommandEvent;

/**
 * Composer Plugin that tags composer in New Relic
 *
 * Uses `newrelic.appname` from your `php.ini` or an environment variable
 * `NEWRELIC_APPNAME` to tag the run.
 *
 * @author Till Klampaeckel <till@php.net>
 */
class NewRelicPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer\Composer
     */
    protected $composer;

    /**
     * @var Composer\IO\IOInterface
     */
    protected $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return array(
            PluginEvents::COMMAND => array(
                array('onCommand', 0),
            )
        );
    }

    /**
     * @param CommandEvent $event
     *
     * @return void
     */
    public function onCommand(CommandEvent $event)
    {

        if (false === extension_loaded('newrelic')) {
            if ($this->io->isVerbose()) {
                $this->io->write("<info>ext/newrelic is not loaded.</info>");
            }
            return;
        }

        $appName = ini_get('newrelic.appname');
        if (empty($appName)) {
            $appName = getenv('NEWRELIC_APPNAME');
        }

        if (false === $appName || empty($appName)) {
            $this->io->write("<error>Unable to determine application name.</error>");
            return;
        }

        $command = $event->getCommandName();

        if ($this->io->isVerbose()) {
            $this->io->write("<debug>Tagging composer run.</debug>");
        }

        if ($this->io->isVeryVerbose()) {
            $this->io->write(sprintf("<debug>Application name: %s</debug>", $appName));
            $this->io->write(sprintf("<debug>Command: %s</debug>", $command));
        }

        newrelic_set_appname($appName);
        newrelic_add_custom_parameter('command', $command);
    }
}
