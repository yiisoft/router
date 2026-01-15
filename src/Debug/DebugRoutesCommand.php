<?php

declare(strict_types=1);

namespace Yiisoft\Router\Debug;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\VarDumper\VarDumper;
use Yiisoft\Yii\Debug\Debugger;

use function count;
use function is_array;
use function is_string;

/**
 * @infection-ignore-all
 */
#[AsCommand(
    name: DebugRoutesCommand::COMMAND_NAME,
    description: 'Show information about registered routes',
)]
final class DebugRoutesCommand extends Command
{
    public const COMMAND_NAME = 'debug:routes';

    public function __construct(
        private readonly RouteCollectionInterface $routeCollection,
        private readonly Debugger $debugger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('route', InputArgument::IS_ARRAY, 'Route name');
    }

    /**
     * @psalm-suppress MixedArgument, MixedAssignment, MixedArrayAccess
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->debugger->stop();

        $io = new SymfonyStyle($input, $output);

        if ($input->hasArgument('route') && !empty($input->getArgument('route'))) {
            /**
             * @var string[] $routes
             */
            $routes = (array) $input->getArgument('route');
            foreach ($routes as $route) {
                $route = $this->routeCollection->getRoute($route);
                $data = $route->__debugInfo();
                $action = '';
                $middlewares = [];
                if (!empty($data['enabledMiddlewares'])) {
                    $middlewareDefinitions = $data['enabledMiddlewares'];
                    $action = array_pop($middlewareDefinitions);
                    $middlewares = $middlewareDefinitions;
                }

                $io->title($data['name']);
                $definitionList = [
                    ['Methods' => $this->export($data['methods'])],
                    ['Name' => $data['name']],
                    ['Pattern' => $data['pattern']],
                ];
                if (!empty($action)) {
                    $definitionList[] = ['Action' => $this->export($action)];
                }
                if (!empty($data['defaults'])) {
                    $definitionList[] = ['Defaults' => $this->export($data['defaults'])];
                }
                if (!empty($data['hosts'])) {
                    $definitionList[] = ['Hosts' => $this->export($data['hosts'])];
                }

                $io->definitionList(...$definitionList);
                if (!empty($middlewares)) {
                    $io->section('Middlewares');
                    foreach ($middlewares as $middleware) {
                        $io->writeln(is_string($middleware) ? $middleware : $this->export($middleware));
                    }
                }
            }

            return 0;
        }

        $table = new Table($output);
        $rows = [];
        foreach ($this->routeCollection->getRoutes() as $route) {
            $data = $route->__debugInfo();
            $action = '';
            if (!empty($data['enabledMiddlewares'])) {
                $middlewareDefinitions = $data['enabledMiddlewares'];
                $action = array_pop($middlewareDefinitions);
            }
            $rows[] = [
                'methods' => $this->export($data['methods']),
                'name' => $data['name'],
                'hosts' => $this->export($data['hosts']),
                'pattern' => $data['pattern'],
                'defaults' => $this->export($data['defaults']),
                'action' => $this->export($action),
            ];
        }
        $table->addRows($rows);
        $table->render();

        return 0;
    }

    protected function export(mixed $value): string
    {
        if (is_array($value)
            && count($value) === 2
            && isset($value[0], $value[1])
            && is_string($value[0])
            && is_string($value[1])
        ) {
            return $value[0] . '::' . $value[1];
        }
        if (is_array($value) && $this->isArrayList($value)) {
            return implode(', ', array_map($this->export(...), $value));
        }
        if (is_string($value)) {
            return $value;
        }
        return VarDumper::create($value)->asString();
    }

    /**
     * Polyfill for is_array_list() function.
     * It is available since PHP 8.1.
     */
    private function isArrayList(array $array): bool
    {
        if ([] === $array) {
            return true;
        }

        $nextKey = -1;

        foreach ($array as $k => $_) {
            if ($k !== ++$nextKey) {
                return false;
            }
        }

        return true;
    }
}
