--TEST--
PDO PgSQL: NULL, empty and special float values are fetched distinctly
--EXTENSIONS--
pdo_pgsql
--SKIPIF--
<?php
require __DIR__ . '/config.inc';
require dirname(__DIR__, 2) . '/pdo/tests/pdo_test.inc';
PDOTest::skip();
?>
--FILE--
<?php
require __DIR__ . '/../../../ext/pdo/tests/pdo_test.inc';
$db = PDOTest::test_factory(__DIR__ . '/common.phpt');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);

foreach ([false, true] as $emulate) {
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, $emulate);
    $stmt = $db->query(<<<'SQL'
    SELECT v AS text_value, NULLIF(v, '') AS null_if_empty, length(v)::int4 AS len,
           CASE WHEN v IS NULL THEN NULL ELSE v = '' END AS is_empty,
           CASE v WHEN 'x' THEN 1.5::float8 WHEN 'Infinity' THEN 'Infinity'::float8
                  WHEN '-Infinity' THEN '-Infinity'::float8 WHEN 'NaN' THEN 'NaN'::float8 END AS float_value
    FROM (VALUES (1, ''), (2, NULL), (3, 'x'), (4, 'Infinity'), (5, '-Infinity'), (6, 'NaN')) AS t(n, v)
    ORDER BY n
    SQL);
    foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $row) {
        echo json_encode(array_map(fn($v) => get_debug_type($v) . ':' . var_export($v, true), $row)), "\n";
    }
}
?>
--EXPECT--
["string:''","null:NULL","int:0","bool:true","null:NULL"]
["null:NULL","null:NULL","null:NULL","null:NULL","null:NULL"]
["string:'x'","string:'x'","int:1","bool:false","float:1.5"]
["string:'Infinity'","string:'Infinity'","int:8","bool:false","float:INF"]
["string:'-Infinity'","string:'-Infinity'","int:9","bool:false","float:-INF"]
["string:'NaN'","string:'NaN'","int:3","bool:false","float:NAN"]
["string:''","null:NULL","int:0","bool:true","null:NULL"]
["null:NULL","null:NULL","null:NULL","null:NULL","null:NULL"]
["string:'x'","string:'x'","int:1","bool:false","float:1.5"]
["string:'Infinity'","string:'Infinity'","int:8","bool:false","float:INF"]
["string:'-Infinity'","string:'-Infinity'","int:9","bool:false","float:-INF"]
["string:'NaN'","string:'NaN'","int:3","bool:false","float:NAN"]
