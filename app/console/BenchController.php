<?php

namespace app\console;

use app\queries\JsonIndexedQueries;
use app\queries\JsonQueries;
use app\queries\SimpleValueQueries;
use app\queries\TypedValueQueries;
use Yii;
use yii\console\Controller;
use yii\console\widgets\Table;
use yii\db\Connection;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\Inflector;
use yii\helpers\Json;
use function array_map;
use function array_sum;
use function count;
use function date;
use function file_put_contents;
use function microtime;
use function round;
use function usleep;

class BenchController extends Controller
{
    private const DB = ['db10', 'db11', 'db12', 'db13'];  //Number of queries execution, avg will be calculated

    public int $repeat = 10; // Delay between same queries (milliseconds)

    public int $delay = 500000;

    private $result;

    public function options($actionID)
    {
        return ['repeat', 'delay'];
    }

    public function actionIndex()
    {
        $queries = $this->getQueries();
        foreach (self::DB as $db) {
            $connection = Yii::$app->get($db);
            $this->stdout("Check $db \n", Console::FG_PURPLE);
            foreach ($queries as $query) {
                $this->stdout(" --- {$query['name']} : {$query['type']} \n", Console::FG_CYAN);
                $timing = [];
                $progress = 0;
                $total = $this->repeat * 2;
                Console::startProgress(0, $total);
                for ($i = 0; $i <= $this->repeat; $i++) {
                    if ($i === 0) {
                        $this->bench($connection, $query['sql']);
                        usleep($this->delay);
                        continue;
                    }
                    $timing[] = $this->bench($connection, $query['sql']);
                    $progress++;
                    Console::updateProgress($progress, $total);
                    usleep($this->delay);
                    $progress++;
                    Console::updateProgress($progress, $total);
                }
                Console::endProgress(true);
                $avg = round(array_sum($timing) / count($timing), 4);
                $min = round(min($timing), 4);
                $max = round(max($timing), 4);
                $this->stdout("               Avg:$avg, Min:$min, Max: $max\n", Console::FG_YELLOW);
                $this->result[] = ['db' => $db, 'query' => $query['name'], 'type' => $query['type'], 'time' => $avg];
            }
        }
        $result = ArrayHelper::index($this->result, 'db', ['query', 'type']);
        $rows = [];
        foreach ($result as $query => $types) {
            foreach ($types as $type => $dbGroup) {
                $dbTimes = array_map(fn($v) => $dbGroup[$v]['time'], self::DB);
                $rows[] = [Inflector::titleize($query), $type, ...$dbTimes];
            }
        }
        echo Table::widget([
            'headers' => ['query', 'type', 'pg10', 'pg11', 'pg12', 'pg13'],
            'rows' => $rows,
        ]);
        file_put_contents(Yii::getAlias('@runtime/' . date('Y_m_d_H_i_s') . '.json'), Json::encode($this->result));
    }

    private function bench(Connection $db, string $sql):float
    {
        $start = microtime(true);
        $db->createCommand(new Expression($sql))->queryAll();
        $finish = microtime(true);
        return $finish - $start;
    }

    private function getQueries():array
    {

        return [
            ['name' => 'Sort', 'type' => 'typed', 'sql' => TypedValueQueries::sort()],
            ['name' => 'Sort', 'type' => 'simple', 'sql' => SimpleValueQueries::sort()],
            ['name' => 'Sort', 'type' => 'json', 'sql' => JsonQueries::sort()],
            ['name' => 'Sort', 'type' => 'json_indx', 'sql' => JsonIndexedQueries::sort()],
            ['name' => 'MultiSort', 'type' => 'typed', 'sql' => TypedValueQueries::multiSort()],
            ['name' => 'MultiSort', 'type' => 'simple', 'sql' => SimpleValueQueries::multiSort()],
            ['name' => 'MultiSort', 'type' => 'json', 'sql' => JsonQueries::multiSort()],
            ['name' => 'MultiSort', 'type' => 'json_indx', 'sql' => JsonIndexedQueries::multiSort()],
            ['name' => 'DateFilter', 'type' => 'typed', 'sql' => TypedValueQueries::dateFilter()],
            ['name' => 'DateFilter', 'type' => 'simple', 'sql' => SimpleValueQueries::dateFilter()],
            ['name' => 'DateFilter', 'type' => 'json', 'sql' => JsonQueries::dateFilter()],
            ['name' => 'DateFilter', 'type' => 'json_indx', 'sql' => JsonIndexedQueries::dateFilter()],
            ['name' => 'JsonFilter', 'type' => 'typed', 'sql' => TypedValueQueries::jsonFilter()],
            ['name' => 'JsonFilter', 'type' => 'simple', 'sql' => SimpleValueQueries::jsonFilter()],
            ['name' => 'JsonFilter', 'type' => 'json', 'sql' => JsonQueries::jsonFilter()],
            ['name' => 'JsonFilter', 'type' => 'json_indx', 'sql' => JsonIndexedQueries::jsonFilter()],
            ['name' => 'ForeignKeyInject', 'type' => 'typed', 'sql' => TypedValueQueries::fkInject()],
            ['name' => 'ForeignKeyInject', 'type' => 'simple', 'sql' => SimpleValueQueries::fkInject()],
            ['name' => 'ForeignKeyInject', 'type' => 'json', 'sql' => JsonQueries::fkInject()],
            ['name' => 'ForeignKeyInject', 'type' => 'json_indx', 'sql' => JsonIndexedQueries::fkInject()],
            ['name' => 'GroupByCount', 'type' => 'typed', 'sql' => TypedValueQueries::aggregate()],
            ['name' => 'GroupByCount', 'type' => 'simple', 'sql' => SimpleValueQueries::aggregate()],
            ['name' => 'GroupByCount', 'type' => 'json', 'sql' => JsonQueries::aggregate()],
            ['name' => 'GroupByCount', 'type' => 'json_indx', 'sql' => JsonIndexedQueries::aggregate()],
            [
                'name' => 'Filters by bool,json,int Sort by int',
                'type' => 'typed',
                'sql' => TypedValueQueries::multiFilter(),
            ],
            [
                'name' => 'Filters by bool,json,int Sort by int',
                'type' => 'simple',
                'sql' => SimpleValueQueries::multiFilter(),
            ],
            [
                'name' => 'Filters by bool,json,int Sort by int',
                'type' => 'json',
                'sql' => JsonQueries::multiFilter(),
            ],
            [
                'name' => 'Filters by bool,json,int Sort by int',
                'type' => 'json_indx',
                'sql' => JsonIndexedQueries::multiFilter(),
            ],
            [
                'name' => 'Inject fk, filter and sort by date',
                'type' => 'typed',
                'sql' => TypedValueQueries::multiFilterFkInject(),
            ],
            [
                'name' => 'Inject fk, filter and sort by date',
                'type' => 'simple',
                'sql' => SimpleValueQueries::multiFilterFkInject(),
            ],
            [
                'name' => 'Inject fk, filter and sort by date',
                'type' => 'json',
                'sql' => JsonQueries::multiFilterFkInject(),
            ],
            [
                'name' => 'Inject fk, filter and sort by date',
                'type' => 'json_indx',
                'sql' => JsonIndexedQueries::multiFilterFkInject(),
            ],
            ['name' => 'Average from filtered', 'type' => 'typed', 'sql' => TypedValueQueries::averageFrom()],
            ['name' => 'Average from filtered', 'type' => 'simple', 'sql' => SimpleValueQueries::averageFrom()],
            ['name' => 'Average from filtered', 'type' => 'json', 'sql' => JsonQueries::averageFrom()],
            ['name' => 'Average from filtered', 'type' => 'json_indx', 'sql' => JsonIndexedQueries::averageFrom()],
            [
                'name' => 'Filter greater than average value',
                'type' => 'typed',
                'sql' => TypedValueQueries::greaterThanAverage(),
            ],
            [
                'name' => 'Filter greater than average value',
                'type' => 'simple',
                'sql' => SimpleValueQueries::greaterThanAverage(),
            ],
            [
                'name' => 'Filter greater than average value',
                'type' => 'json',
                'sql' => JsonQueries::greaterThanAverage(),
            ],
            [
                'name' => 'Filter greater than average value',
                'type' => 'json_indx',
                'sql' => JsonIndexedQueries::greaterThanAverage(),
            ],
        ];
    }
}