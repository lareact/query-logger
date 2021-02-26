<?php


namespace Golly\QueryLogger;


use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;

/**
 * Class QueryLogger
 * @package Golly\QueryLogger
 */
class QueryLogger
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * Number of executed queries.
     *
     * @var int
     */
    protected $queryNumber = 0;

    /**
     * @var int
     */
    protected $queryTime = 0;

    /**
     * @var array
     */
    protected $queries = [];

    /**
     * Milliseconds
     *
     * @var int
     */
    protected $slowTime = 200;

    /**
     * @var array
     */
    protected $slowQueries = [];

    /**
     * 输出模版
     *
     * @var string
     */
    protected $output = '[[[query_number]]][[[query_time]]] [[query]]';

    /**
     * QueryLogger constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * 打印日志
     *
     * @return void
     */
    public function __destruct()
    {
        if (!$this->queryNumber) {
            return;
        }
        $lines[] = $this->getRunningInfo();
        foreach ($this->queries as $query) {
            $lines[] = $query;
        }
        $this->write(implode(PHP_EOL, $lines));
    }

    /**
     * @param QueryExecuted $query
     * @return $this
     */
    public function setQuery(QueryExecuted $query)
    {
        $this->queryNumber++;
        $this->queryTime += $query->time;
        $sql = $this->getQueryInfo($query);
        $this->queries[] = $sql;
        if ($this->slow($query->time)) {
            $this->slowQueries[] = $sql;
        }

        return $this;
    }


    /**
     * @return string
     */
    protected function getRunningInfo()
    {
        return $this->app->runningInConsole()
            ? $this->getArtisanInfo()
            : $this->getRequestInfo();
    }


    /**
     * Get Artisan line.
     *
     * @return string
     */
    protected function getArtisanInfo()
    {
        $command = $this->app['request']->server('argv', []);
        if (is_array($command)) {
            $command = implode(' ', $command);
        }

        return '[CONSOLE] ' . $command;
    }

    /**
     * Get request line.
     *
     * @return string
     */
    protected function getRequestInfo()
    {
        return sprintf(
            '[HTTP][%s]%s %s',
            $this->time($this->queryTime),
            $this->app['request']->method(),
            $this->app['request']->path()
        );
    }

    /**
     * @return string
     */
    protected function getQueryInfo(QueryExecuted $query)
    {
        foreach ($query->bindings as &$binding) {
            if (is_string($binding)) {
                $binding = "'{$binding}'";
            }
        }
        $sql = vsprintf(
            str_replace('?', '%s', $query->sql),
            $query->bindings
        );
        $replace = [
            '[[query_number]]' => $this->queryNumber,
            '[[query_time]]' => $this->time($query->time),
            '[[query]]' => $sql
        ];

        return str_replace(array_keys($replace), array_values($replace), $this->output);
    }

    /**
     * @param $time
     * @return bool
     */
    protected function slow($time)
    {
        return $time >= $this->slowTime;
    }

    /**
     * @param string $message
     * @return void
     */
    protected function write(string $message)
    {
        if ($this->app->runningInConsole()) {
            Log::channel('sql-console')->info($message);
        } else {
            Log::channel('sql-request')->info($message);
        }
    }

    /**
     * The number of milliseconds it took to execute the query.
     *
     * @param $time
     * @return string
     */
    public function time($time)
    {
        if ($time < 1) {
            return round($time * 1000) . 'μs';
        } elseif ($time < 1000) {
            return round($time, 2) . 'ms';
        }

        return round($time / 1000, 2) . 's';
    }
}
