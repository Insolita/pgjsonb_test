<?php

namespace app\queries;

class JsonQueries
{

    public static function sort():string
    {
        return <<<SQL
SELECT data.*,u.name as username
FROM form_data_json data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
WHERE f.name = 'test_form'
ORDER BY cast(data.values->'age'->>'value' as int) DESC;
SQL;
    }

    public static function multiSort():string
    {
        return <<<SQL
SELECT data.*,u.name as username
FROM form_data_json data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
WHERE f.name = 'test_form'
ORDER BY data.values->'gender'->>'value',
         cast(data.values->'active'->>'value' as bool),
         cast(data.values->'age'->>'value' as int) DESC;
SQL;
    }

    public static function dateFilter():string
    {
        return <<<SQL
SELECT data.*,u.name as username
FROM form_data_json data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
WHERE f.name = 'test_form'
AND cast(data.values->'deadline'->>'value' as date)  <= now()
SQL;
    }

    public static function jsonFilter():string
    {
        return <<<SQL
SELECT data.*,u.name as username
FROM form_data_json data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
WHERE f.name = 'test_form'
AND cast(data.values->'partner'->>'value' as jsonb)  ??& '{A, E}'
SQL;
    }

    public static function fkInject():string
    {
        return <<<SQL
SELECT data.*,u.name as username, p.name as partner
FROM form_data_json data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN users p on p.id = cast(data.values->'partner'->>'value' as int)
WHERE f.name = 'test_form' ORDER BY p.name
SQL;
    }

    public static function aggregate():string
    {
        return <<<SQL
SELECT count(data.values->'gender'->>'value') as cnt,
       data.values->'gender'->>'value' as gender,
       data.values->'active'->>'value' as active
FROM form_data_json data
    INNER JOIN forms f on f.id = data.form_id
WHERE f.name = 'test_form' 
GROUP BY data.values->'gender'->>'value', data.values->'active'->>'value';
SQL;
    }

    public static function averageFrom():string
    {
        return <<<SQL
SELECT avg(cast(data.values->'age'->>'value' as int)) as age
FROM form_data_json data
    INNER JOIN forms f on f.id = data.form_id
WHERE f.name = 'test_form'
  AND cast(data.values->'active'->>'value' as bool) = false
  AND data.values->'gender'->>'value' = 'female'
  AND date_part('month', cast(data.values->'deadline'->>'value' as date)) = date_part('month', NOW() + interval'1 month')
SQL;
    }

    public static function greaterThanAverage():string
    {
        return <<<SQL
WITH avg_active_age as
    (SELECT avg(cast(v.values->'age'->>'value' as int)) as age from form_data_json v
        INNER JOIN forms f on f.id = v.form_id
      WHERE f.name = 'test_form' AND cast(v.values->'active'->>'value' as bool) = true)
SELECT data.*,u.name as username,
       cast(data.values->'department'->>'value' as jsonb) as dep,
       cast(data.values->'age'->>'value' as int) as age
FROM form_data_json data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
WHERE f.name = 'test_form'
  AND not cast(data.values->'department'->>'value' as jsonb) ??| '{A, B, C}'
  AND cast(data.values->'age'->>'value' as int) > (SELECT age FROM avg_active_age)
ORDER BY cast(data.values->'age'->>'value' as int), u.name;
SQL;
    }

    public static function multiFilter():string
    {
        return <<<SQL
SELECT data.*,u.name as username,
       cast(data.values->'active'->>'value' as bool) as active,
       cast(data.values->'age'->>'value' as int) as age,
       cast(data.values->'department'->>'value' as jsonb) as dep
FROM form_data_json data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
WHERE f.name = 'test_form'
  AND cast(data.values->'active'->>'value' as bool) = true
  AND cast(data.values->'department'->>'value' as jsonb) ??| '{B, E}'
  AND cast(data.values->'age'->>'value' as int) between 18 and 65
ORDER BY cast(data.values->'age'->>'value' as int);
SQL;
    }

    public static function multiFilterFkInject():string
    {
        return <<<SQL
SELECT data.*,u.name as username,
       data.values->'gender'->>'value' as gender,
       cast(data.values->'deadline'->>'value' as date) as deadline,
       cast(data.values->'partner'->>'value' as int) as partner_id,
       p.name as partner
FROM form_data_json data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN users p on p.id = cast(data.values->'partner'->>'value' as int)
WHERE f.name = 'test_form'
  AND data.values->'gender'->>'value' = 'male'
  AND cast(data.values->'deadline'->>'value' as date)  > now()
ORDER BY cast(data.values->'deadline'->>'value' as date);
SQL;
    }
}