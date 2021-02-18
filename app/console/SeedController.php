<?php

namespace app\console;

use Faker\Factory;
use Faker\Generator;
use Throwable;
use Yii;
use yii\console\Controller;
use yii\db\Connection;
use yii\db\Query;
use yii\helpers\Console;
use yii\helpers\Json;
use yii\helpers\VarDumper;
use function count;
use function is_array;

class SeedController extends Controller
{
    private const DB = ['db10', 'db11', 'db12', 'db13'];

    public int $users = 2000; // how many users will be generated

    public int $forms = 100; //how many different forms will be generated

    /**
     * @var \Faker\Generator
     */
    private Generator $faker;

    /**
     * @var array|int[]
     */
    private $userIds;

    /**
     * @var array|Connection[]
     */
    private array $connections = [];

    public function __construct($id, $module, $config = [])
    {
        $this->faker = Factory::create('en_US');
        parent::__construct($id, $module, $config);
        foreach (self::DB as $dbName) {
            $this->connections[$dbName] = Yii::$app->get($dbName);
        }
    }

    public function options($actionID)
    {
        return ['users', 'forms'];
    }

    public function actionIndex()
    {
        $this->actionTruncate();
        $this->stdout("Seed started...\n", Console::FG_PURPLE);
        $this->seedUsers();
        $this->seedForms();
        $this->seedFormData();
        $this->seedFormValues();
        $this->stdout("\nSeed finished!\n", Console::FG_GREEN);
    }

    public function actionTruncate()
    {
        foreach (self::DB as $dbName) {
            $db = $this->connections[$dbName];
            $db->createCommand('TRUNCATE form_data_values RESTART IDENTITY')->execute();
            $db->createCommand('TRUNCATE form_data_values_typed RESTART IDENTITY')->execute();
            $db->createCommand('TRUNCATE forms RESTART IDENTITY CASCADE')->execute();
            $db->createCommand('TRUNCATE users RESTART IDENTITY CASCADE')->execute();
            $this->stdout("  Db {$dbName} truncated! ", Console::FG_GREEN);
        }
        $this->stdout("\n");
    }

    private function seedUsers()
    {
        $this->stdout(" --- Seed users...\n", Console::FG_CYAN);
        $count = 0;
        $total = $limit = $this->users;
        Console::startProgress(0, $total);
        while ($limit > 0) {
            $batch = [];
            $batchSize = min($limit, 100);
            for ($i = 0; $i < $batchSize; $i++) {
                $batch[] = [$this->faker->unique()->userName];
            }
            foreach (self::DB as $dbName) {
                $this->connections[$dbName]->createCommand()->batchInsert('users', ['name'], $batch)->execute();
            }
            $limit -= $batchSize;
            $count += $batchSize;
            Console::updateProgress($count, $total);
        }
        Console::endProgress();
    }

    private function seedForms()
    {
        $this->stdout(" --- Seed forms...\n", Console::FG_CYAN);
        $batch = [];
        for ($i = 0; $i < $this->forms; $i++) {
            $batch[] = [$this->faker->word.'_'.$this->faker->unique()->firstName, $this->generateFields()];
        }
        $batch[] = [
            'test_form',
            [
                ['name' => 'age', 'type' => 'int'],
                ['name' => 'gender', 'type' => 'str'],
                ['name' => 'department', 'type' => 'json'],
                ['name' => 'deadline', 'type' => 'date'],
                ['name' => 'active', 'type' => 'bool'],
                ['name' => 'partner', 'type' => 'userfk'],
            ],
        ];
        foreach (self::DB as $dbName) {
            $this->connections[$dbName]->createCommand()->batchInsert('forms', ['name', 'fields'], $batch)->execute();
        }
    }

    private function seedFormData()
    {
        $this->stdout(" --- Seed forms data...\n", Console::FG_CYAN);
        $forms = (new Query())->from('forms')->all($this->connections['db10']);
        $users = (new Query())->from('users');
        $total = $users->count('*', $this->connections['db10']);
        Console::startProgress(0, $total);
        foreach ($users->each(200, $this->connections['db10']) as $i => $user) {
            $dataJsonBatch = $dataRawBatch = [];
            foreach ($forms as $form) {
                $formFields = is_array($form['fields']) ? $form['fields'] : Json::decode($form['fields']);
                if ($form['name'] === 'test_form') {
                    $values = [
                        'age' => ['value' => $this->faker->numberBetween(10, 75), 'type' => 'int'],
                        'gender' => ['value' => $this->faker->randomElement(['male', 'female']), 'type' => 'str'],
                        'department' => [
                            'value' => $this->faker->randomElements(
                                ['A', 'B', 'C', 'D', 'E'],
                                $this->faker->randomElement([1, 2, 3])
                            ),
                            'type' => 'json',
                        ],
                        'deadline' => [
                            'value' => $this->faker->dateTimeBetween('-3month', '+3month')->format('Y-m-d')
                            ,
                            'type' => 'date',
                        ],
                        'active' => ['value' => $this->faker->boolean, 'type' => 'bool'],
                        'partner' => ['value' => $this->faker->randomElement($this->getUserIds()), 'type' => 'userfk'],
                    ];
                } else {
                    $values = $this->generateValuesByFields($formFields);
                }
                $row = [$form['id'], $user['id'], $this->faker->slug, $this->faker->randomElement(['open', 'close'])];
                $dataRawBatch[] = $row;
                $dataJsonBatch[] = [...$row, $values, $values];
            }
            foreach (self::DB as $dbName) {
                $this->connections[$dbName]->createCommand()->batchInsert(
                    'form_data_json',
                    ['form_id', 'user_id', 'title', 'status', 'values', 'values_ind'],
                    $dataJsonBatch
                )->execute();
                $this->connections[$dbName]->createCommand()->batchInsert(
                    'form_data_raw',
                    ['form_id', 'user_id', 'title', 'status'],
                    $dataRawBatch
                )->execute();
            }
            Console::updateProgress($i + 1, $total);
        }
        Console::endProgress();
    }

    private function seedFormValues()
    {
        $this->stdout(" --- Seed forms values...\n", Console::FG_CYAN);
        $jsonData = (new Query())->from('form_data_json');
        $total = $jsonData->count('*',$this->connections['db10']);
        $processed = 0;
        $batchSize = 250;
        Console::startProgress(0, $total);
        foreach ($jsonData->batch($batchSize,  $this->connections['db10']) as $formData) {
            $batch = [];
            $batchTyped = [];
            foreach ($formData as $data) {
                $values = is_array($data['values']) ? $data['values'] : Json::decode($data['values']);
                foreach ($values as $name => $value) {
                    $row = [
                        $data['id'],
                        $name,
                        $value['type'] === 'json' ? Json::encode($value['value']) : $value['value'],
                    ];
                    $rowTyped = [
                        $data['id'],
                        $name,
                        ($value['type'] === 'bool') ? $value['value'] : null,
                        ($value['type'] === 'int') ? $value['value'] : null,
                        ($value['type'] === 'str') ? $value['value'] : null,
                        ($value['type'] === 'date') ? $value['value'] : null,
                        ($value['type'] === 'userfk') ? $value['value'] : null,
                        ($value['type'] === 'json') ? $value['value'] : null,
                    ];
                    $batch[] = $row;
                    $batchTyped[] = $rowTyped;
                }
            }
            foreach (self::DB as $dbName) {
                $db = $this->connections[$dbName];
                try {
                    $db->createCommand()->batchInsert(
                        'form_data_values',
                        ['data_id', 'field', 'value'],
                        $batch
                    )->execute();
                    $db->createCommand()->batchInsert(
                        'form_data_values_typed',
                        [
                            'data_id',
                            'field',
                            'value_bool',
                            'value_int',
                            'value_str',
                            'value_date',
                            'value_userfk',
                            'value_json',
                        ],
                        $batchTyped
                    )->execute();
                } catch (Throwable $e) {
                    VarDumper::dump($batch);
                    VarDumper::dump($e);
                    break;
                }
            }
            $processed += count($formData);
            Console::updateProgress($processed, $total);
        }
        Console::endProgress();

    }

    private function generateFields():array
    {
        $num = $this->faker->randomElement([2, 3, 4, 5, 6]);
        $fields = [];
        while (count($fields) <= $num) {
            $fieldName = $this->faker->word;
            $fields[$fieldName] = [
                'name' => $fieldName,
                'type' => $this->faker->randomElement(['bool', 'string', 'int', 'date', 'userfk', 'json']),
            ];
        }
        return $fields;
    }

    private function generateValuesByFields(array $fields):array
    {
        $values = [];
        foreach ($fields as $field) {
            switch ($field['type']) {
                case 'int':
                    $values[$field['name']] = ['type' => $field['type'], 'value' => $this->faker->randomNumber(2)];
                    break;
                case 'date':
                    $values[$field['name']] = [
                        'type' => $field['type'],
                        'value' => $this->faker->dateTimeThisYear->format('Y-m-d'),
                    ];
                    break;
                case 'bool':
                    $values[$field['name']] = [
                        'type' => $field['type'],
                        'value' => $this->faker->boolean,
                    ];
                    break;
                case 'str':
                    $values[$field['name']] =
                        ['type' => $field['type'], 'value' => $this->faker->word];
                    break;
                case 'userfk':
                    $values[$field['name']] =
                        ['type' => $field['type'], 'value' => $this->faker->randomElement($this->getUserIds())];
                    break;
                case 'json':
                    $values[$field['name']] =
                        [
                            'type' => $field['type'],
                            'value' => $this->faker->randomElements(['alpha', 'beta', 'gamma', 'delta', 'epsilon'], 3),
                        ];
                    break;
            }
        }
        return $values;
    }

    private function getUserIds():array
    {
        if (!$this->userIds) {
            $this->userIds = (new Query())->select(['id'])->from('users')->orderBy('id')->column($this->connections['db10']);
        }
        return $this->userIds;
    }
}