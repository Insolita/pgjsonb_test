<?php

namespace app\queries;

class TypedValueQueries
{

    public static function sort():string
    {
        return <<<SQL
SELECT data.*,u.name as username
FROM form_data_raw data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values_typed vage on data.id = vage.data_id AND (vage.field ='age')
WHERE f.name = 'test_form' 
ORDER BY vage.value_int DESC;
SQL;
    }

    public static function multiSort():string
    {
        return <<<SQL
SELECT data.*,u.name as username
FROM form_data_raw data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values_typed vage on data.id = vage.data_id AND (vage.field ='age')
    INNER JOIN form_data_values_typed vactive on data.id = vactive.data_id AND (vactive.field ='active')
    INNER JOIN form_data_values_typed vgender on data.id = vgender.data_id AND (vgender.field ='gender')
WHERE f.name = 'test_form' 
ORDER BY vgender.value_str,vactive.value_bool, vage.value_int DESC;
SQL;
    }

    public static function dateFilter():string
    {
        return <<<SQL
SELECT data.*,u.name as username
FROM form_data_raw data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values_typed vdeadline 
        on data.id = vdeadline.data_id AND (vdeadline.field ='deadline' AND vdeadline.value_date <= now())
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
    INNER JOIN form_data_values_typed vdep 
        on data.id = vdep.data_id AND (vdep.field ='department' AND vdep.value_json ??& '{A, E}')
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
    INNER JOIN form_data_values_typed vpartner
        on data.id = vpartner.data_id AND (vpartner.field ='partner')
    INNER JOIN users p on p.id = vpartner.value_userfk
WHERE f.name = 'test_form' 
ORDER BY p.name;
SQL;
    }

    public static function aggregate():string
    {
        return <<<SQL
SELECT count(vgender.value_str), vgender.value_str as gender, vactive.value_bool as active
FROM form_data_raw data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values_typed vgender on data.id = vgender.data_id AND (vgender.field ='gender')
    INNER JOIN form_data_values_typed vactive on data.id = vactive.data_id AND (vactive.field ='active')
WHERE f.name = 'test_form' 
GROUP BY vgender.value_str, vactive.value_bool
SQL;
    }

    public static function averageFrom():string
    {
        return <<<SQL
SELECT avg(vage.value_int) as age
FROM form_data_raw data
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values_typed vactive
        on data.id = vactive.data_id AND (vactive.field ='active' AND vactive.value_bool = false)
    INNER JOIN form_data_values_typed vgender
        on data.id = vgender.data_id AND (vgender.field ='gender' AND vgender.value_str ='female')
    INNER JOIN form_data_values_typed vdedline
        on data.id = vdedline.data_id
               AND (vdedline.field ='deadline'
                        AND date_part('month', vdedline.value_date) = date_part('month', NOW() + interval'1 month'))
    INNER JOIN form_data_values_typed vage on data.id = vage.data_id AND vage.field ='age'
WHERE f.name = 'test_form'
SQL;
    }

    public static function greaterThanAverage():string
    {
        return <<<SQL
WITH avg_active_age as (SELECT avg(v.value_int) as age from form_data_values_typed v
     INNER JOIN form_data_raw fdr on fdr.id = v.data_id
     INNER JOIN forms f on f.id = fdr.form_id
     INNER JOIN form_data_values_typed vactive 
         on fdr.id = vactive.data_id AND (vactive.field ='active' AND vactive.value_bool = true)
     WHERE v.field = 'age' and f.name = 'test_form')
SELECT data.*,u.name as username,  vdep.value_json as dep, vage.value_int as age
FROM form_data_raw data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values_typed vdep
        on data.id = vdep.data_id
               AND (vdep.field ='department' AND not (vdep.value_json ??| '{A, B, C}'))
    INNER JOIN form_data_values_typed vage
        on data.id = vage.data_id AND (vage.field ='age' AND vage.value_int > (SELECT age FROM avg_active_age))
WHERE f.name = 'test_form' ORDER BY vage.value_int, u.name;
SQL;
    }

    public static function multiFilter():string
    {
        return <<<SQL
SELECT data.*,u.name as username, vactive.value_bool as active, vdep.value_json as dep, vage.value_int as age
FROM form_data_raw data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values_typed vactive
        on data.id = vactive.data_id AND (vactive.field ='active' AND vactive.value_bool = true)
    INNER JOIN form_data_values_typed vdep
        on data.id = vdep.data_id AND (vdep.field ='department' AND vdep.value_json ??| '{B, E}')
    INNER JOIN form_data_values_typed vage
        on data.id = vage.data_id AND (vage.field ='age' AND vage.value_int between 18 and 65)
WHERE f.name = 'test_form' 
ORDER BY vage.value_int
SQL;
    }

    public static function multiFilterFkInject():string
    {
        return <<<SQL
SELECT data.*,u.name as username, vgender.value_str as gender, vdedline.value_date as deadline,
       vpartner.value_userfk as partner_id, p.name as partner
FROM form_data_raw data
    INNER JOIN users u on u.id = data.user_id
    INNER JOIN forms f on f.id = data.form_id
    INNER JOIN form_data_values_typed vgender
        on data.id = vgender.data_id
               AND (vgender.field ='gender' AND vgender.value_str ='male')
    INNER JOIN form_data_values_typed vpartner
        on data.id = vpartner.data_id
               AND (vpartner.field ='partner')
    INNER JOIN users p on p.id = vpartner.value_userfk
    INNER JOIN form_data_values_typed vdedline
        on data.id = vdedline.data_id AND (vdedline.field ='deadline' AND vdedline.value_date > now())
WHERE f.name = 'test_form' 
ORDER BY vdedline.value_date;
SQL;
    }
}