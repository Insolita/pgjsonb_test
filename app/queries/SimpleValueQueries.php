<?php

namespace app\queries;

class SimpleValueQueries
{

    public static function sort():string
    {
        return <<<SQL
SELECT data.*,u.name as username
FROM form_data_raw data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values vage on data.id = vage.data_id AND (vage.field ='age')
WHERE f.name = 'test_form' 
ORDER BY vage.value::int DESC;
SQL;
    }

    public static function multiSort():string
    {
        return <<<SQL
SELECT data.*,u.name as username
FROM form_data_raw data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values vage on data.id = vage.data_id AND (vage.field ='age')
    INNER JOIN form_data_values vactive on data.id = vactive.data_id AND (vactive.field ='active')
    INNER JOIN form_data_values vgender on data.id = vgender.data_id AND (vgender.field ='gender')
WHERE f.name = 'test_form' 
ORDER BY vgender.value,vactive.value, vage.value::int DESC;
SQL;
    }

    public static function dateFilter():string
    {
        return <<<SQL
SELECT data.*,u.name as username
FROM form_data_raw data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values vdeadline on data.id = vdeadline.data_id
                                                 AND (vdeadline.field ='deadline' AND vdeadline.value::date <= now())
WHERE f.name = 'test_form';
SQL;
    }

    public static function jsonFilter():string
    {
        return <<<SQL
SELECT data.*,u.name as username
FROM form_data_raw data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values vdep on data.id = vdep.data_id
                                            AND (vdep.field ='department' AND vdep.value::jsonb ??& '{A, E}')
WHERE f.name = 'test_form';
SQL;
    }

    public static function fkInject():string
    {
        return <<<SQL
SELECT data.*,u.name as username, p.name as partner
FROM form_data_raw data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values vpartner on data.id = vpartner.data_id AND (vpartner.field ='partner')
    INNER JOIN users p on p.id = vpartner.value::int
WHERE f.name = 'test_form' 
ORDER BY p.name;
SQL;
    }

    public static function aggregate():string
    {
        return <<<SQL
SELECT count(vgender.value), vgender.value as gender, vactive.value as active
FROM form_data_raw data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values vgender on data.id = vgender.data_id AND (vgender.field ='gender')
    INNER JOIN form_data_values vactive on data.id = vactive.data_id AND (vactive.field ='active')
WHERE f.name = 'test_form' 
GROUP BY vgender.value, vactive.value
SQL;
    }

    public static function averageFrom():string
    {
        return <<<SQL
SELECT avg(vage.value::int) as age
FROM form_data_raw data
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values vactive
        on data.id = vactive.data_id AND (vactive.field ='active' AND vactive.value = '')
    INNER JOIN form_data_values vgender
        on data.id = vgender.data_id AND (vgender.field ='gender' AND vgender.value ='female')
    INNER JOIN form_data_values vdedline
        on data.id = vdedline.data_id
               AND (vdedline.field ='deadline'
                        AND date_part('month', vdedline.value::date) = date_part('month', NOW() + interval'1 month'))
    INNER JOIN form_data_values vage on data.id = vage.data_id AND vage.field ='age'
WHERE f.name = 'test_form';
SQL;
    }

    public static function greaterThanAverage():string
    {
        return <<<SQL
WITH avg_active_age as (SELECT avg(v.value::int) as age from form_data_values v
   INNER JOIN form_data_raw fdr on fdr.id = v.data_id
   INNER JOIN forms f on f.id = fdr.form_id
   INNER JOIN form_data_values vactive on fdr.id = vactive.data_id AND (vactive.field ='active' AND vactive.value = '1')
WHERE v.field = 'age' and f.name = 'test_form')
SELECT data.*,u.name as username,  vdep.value as dep, vage.value as age
FROM form_data_raw data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values vdep
        on data.id = vdep.data_id AND (vdep.field ='department' AND not (vdep.value::jsonb ??| '{A, B, C}'))
    INNER JOIN form_data_values vage
        on data.id = vage.data_id AND (vage.field ='age' AND vage.value::int > (SELECT age FROM avg_active_age))
WHERE f.name = 'test_form' 
ORDER BY vage.value::int, u.name;
SQL;
    }

    public static function multiFilter():string
    {
        return <<<SQL
SELECT data.*,u.name as username, vactive.value as active, vdep.value as dep, vage.value as age
FROM form_data_raw data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values vactive
        on data.id = vactive.data_id AND (vactive.field ='active' AND vactive.value = '1')
    INNER JOIN form_data_values vdep
        on data.id = vdep.data_id AND (vdep.field ='department' AND vdep.value::jsonb ??| '{B, E}')
    INNER JOIN form_data_values vage
        on data.id = vage.data_id AND (vage.field ='age' AND vage.value::int between 18 and 65)
WHERE f.name = 'test_form' 
ORDER BY vage.value::int;
SQL;
    }

    public static function multiFilterFkInject():string
    {
        return <<<SQL
SELECT data.*,u.name as username, vgender.value as gender, vdedline.value as deadline,
       vpartner.value as partner_id, p.name as partner
FROM form_data_raw data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values vgender
        on data.id = vgender.data_id AND (vgender.field ='gender' AND vgender.value ='male')
    INNER JOIN form_data_values vpartner
        on data.id = vpartner.data_id AND (vpartner.field ='partner')
    INNER JOIN users p on p.id = vpartner.value::int
    INNER JOIN form_data_values vdedline
        on data.id = vdedline.data_id AND (vdedline.field ='deadline' AND vdedline.value::date > now())
WHERE f.name = 'test_form' 
ORDER BY vdedline.value;
SQL;
    }
}