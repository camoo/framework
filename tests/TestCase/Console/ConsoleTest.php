<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Console;

namespace CAMOO\Test\TestCase\Console\Command;

use CAMOO\Command\Command;
use CAMOO\Console\Input\InputArgument;

class TestDummyCommand extends Command
{
    public function execute(): int
    {
        return Command::SUCCESS;
    }

    public function customMethod(string $param1 = 'default'): string
    {
        return 'executed_' . $param1;
    }

    public function getDefinition(): \Symfony\Component\Console\Input\InputDefinition
    {
        return new \Symfony\Component\Console\Input\InputDefinition([
            new InputArgument('action', InputArgument::OPTIONAL),
            new InputArgument('param1', InputArgument::OPTIONAL),
        ]);
    }
}

class TestDisabledCommand extends Command
{
    public function execute(): int
    {
        return Command::SUCCESS;
    }

    public function isEnabled(): bool
    {
        return false;
    }
}

namespace CAMOO\Test\TestCase\Console;

use CAMOO\Command\Command;
use CAMOO\Console\BackgroundProcess;
use CAMOO\Console\CommandFinder;
use CAMOO\Console\CommandWrapper;
use CAMOO\Console\Input\InputArgument;
use CAMOO\Console\Input\InputOption;
use CAMOO\Console\Runner;
use CAMOO\Exception\ConsoleException;
use CAMOO\Test\TestCase\Console\Command\TestDummyCommand;
use CAMOO\Utils\Configure;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BackgroundProcess::class)]
#[CoversClass(CommandWrapper::class)]
#[CoversClass(InputArgument::class)]
#[CoversClass(InputOption::class)]
#[CoversClass(Runner::class)]
#[CoversClass(CommandFinder::class)]
#[CoversClass(Command::class)]
class ConsoleTest extends TestCase
{
    public function setUp(): void
    {
        Configure::write('App.namespace', 'CAMOO\\Test\\TestCase\\Console');
        \CAMOO\Di\CamooDi::create();
    }

    public function testBackgroundProcess(): void
    {
        $bg = new BackgroundProcess('echo "hello"');
        $this->assertSame(0, $bg->getPid());
        $this->assertFalse($bg->isRunning());
        $this->assertFalse($bg->stop());

        $pid = $bg->run(TMP . 'bg_out.txt');
        $this->assertGreaterThan(0, $pid);

        @unlink(TMP . 'bg_out.txt');
    }

    public function testBackgroundProcessMissingCommand(): void
    {
        $bg = new BackgroundProcess();
        $this->expectException(ConsoleException::class);
        $bg->run();
    }

    public function testInputArgumentAndOption(): void
    {
        $arg = new InputArgument('name', InputArgument::REQUIRED, 'User name');
        $this->assertSame('name', $arg->getName());

        $opt = new InputOption('flag', 'f', InputOption::VALUE_NONE, 'Flag option');
        $this->assertSame('flag', $opt->getName());
    }

    public function testCommandWrapperMethodException(): void
    {
        $wrapper = new CommandWrapper('test');
        $this->expectException(ConsoleException::class);
        $wrapper->nonExistentMethod();
    }

    public function testCommandWrapperValidMethod(): void
    {
        $wrapper = new CommandWrapper('test_cmd');
        $wrapper->setDescription('My Description');
        $this->assertSame('My Description', $wrapper->getDescription());
    }

    public function testCommandInitializationAndSatanise(): void
    {
        $cmd = new TestDummyCommand();
        $this->assertTrue($cmd->isEnabled());

        $cmd->initialize('test_dummy', ['customMethod', 'param1_val']);
        $this->assertSame('customMethod', $cmd->getCommandMethod());
        $this->assertSame(['param1' => 'param1_val'], $cmd->getCommandParam());

        $cmd->setDescription('Test Command Description');
        $this->assertSame('Test Command Description', $cmd->getDescription());
    }

    public function testCommandSataniseObjectThrowsException(): void
    {
        $cmd = new TestDummyCommand();
        $cmd->initialize('test_dummy', []);

        $refMethod = new \ReflectionMethod($cmd, 'satanise');
        $refMethod->setAccessible(true);

        $this->expectException(InvalidArgumentException::class);
        $refMethod->invoke($cmd, new \stdClass());
    }

    public function testCommandSataniseNumericAndNestedArray(): void
    {
        $cmd = new TestDummyCommand();
        $refMethod = new \ReflectionMethod($cmd, 'satanise');
        $refMethod->setAccessible(true);

        $this->assertSame(123, $refMethod->invoke($cmd, 123));
        $this->assertSame([], $refMethod->invoke($cmd, []));
        $this->assertSame(['key' => 'safe_string'], $refMethod->invoke($cmd, ['key' => 'safe_string']));
    }

    public function testCommandFinder(): void
    {
        $finder = new CommandFinder();
        $finder->find();
        $this->assertTrue(true);
    }

    public function testRunnerEmptyArgsThrowsException(): void
    {
        $runner = new Runner(['script.php']);
        $this->expectException(ConsoleException::class);
        $runner->run();
    }

    public function testRunnerUnknownCommandThrowsException(): void
    {
        $runner = new Runner(['script.php', 'non_existing_command_xyz']);
        $this->expectException(ConsoleException::class);
        $runner->run();
    }

    public function testRunnerExecutesCustomMethod(): void
    {
        $runner = new Runner(['script.php', 'test_dummy', 'customMethod', 'val1']);
        $runner->run();
        $this->assertTrue(true);
    }

    public function testRunnerDisabledCommandThrowsException(): void
    {
        $runner = new Runner(['script.php', 'test_disabled']);
        $this->expectException(ConsoleException::class);
        $runner->run();
    }
}
